<?php

namespace App\Services;

use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\PersonRelationship;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GlobalTreePedigreeService
{
    private const CARD_WIDTH   = 200;
    private const CARD_HEIGHT  = 100;
    private const SPOUSE_GAP   = 10;
    private const UNIT_GAP     = 56;
    private const ROW_SPACING  = 200;
    private const TOP_PADDING  = 50;
    private const LEFT_PADDING = 60;

    public function __construct(
        private readonly GlobalTreePrivacyService $privacy,
    ) {}

    public function findRootPerson(User $user): ?Person
    {
        $trees = FamilyTree::where('user_id', $user->id)
            ->where('global_tree_enabled', true)
            ->whereNotNull('owner_person_id')
            ->get();

        foreach ($trees as $tree) {
            $person = Person::find($tree->owner_person_id);
            if ($person && ! $person->exclude_from_global_tree) {
                return $person;
            }
        }

        return null;
    }

    public function hasAnyEnabledTree(User $user): bool
    {
        return FamilyTree::where('user_id', $user->id)
            ->where('global_tree_enabled', true)
            ->exists();
    }

    /**
     * Build pedigree chart data for the given root person.
     *
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, string>>, 2: array<string, int>}
     */
    /**
     * @param  list<int>  $ownedTreeIds
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, string>>, 2: array<string, int>}
     */
    public function buildChart(Person $rootPerson, int $generations = 3, array $ownedTreeIds = []): array
    {
        $people = Person::where('family_tree_id', $rootPerson->family_tree_id)
            ->where('exclude_from_global_tree', false)
            ->get()
            ->keyBy('id');

        $relationships = PersonRelationship::where('family_tree_id', $rootPerson->family_tree_id)
            ->whereIn('person_id', $people->keys())
            ->whereIn('related_person_id', $people->keys())
            ->get();

        [$parentsMap, $childrenMap, $spousesMap] = $this->buildMaps($relationships);

        return $this->layout($rootPerson, $people, $parentsMap, $childrenMap, $spousesMap, $generations, $ownedTreeIds);
    }

    /**
     * @return array{0: array<int, list<int>>, 1: array<int, list<int>>, 2: array<int, list<int>>}
     */
    private function buildMaps(Collection $relationships): array
    {
        $parentsMap  = [];
        $childrenMap = [];
        $spousesMap  = [];

        foreach ($relationships as $rel) {
            if ($rel->type === 'spouse') {
                $this->push($spousesMap, $rel->person_id, $rel->related_person_id);
                $this->push($spousesMap, $rel->related_person_id, $rel->person_id);
            }
            if ($rel->type === 'parent') {
                $this->push($parentsMap, $rel->related_person_id, $rel->person_id);
                $this->push($childrenMap, $rel->person_id, $rel->related_person_id);
            }
            if ($rel->type === 'child') {
                $this->push($parentsMap, $rel->person_id, $rel->related_person_id);
                $this->push($childrenMap, $rel->related_person_id, $rel->person_id);
            }
        }

        return [$parentsMap, $childrenMap, $spousesMap];
    }

    private function push(array &$map, int $key, int $value): void
    {
        if (! isset($map[$key]) || ! in_array($value, $map[$key], true)) {
            $map[$key][] = $value;
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Person>  $peopleById
     * @param  array<int, list<int>>  $parentsMap
     * @param  array<int, list<int>>  $childrenMap
     * @param  array<int, list<int>>  $spousesMap
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, string>>, 2: array<string, int>}
     */
    private function layout(
        Person $focus,
        \Illuminate\Support\Collection $peopleById,
        array $parentsMap,
        array $childrenMap,
        array $spousesMap,
        int $generations,
        array $ownedTreeIds = []
    ): array {
        $rootRow     = $generations;
        $primaryRows = [$rootRow => [$focus->id]];
        $familyEdges = [];

        // Ancestors
        $current = [$focus->id];
        for ($step = 1; $step <= $generations; $step++) {
            $next = [];
            foreach ($current as $childId) {
                foreach ($parentsMap[$childId] ?? [] as $parentId) {
                    if (! in_array($parentId, $next, true)) {
                        $next[] = $parentId;
                    }
                    $familyEdges[] = ['from' => $parentId, 'to' => $childId];
                }
            }
            if ($next === []) {
                break;
            }
            $primaryRows[$rootRow - $step] = $next;
            $current = $next;
        }

        // Children (one generation down)
        if (! empty($childrenMap[$focus->id])) {
            $primaryRows[$rootRow + 1] = $childrenMap[$focus->id];
            foreach ($childrenMap[$focus->id] as $childId) {
                $familyEdges[] = ['from' => $focus->id, 'to' => $childId];
            }
        }

        ksort($primaryRows);

        $rows     = [];
        $rowOrder = [];

        foreach ($primaryRows as $rowIndex => $personIds) {
            $rowItems = [];
            $seen     = [];

            foreach ($personIds as $personId) {
                if (! $peopleById->has($personId) || isset($seen[$personId])) {
                    continue;
                }

                $role = $personId === $focus->id
                    ? 'focus'
                    : ($rowIndex < $rootRow ? 'ancestor' : 'descendant');

                $rowItems[]      = ['person_id' => $personId, 'role' => $role, 'linked_to' => null, 'is_primary' => true];
                $seen[$personId] = true;

                foreach ($spousesMap[$personId] ?? [] as $spouseId) {
                    if (! $peopleById->has($spouseId) || isset($seen[$spouseId])) {
                        continue;
                    }
                    $rowItems[]       = ['person_id' => $spouseId, 'role' => 'spouse', 'linked_to' => $personId, 'is_primary' => false];
                    $seen[$spouseId]  = true;
                }
            }

            if ($rowItems !== []) {
                $rows[$rowIndex] = $rowItems;
                $rowOrder[]      = $rowIndex;
            }
        }

        $cw = self::CARD_WIDTH;
        $sg = self::SPOUSE_GAP;
        $ug = self::UNIT_GAP;
        $rs = self::ROW_SPACING;
        $tp = self::TOP_PADDING;
        $lp = self::LEFT_PADDING;

        // First pass — compute canvas width
        $rowUnits       = [];
        $minCanvasWidth = 1360;

        foreach ($rowOrder as $rowIndex) {
            $items          = $rows[$rowIndex];
            $spouseByLinked = [];
            foreach ($items as $item) {
                if (! $item['is_primary']) {
                    $spouseByLinked[$item['linked_to']] = $item;
                }
            }

            $units     = [];
            $placedIds = [];
            foreach ($items as $item) {
                if (! $item['is_primary']) {
                    continue;
                }
                $spouse  = $spouseByLinked[$item['person_id']] ?? null;
                $units[] = ['primary' => $item, 'spouse' => $spouse];
                $placedIds[] = $item['person_id'];
                if ($spouse) {
                    $placedIds[] = $spouse['person_id'];
                }
            }
            foreach ($items as $item) {
                if (! $item['is_primary'] && ! in_array($item['person_id'], $placedIds, true)) {
                    $units[] = ['primary' => $item, 'spouse' => null];
                }
            }

            $rowUnits[$rowIndex] = $units;
            $numUnits            = count($units);
            $totalUW             = array_sum(array_map(
                fn ($u) => $u['spouse'] ? ($cw * 2 + $sg) : $cw,
                $units
            ));
            $rowWidth       = ($lp * 2) + $totalUW + (max(0, $numUnits - 1) * $ug);
            $minCanvasWidth = max($minCanvasWidth, $rowWidth + 80);
        }

        $canvasWidth     = $minCanvasWidth;
        $nodes           = [];
        $nodeMap         = [];
        $primaryByPerson = [];
        $spouseByPerson  = [];
        $allXByPerson    = [];

        // Second pass — place nodes
        foreach ($rowOrder as $visualRow => $rowIndex) {
            $units = $rowUnits[$rowIndex];

            usort($units, function ($ua, $ub) use ($parentsMap, $allXByPerson, $cw): int {
                $avgX = function (array $unit) use ($parentsMap, $allXByPerson, $cw): ?float {
                    $xs = [];
                    foreach ($parentsMap[$unit['primary']['person_id']] ?? [] as $pid) {
                        if (isset($allXByPerson[$pid])) {
                            $xs[] = $allXByPerson[$pid] + intdiv($cw, 2);
                        }
                    }
                    return $xs ? array_sum($xs) / count($xs) : null;
                };
                $xa = $avgX($ua);
                $xb = $avgX($ub);
                if ($xa === null && $xb === null) {
                    return 0;
                }
                if ($xa === null) {
                    return 1;
                }
                if ($xb === null) {
                    return -1;
                }
                return $xa <=> $xb;
            });

            $y          = $tp + ($visualRow * $rs);
            $numUnits   = count($units);
            $unitWidths = array_map(fn ($u) => $u['spouse'] ? ($cw * 2 + $sg) : $cw, $units);
            $totalUW    = array_sum($unitWidths);
            $availWidth = $canvasWidth - ($lp * 2);

            if ($numUnits <= 1) {
                $gap         = 0;
                $startOffset = max(0, intdiv($availWidth - ($unitWidths[0] ?? 0), 2));
            } else {
                $gap         = max($ug, intdiv($availWidth - $totalUW, $numUnits - 1));
                $rowTW       = $totalUW + $gap * ($numUnits - 1);
                $startOffset = max(0, intdiv($availWidth - $rowTW, 2));
            }

            $curX = $lp + $startOffset;

            foreach ($units as $uIndex => $unit) {
                $primary = $unit['primary'];
                $person  = $peopleById[$primary['person_id']];
                $x       = $curX;
                $key     = "{$rowIndex}:{$person->id}:{$primary['role']}";

                $nodes[]                      = $this->makeNode($person, $x, $y, $person->id === $focus->id, $primary['role'], $ownedTreeIds);
                $nodeMap[$key]                = ['x' => $x, 'y' => $y, 'person_id' => $person->id];
                $primaryByPerson[$person->id] = $key;
                $allXByPerson[$person->id]    = $x;

                if ($unit['spouse']) {
                    $sp       = $unit['spouse'];
                    $spPerson = $peopleById[$sp['person_id']];
                    $spX      = $x + $cw + $sg;
                    $spKey    = "{$rowIndex}:{$spPerson->id}:spouse";

                    $nodes[]                      = $this->makeNode($spPerson, $spX, $y, false, 'spouse', $ownedTreeIds);
                    $nodeMap[$spKey]               = ['x' => $spX, 'y' => $y, 'person_id' => $spPerson->id];
                    $allXByPerson[$spPerson->id]   = $spX;
                    $spouseByPerson[$spPerson->id] = $spKey;

                    $curX += $cw + $sg;
                }

                $curX += $cw + ($numUnits > 1 ? $gap : 0);
            }
        }

        // Parent–child lines
        $lines        = [];
        $childParents = [];
        foreach ($familyEdges as $edge) {
            $childParents[$edge['to']][] = $edge['from'];
        }

        foreach ($childParents as $childId => $parentIds) {
            $childKey = $primaryByPerson[$childId] ?? $spouseByPerson[$childId] ?? null;
            if ($childKey === null) {
                continue;
            }

            $childNode  = $nodeMap[$childKey];
            $childTopX  = $childNode['x'] + intdiv($cw, 2);
            $childTopY  = $childNode['y'];
            $parentIds  = array_values(array_unique($parentIds));

            if (count($parentIds) === 2) {
                [$p1, $p2] = $parentIds;
                $coupled   = in_array($p2, $spousesMap[$p1] ?? [], true)
                          || in_array($p1, $spousesMap[$p2] ?? [], true);

                if ($coupled && isset($allXByPerson[$p1], $allXByPerson[$p2])) {
                    $anchorId  = isset($primaryByPerson[$p1]) ? $p1 : $p2;
                    $anchorKey = $primaryByPerson[$anchorId] ?? $spouseByPerson[$anchorId] ?? null;

                    if ($anchorKey !== null) {
                        $anchorNode = $nodeMap[$anchorKey];
                        $p1cx       = $allXByPerson[$p1] + intdiv($cw, 2);
                        $p2cx       = $allXByPerson[$p2] + intdiv($cw, 2);
                        $midX       = intdiv($p1cx + $p2cx, 2);
                        $barY       = $anchorNode['y'] + self::CARD_HEIGHT;

                        if ($p1cx !== $p2cx) {
                            $lines[] = [
                                'type'   => 'family',
                                'path'   => 'M ' . min($p1cx, $p2cx) . ' ' . $barY . ' H ' . max($p1cx, $p2cx),
                                'stroke' => '#5a8aaa',
                            ];
                        }

                        $lines[] = $this->orthogonal($midX, $barY, $childTopX, $childTopY);
                        continue;
                    }
                }
            }

            foreach ($parentIds as $parentId) {
                $parentKey = $primaryByPerson[$parentId] ?? $spouseByPerson[$parentId] ?? null;
                if ($parentKey === null) {
                    continue;
                }
                $pNode   = $nodeMap[$parentKey];
                $lines[] = $this->orthogonal(
                    $pNode['x'] + intdiv($cw, 2),
                    $pNode['y'] + self::CARD_HEIGHT,
                    $childTopX,
                    $childTopY,
                );
            }
        }

        // Spouse lines
        $drawnPairs = [];
        foreach ($primaryByPerson as $personId => $primaryKey) {
            $fromNode = $nodeMap[$primaryKey];
            foreach ($spousesMap[$personId] ?? [] as $spouseId) {
                $spouseKey = $primaryByPerson[$spouseId] ?? $spouseByPerson[$spouseId] ?? null;
                if ($spouseKey === null) {
                    continue;
                }
                $toNode = $nodeMap[$spouseKey];
                if ($toNode['y'] !== $fromNode['y']) {
                    continue;
                }
                $pairKey = min($personId, $spouseId) . ':' . max($personId, $spouseId);
                if (isset($drawnPairs[$pairKey])) {
                    continue;
                }
                $drawnPairs[$pairKey] = true;

                $lineY  = $fromNode['y'] + 42;
                $leftX  = min($fromNode['x'] + $cw, $toNode['x'] + $cw);
                $rightX = max($fromNode['x'], $toNode['x']);
                if ($rightX <= $leftX) {
                    continue;
                }
                $lines[] = [
                    'type'   => 'spouse',
                    'path'   => 'M ' . $leftX . ' ' . $lineY . ' H ' . $rightX,
                    'stroke' => '#b88a8a',
                ];
            }
        }

        $height = max(760, ($tp * 2) + (count($rowOrder) * $rs) + 60);

        return [$nodes, $lines, ['width' => $canvasWidth, 'height' => $height]];
    }

    /** @return array<string, mixed> */
    private function makeNode(Person $person, int $x, int $y, bool $isFocus, string $role, array $ownedTreeIds = []): array
    {
        $data = $this->privacy->buildDisplayData($person, $ownedTreeIds);

        return [
            'id'          => $person->id,
            'name'        => $data['display_name'],
            'life_span'   => $data['is_private'] ? '' : ($data['life_span'] ?? ''),
            'birth_place' => $data['is_private'] ? '' : ($data['birth_place'] ?? ''),
            'sex'         => $data['sex'],
            'is_private'  => $data['is_private'],
            'is_focus'    => $isFocus,
            'role'        => $role,
            'x'           => $x,
            'y'           => $y,
        ];
    }

    /**
     * @param  list<int>  $ownedTreeIds
     * @return array<string, mixed>
     */
    public function buildSidebarData(Person $person, array $ownedTreeIds = []): array
    {
        $person->loadMissing('familyTree:id,name');

        $allowedIds = Person::where('family_tree_id', $person->family_tree_id)
            ->where('exclude_from_global_tree', false)
            ->pluck('id')
            ->all();

        $relationships = PersonRelationship::where('family_tree_id', $person->family_tree_id)
            ->where(function ($q) use ($person): void {
                $q->where('person_id', $person->id)->orWhere('related_person_id', $person->id);
            })
            ->get();

        $parentIds = [];
        $childIds  = [];
        $spouseIds = [];

        foreach ($relationships as $rel) {
            $other = $rel->person_id === $person->id ? $rel->related_person_id : $rel->person_id;
            if (! in_array($other, $allowedIds, true)) {
                continue;
            }

            if ($rel->type === 'spouse') {
                $spouseIds[] = $other;
            } elseif ($rel->type === 'parent') {
                if ($rel->person_id === $person->id) {
                    $childIds[] = $rel->related_person_id;
                } else {
                    $parentIds[] = $rel->person_id;
                }
            } elseif ($rel->type === 'child') {
                if ($rel->person_id === $person->id) {
                    $parentIds[] = $rel->related_person_id;
                } else {
                    $childIds[] = $rel->person_id;
                }
            }
        }

        $familyIds    = array_unique(array_merge($parentIds, $childIds, $spouseIds));
        $loadedFamily = Person::whereIn('id', $familyIds)->get()->keyBy('id');

        $toMember = fn (int $id): array => [
            'id'   => $id,
            'data' => $this->privacy->buildDisplayData($loadedFamily[$id], $ownedTreeIds),
        ];

        return [
            'person'       => $person,
            'display_data' => $this->privacy->buildDisplayData($person, $ownedTreeIds),
            'parents'      => array_map($toMember, array_values(array_unique($parentIds))),
            'spouses'      => array_map($toMember, array_values(array_unique($spouseIds))),
            'children'     => array_map($toMember, array_values(array_unique($childIds))),
            'tree_name'    => $person->familyTree?->name ?? '',
        ];
    }

    /** @return array<string, string> */
    private function orthogonal(int $x1, int $y1, int $x2, int $y2): array
    {
        $midY = intdiv($y1 + $y2, 2);

        return [
            'type'   => 'family',
            'path'   => "M {$x1} {$y1} V {$midY} H {$x2} V {$y2}",
            'stroke' => '#5a8aaa',
        ];
    }
}
