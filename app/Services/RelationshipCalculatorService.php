<?php

namespace App\Services;

use App\Models\Person;
use App\Models\PersonRelationship;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RelationshipCalculatorService
{
    /**
     * Find the shortest relationship path between two people across all
     * global-tree-enabled trees. Returns an ordered list of steps, each containing
     * the person and the relationship label used to reach them, or null if no path.
     *
     * @return list<array{person: Person, via: string|null}>|null
     */
    public function findPath(Person $from, Person $to): ?array
    {
        if ($from->id === $to->id) {
            return [['person' => $from, 'via' => null]];
        }

        // Build adjacency list from ALL person_relationships in the system
        // (only for non-merged people to avoid stale edges)
        $rows = DB::table('person_relationships')
            ->whereNull('deleted_at')
            ->get(['person_id', 'related_person_id', 'type']);

        /** @var array<string, list<array{id: string, label: string}>> $graph */
        $graph = [];

        foreach ($rows as $row) {
            $graph[$row->person_id][]         = ['id' => $row->related_person_id, 'label' => $this->forwardLabel($row->type)];
            $graph[$row->related_person_id][] = ['id' => $row->person_id,         'label' => $this->reverseLabel($row->type)];
        }

        // BFS
        $visited  = [$from->id => true];
        $queue    = [[$from->id, []]]; // [currentId, pathSoFar]
        $maxDepth = 20;

        while (! empty($queue)) {
            [$currentId, $path] = array_shift($queue);

            if (count($path) > $maxDepth) {
                continue;
            }

            foreach ($graph[$currentId] ?? [] as $edge) {
                $neighbourId = $edge['id'];
                $label       = $edge['label'];

                if (isset($visited[$neighbourId])) {
                    continue;
                }

                $newPath = array_merge($path, [['id' => $neighbourId, 'via' => $label]]);

                if ($neighbourId === $to->id) {
                    return $this->hydratePathWithPeople($from, $newPath);
                }

                $visited[$neighbourId] = true;
                $queue[]               = [$neighbourId, $newPath];
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------

    private function forwardLabel(string $type): string
    {
        return match ($type) {
            'parent' => 'parent of',
            'child'  => 'child of',
            'spouse' => 'spouse of',
            default  => $type.' of',
        };
    }

    private function reverseLabel(string $type): string
    {
        return match ($type) {
            'parent' => 'child of',
            'child'  => 'parent of',
            'spouse' => 'spouse of',
            default  => $type.' of',
        };
    }

    /**
     * @param  list<array{id: string, via: string}>  $path
     * @return list<array{person: Person, via: string|null}>
     */
    private function hydratePathWithPeople(Person $from, array $path): array
    {
        $ids = array_column($path, 'id');
        $map = Person::whereIn('id', $ids)->get()->keyBy('id');

        $result = [['person' => $from, 'via' => null]];
        foreach ($path as $step) {
            $person = $map[$step['id']] ?? null;
            if ($person) {
                $result[] = ['person' => $person, 'via' => $step['via']];
            }
        }

        return $result;
    }
}
