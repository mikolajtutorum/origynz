<?php

namespace App\Services;

use App\Enums\MergeCandidateStatus;
use App\Models\MergeCandidate;
use App\Models\Person;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DuplicateDetectionService
{
    private const MIN_SCORE = 55;

    /**
     * Scan all persons from global-tree-enabled trees and create/update MergeCandidate
     * records for pairs whose similarity exceeds the threshold.
     *
     * Returns the count of new candidates created.
     */
    public function scan(): int
    {
        $people = Person::query()
            ->whereHas('familyTree', fn ($q) => $q->where('global_tree_enabled', true))
            ->where('exclude_from_global_tree', false)
            ->whereNull('merged_into_id')
            ->with('familyTree:id')
            ->get(['id', 'family_tree_id', 'given_name', 'surname', 'birth_date', 'birth_date_text', 'birth_place', 'sex']);

        $created = 0;

        // Group roughly by first letter of surname + sex to limit comparisons
        $buckets = $people->groupBy(fn (Person $p) => strtolower(substr($p->surname ?? '', 0, 1)).'_'.$p->sex);

        foreach ($buckets as $bucket) {
            /** @var Collection<int, Person> $bucket */
            $items = $bucket->values();
            $count = $items->count();

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $a = $items[$i];
                    $b = $items[$j];

                    // Skip same tree
                    if ($a->family_tree_id === $b->family_tree_id) {
                        continue;
                    }

                    $score = $this->similarity($a, $b);
                    if ($score < self::MIN_SCORE) {
                        continue;
                    }

                    // Normalise order so (a,b) always has a.id < b.id lexicographically
                    [$idA, $idB] = strcmp($a->id, $b->id) < 0 ? [$a->id, $b->id] : [$b->id, $a->id];

                    $existing = MergeCandidate::where('person_a_id', $idA)
                        ->where('person_b_id', $idB)
                        ->first();

                    if ($existing) {
                        $existing->update(['similarity_score' => $score]);
                    } else {
                        MergeCandidate::create([
                            'person_a_id'      => $idA,
                            'person_b_id'      => $idB,
                            'similarity_score' => $score,
                            'status'           => MergeCandidateStatus::Pending,
                        ]);
                        $created++;
                    }
                }
            }
        }

        return $created;
    }

    /**
     * Compute a 0-100 similarity score between two persons.
     */
    public function similarity(Person $a, Person $b): int
    {
        $score = 0;

        // Name similarity (0-40)
        $nameSim = $this->nameSimilarity(
            ($a->given_name ?? '').' '.($a->surname ?? ''),
            ($b->given_name ?? '').' '.($b->surname ?? ''),
        );
        $score += (int) round($nameSim * 40);

        // Birth year (0-35)
        $yearA = $this->extractYear($a);
        $yearB = $this->extractYear($b);
        if ($yearA && $yearB) {
            $diff = abs($yearA - $yearB);
            $score += match (true) {
                $diff === 0 => 35,
                $diff <= 2  => 25,
                $diff <= 5  => 15,
                $diff <= 10 => 5,
                default     => 0,
            };
        }

        // Birth place (0-25)
        if ($a->birth_place && $b->birth_place) {
            $placeSim = $this->nameSimilarity(
                strtolower($a->birth_place),
                strtolower($b->birth_place),
            );
            $score += (int) round($placeSim * 25);
        }

        return min(100, $score);
    }

    // -------------------------------------------------------------------------

    private function nameSimilarity(string $a, string $b): float
    {
        $a = strtolower(trim($a));
        $b = strtolower(trim($b));

        if ($a === '' || $b === '') {
            return 0.0;
        }
        if ($a === $b) {
            return 1.0;
        }

        similar_text($a, $b, $percent);

        return $percent / 100;
    }

    private function extractYear(Person $person): ?int
    {
        if ($person->birth_date) {
            return (int) $person->birth_date->format('Y');
        }

        if ($person->birth_date_text) {
            preg_match('/\b(\d{4})\b/', $person->birth_date_text, $m);
            if (isset($m[1])) {
                return (int) $m[1];
            }
        }

        return null;
    }
}
