<?php

namespace App\Actions;

use App\Enums\TreeAccessLevel;
use App\Models\FamilyTree;
use App\Models\Site;
use App\Models\User;
use App\Support\Authorization\TreeAccessService;

class CreateDefaultFamilyTree
{
    public function __construct(
        private readonly TreeAccessService $treeAccess,
    ) {}

    public function execute(User $user): FamilyTree
    {
        $tree = $user->familyTrees()->create([
            'name' => trim($user->name)."'s Family Tree",
            'privacy' => 'private',
            'home_region' => $user->country_of_residence,
            'site_id' => Site::forUser($user)->id,
        ]);

        $this->treeAccess->grantTreeAccess($user, $tree, TreeAccessLevel::Owner);

        $ownerPerson = $tree->people()->create([
            'created_by' => $user->id,
            'given_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'surname' => $user->last_name,
            'birth_date' => $user->birth_date?->format('Y-m-d'),
            'sex' => 'unknown',
            'is_living' => true,
            'headline' => 'Account holder',
            'notes' => 'This profile was created automatically for the tree owner.',
        ]);

        $tree->update(['owner_person_id' => $ownerPerson->id]);

        return $tree;
    }
}
