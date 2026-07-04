<?php

namespace App\Services;

use App\Enums\MergeCandidateStatus;
use App\Models\MergeCandidate;
use App\Models\Person;
use App\Models\PersonMerge;
use App\Models\ProfileClaim;
use App\Models\ProfileDiscussion;
use App\Models\ProfileWatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PersonMergeService
{
    /**
     * Scalar fields that can be selected during merge conflict resolution.
     *
     * @return list<string>
     */
    public function mergeableFields(): array
    {
        return [
            'given_name', 'middle_name', 'alternative_name', 'surname', 'birth_surname',
            'prefix', 'suffix', 'nickname', 'sex',
            'birth_date', 'birth_date_text', 'death_date', 'death_date_text',
            'birth_place', 'death_place', 'cause_of_death', 'burial_place',
            'headline', 'notes', 'physical_description',
            'is_living',
        ];
    }

    /**
     * Execute a merge. $decisions is keyed by field name, value is 'a' or 'b'
     * indicating which person's value to keep (A = surviving, B = absorbed).
     *
     * @param  array<string, string>  $decisions
     */
    public function execute(Person $surviving, Person $absorbed, User $actor, array $decisions = []): Person
    {
        DB::transaction(function () use ($surviving, $absorbed, $actor, $decisions): void {
            // Apply field decisions — for any field where decision is 'b', copy from absorbed
            foreach ($this->mergeableFields() as $field) {
                $choice = $decisions[$field] ?? 'a';
                if ($choice === 'b' && $absorbed->{$field} !== null) {
                    $surviving->{$field} = $absorbed->{$field};
                } elseif ($choice === 'a' && $surviving->{$field} === null && $absorbed->{$field} !== null) {
                    // Fill any missing value from B even when choice is A
                    $surviving->{$field} = $absorbed->{$field};
                }
            }
            $surviving->save();

            // Migrate relationships
            $absorbed->outgoingRelationships()->update(['person_id' => $surviving->id]);
            $absorbed->incomingRelationships()->update(['related_person_id' => $surviving->id]);

            // Remove self-referential relationships that may have been created
            $surviving->outgoingRelationships()
                ->where('related_person_id', $surviving->id)
                ->delete();

            // Migrate media, events, source citations, discussions, watches, claims
            $absorbed->mediaItems()->update(['person_id' => $surviving->id]);
            $absorbed->events()->update(['person_id' => $surviving->id]);
            $absorbed->sourceCitations()->update(['person_id' => $surviving->id]);

            ProfileDiscussion::where('person_id', $absorbed->id)
                ->update(['person_id' => $surviving->id]);

            ProfileWatch::where('person_id', $absorbed->id)
                ->update(['person_id' => $surviving->id]);

            ProfileClaim::where('person_id', $absorbed->id)
                ->update(['person_id' => $surviving->id]);

            // Mark absorbed as merged
            $absorbed->merged_into_id = $surviving->id;
            $absorbed->saveQuietly();

            // Dismiss all merge candidates involving the absorbed person
            MergeCandidate::where('person_a_id', $absorbed->id)
                ->orWhere('person_b_id', $absorbed->id)
                ->update(['status' => MergeCandidateStatus::Merged]);

            // Record the merge event
            PersonMerge::create([
                'surviving_person_id' => $surviving->id,
                'absorbed_person_id' => $absorbed->id,
                'merged_by_user_id' => $actor->id,
                'field_decisions' => $decisions,
            ]);
        });

        return $surviving->fresh();
    }
}
