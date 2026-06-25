<?php

namespace App\Services;

use App\Models\FamilyTree;
use App\Models\Person;
use InvalidArgumentException;

/**
 * Creates the relationship edges when a new relative is attached to an anchor
 * person. Extracted from the legacy web PersonController so the headless API and
 * (until it is retired) the Blade UI can share identical behavior.
 */
class RelativeService
{
    /** @var list<string> */
    public const PARENT_ROLES = [
        'father', 'mother', 'parent', 'stepfather', 'stepmother',
        'adoptive-father', 'adoptive-mother', 'adoptive-parent', 'foster-parent', 'guardian',
    ];

    /** @var list<string> */
    public const CHILD_ROLES = [
        'son', 'daughter', 'child', 'stepchild', 'stepson', 'stepdaughter',
        'adopted-son', 'adopted-daughter', 'adopted-child', 'foster-child',
    ];

    /** @var list<string> */
    public const SIBLING_ROLES = ['brother', 'sister', 'half-brother', 'half-sister'];

    /** @var list<string> */
    public const SPOUSE_ROLES = ['partner', 'spouse'];

    /**
     * @param  array{relation_role?:string|null, relation_kind?:string|null}  $data
     */
    public function resolveRole(array $data): string
    {
        $role = (string) ($data['relation_role'] ?? '');

        if ($role !== '') {
            return $role;
        }

        return match ($data['relation_kind'] ?? null) {
            'parent' => 'parent',
            'spouse' => 'partner',
            'child' => 'child',
            default => throw new InvalidArgumentException('A relation_role or relation_kind is required.'),
        };
    }

    public function resolveSubtype(string $role, mixed $requestedSubtype): ?string
    {
        $requested = $this->normalizeSubtype($requestedSubtype);

        return match ($role) {
            'stepfather', 'stepmother', 'stepchild', 'stepson', 'stepdaughter' => 'step',
            'adoptive-father', 'adoptive-mother', 'adoptive-parent', 'adopted-son', 'adopted-daughter', 'adopted-child' => 'adoptive',
            'foster-parent', 'foster-child' => 'foster',
            'guardian' => 'guardian',
            default => $requested,
        };
    }

    /**
     * Wire up the relationship(s) for $newPerson relative to $anchorPerson and
     * return a human label describing the relationship that was created.
     */
    public function attach(FamilyTree $tree, Person $anchorPerson, Person $newPerson, string $role, ?string $relationshipSubtype): string
    {
        if (in_array($role, self::PARENT_ROLES, true)) {
            $tree->relationships()->firstOrCreate([
                'person_id' => $newPerson->id,
                'related_person_id' => $anchorPerson->id,
                'type' => 'parent',
            ], array_filter(['subtype' => $relationshipSubtype], fn ($v) => $v !== null && $v !== ''));

            return match ($relationshipSubtype) {
                'step' => ucfirst($role === 'stepmother' ? 'stepmother' : ($role === 'stepfather' ? 'stepfather' : 'step-parent')),
                'adoptive' => match ($role) {
                    'adoptive-father' => 'Adoptive father',
                    'adoptive-mother' => 'Adoptive mother',
                    default => 'Adoptive parent',
                },
                'foster' => 'Foster parent',
                'guardian' => 'Guardian',
                default => ucfirst($role === 'parent' ? 'parent' : $role),
            };
        }

        if (in_array($role, self::CHILD_ROLES, true)) {
            $tree->relationships()->firstOrCreate([
                'person_id' => $anchorPerson->id,
                'related_person_id' => $newPerson->id,
                'type' => 'parent',
            ], array_filter(['subtype' => $relationshipSubtype], fn ($v) => $v !== null && $v !== ''));

            return match ($relationshipSubtype) {
                'step' => match ($role) {
                    'stepson' => 'Stepson',
                    'stepdaughter' => 'Stepdaughter',
                    default => 'Stepchild',
                },
                'adoptive' => match ($role) {
                    'adopted-son' => 'Adopted son',
                    'adopted-daughter' => 'Adopted daughter',
                    default => 'Adopted child',
                },
                'foster' => 'Foster child',
                default => ucfirst($role === 'child' ? 'child' : $role),
            };
        }

        if (in_array($role, self::SPOUSE_ROLES, true)) {
            $tree->relationships()->firstOrCreate([
                'person_id' => $anchorPerson->id,
                'related_person_id' => $newPerson->id,
                'type' => 'spouse',
            ]);

            return 'Partner';
        }

        if (in_array($role, self::SIBLING_ROLES, true)) {
            $parentIds = $tree->relationships()
                ->where('type', 'parent')
                ->where('related_person_id', $anchorPerson->id)
                ->pluck('person_id')
                ->all();

            if ($parentIds === []) {
                $bridgeParent = $tree->people()->create([
                    'created_by' => $tree->user_id,
                    'given_name' => 'Unknown',
                    'surname' => $anchorPerson->surname,
                    'sex' => 'unknown',
                    'headline' => 'Auto-created relationship bridge',
                    'notes' => 'Created automatically so sibling relationships remain connected in the graph.',
                ]);

                $parentIds = [$bridgeParent->id];

                $tree->relationships()->firstOrCreate([
                    'person_id' => $bridgeParent->id,
                    'related_person_id' => $anchorPerson->id,
                    'type' => 'parent',
                ]);
            }

            $sharedParentIds = str_starts_with($role, 'half-') ? array_slice($parentIds, 0, 1) : $parentIds;

            foreach ($sharedParentIds as $parentId) {
                $tree->relationships()->firstOrCreate([
                    'person_id' => $parentId,
                    'related_person_id' => $newPerson->id,
                    'type' => 'parent',
                ]);
            }

            return match ($role) {
                'half-brother' => 'Half-brother',
                'half-sister' => 'Half-sister',
                default => ucfirst($role),
            };
        }

        throw new InvalidArgumentException("Unsupported relation role: {$role}");
    }

    public function normalizeSubtype(mixed $value): ?string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '', 'birth', 'biological' => null,
            'adopted', 'adoptive' => 'adoptive',
            'foster' => 'foster',
            'guardian', 'guardianship' => 'guardian',
            'step', 'stepchild' => 'step',
            default => null,
        };
    }

    /** @return list<string> */
    public function allowedRoles(): array
    {
        return [
            ...self::PARENT_ROLES,
            ...self::CHILD_ROLES,
            ...self::SIBLING_ROLES,
            ...self::SPOUSE_ROLES,
        ];
    }
}
