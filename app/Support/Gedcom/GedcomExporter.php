<?php

namespace App\Support\Gedcom;

use App\Models\FamilyTree;
use App\Models\MediaItem;
use App\Models\Person;
use App\Models\PersonSourceCitation;
use App\Models\PersonRelationship;
use App\Models\Source;
use Illuminate\Support\Collection;

class GedcomExporter
{
    public function export(FamilyTree $tree): string
    {
        $people = $tree->people()->orderBy('id')->get()->keyBy('id');
        $relationships = $tree->relationships()->orderBy('id')->get();
        $mediaItems = $tree->mediaItems()->orderBy('id')->get()->groupBy('person_id');
        $sources = $tree->sources()->orderBy('id')->get()->keyBy('id');
        $citations = \App\Models\PersonSourceCitation::query()
            ->whereIn('person_id', $people->keys())
            ->with('source')
            ->orderBy('id')
            ->get()
            ->groupBy('person_id');
        $familyMap = $this->buildFamilies($people, $relationships);

        $lines = [
            '0 HEAD',
            '1 GEDC',
            '2 VERS 7.0',
            '2 FORM LINEAGE-LINKED',
            '1 CHAR UTF-8',
            '1 SOUR Kinfolk Atlas',
            '2 VERS 1.0',
            '1 DEST ANY',
            '1 DATE '.now()->format('d M Y'),
        ];

        foreach ($people as $person) {
            $xref = $this->personXref($person);
            $lines[] = "0 {$xref} INDI";
            $lines[] = '1 NAME '.$this->formatName($person);
            $lines[] = '1 SEX '.$this->mapSex($person->sex);

            $this->appendEvent($lines, 'BIRT', GedcomDate::forExport($person->birth_date, $person->birth_date_text), $person->birth_place);
            $this->appendEvent($lines, 'DEAT', GedcomDate::forExport($person->death_date, $person->death_date_text), $person->death_place);

            if ($person->headline) {
                $lines[] = '1 TITL '.$this->sanitize($person->headline);
            }

            if ($person->notes) {
                $this->appendMultiline($lines, 1, 'NOTE', $person->notes);
            }

            foreach ($mediaItems[$person->id] ?? [] as $mediaItem) {
                $lines[] = '1 OBJE @O'.$mediaItem->id.'@';
            }

            foreach ($citations[$person->id] ?? [] as $citation) {
                if (! $citation->source) {
                    continue;
                }

                $lines[] = '1 SOUR @S'.$citation->source->id.'@';

                if ($citation->page) {
                    $lines[] = '2 PAGE '.$this->sanitize($citation->page);
                }

                if ($citation->quality !== null) {
                    $lines[] = '2 QUAY '.$citation->quality;
                }

                if ($citation->quotation) {
                    $this->appendMultiline($lines, 2, 'NOTE', $citation->quotation);
                }

                if ($citation->note) {
                    $this->appendMultiline($lines, 2, 'NOTE', $citation->note);
                }
            }

            foreach ($familyMap as $familyId => $family) {
                if (($family['husb'] ?? null) === $person->id || ($family['wife'] ?? null) === $person->id) {
                    $lines[] = '1 FAMS @F'.$familyId.'@';
                }

                if (in_array($person->id, $family['chil'], true)) {
                    $lines[] = '1 FAMC @F'.$familyId.'@';
                    $relations = $family['child_relations'][$person->id] ?? [];
                    $pedi = $this->sharedGedcomPedi($relations['husb'] ?? null, $relations['wife'] ?? null);

                    if ($pedi !== null) {
                        $lines[] = '2 PEDI '.$pedi;
                    }
                }
            }
        }

        foreach ($sources as $source) {
            $lines[] = '0 @S'.$source->id.'@ SOUR';
            $lines[] = '1 TITL '.$this->sanitize($source->title);

            if ($source->author) {
                $lines[] = '1 AUTH '.$this->sanitize($source->author);
            }

            if ($source->publication_facts) {
                $lines[] = '1 PUBL '.$this->sanitize($source->publication_facts);
            }

            if ($source->text) {
                $this->appendMultiline($lines, 1, 'TEXT', $source->text);
            }

            if ($source->quality !== null) {
                $lines[] = '1 QUAY '.$source->quality;
            }

            if ($source->repository) {
                $this->appendMultiline($lines, 1, 'NOTE', 'Repository: '.$source->repository);
            }

            if ($source->call_number) {
                $this->appendMultiline($lines, 1, 'NOTE', 'Call number: '.$source->call_number);
            }

            if ($source->url) {
                $this->appendMultiline($lines, 1, 'NOTE', 'URL: '.$source->url);
            }
        }

        foreach ($tree->mediaItems()->orderBy('id')->get() as $mediaItem) {
            $lines[] = '0 @O'.$mediaItem->id.'@ OBJE';
            $lines[] = '1 FILE '.$this->sanitize($mediaItem->external_reference ?: $mediaItem->file_name);

            if ($mediaItem->mime_type) {
                $lines[] = '2 FORM '.$this->sanitize($mediaItem->mime_type);
            }

            $lines[] = '1 TITL '.$this->sanitize($mediaItem->title);

            if ($mediaItem->description) {
                $this->appendMultiline($lines, 1, 'NOTE', $mediaItem->description);
            }
        }

        foreach ($familyMap as $familyId => $family) {
            $lines[] = "0 @F{$familyId}@ FAM";

            if (isset($family['husb'])) {
                $lines[] = '1 HUSB '.$this->personXref($people[$family['husb']]);
            }

            if (isset($family['wife'])) {
                $lines[] = '1 WIFE '.$this->personXref($people[$family['wife']]);
            }

            foreach ($family['chil'] as $childId) {
                if (isset($people[$childId])) {
                    $lines[] = '1 CHIL '.$this->personXref($people[$childId]);
                    $relations = $family['child_relations'][$childId] ?? [];

                    if (($frel = $this->gedcomParentRelationValue($relations['husb'] ?? null)) !== null) {
                        $lines[] = '2 _FREL '.$frel;
                    }

                    if (($mrel = $this->gedcomParentRelationValue($relations['wife'] ?? null)) !== null) {
                        $lines[] = '2 _MREL '.$mrel;
                    }
                }
            }
        }

        $lines[] = '0 TRLR';

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  Collection<int, Person>  $people
     * @param  Collection<int, PersonRelationship>  $relationships
     * @return array<int, array{
     *     husb?: int,
     *     wife?: int,
     *     chil: list<int>,
     *     child_relations: array<int, array{husb?: ?string, wife?: ?string}>
     * }>
     */
    private function buildFamilies(Collection $people, Collection $relationships): array
    {
        $spouses = [];
        $childrenByParent = [];
        $childSubtypeByParent = [];

        foreach ($relationships as $relationship) {
            if ($relationship->type === 'spouse') {
                $key = $this->pairKey($relationship->person_id, $relationship->related_person_id);
                $spouses[$key] = [$relationship->person_id, $relationship->related_person_id];
            }

            if ($relationship->type === 'parent') {
                $childrenByParent[$relationship->person_id] ??= [];
                $childSubtypeByParent[$relationship->person_id] ??= [];

                if (! in_array($relationship->related_person_id, $childrenByParent[$relationship->person_id], true)) {
                    $childrenByParent[$relationship->person_id][] = $relationship->related_person_id;
                }

                $childSubtypeByParent[$relationship->person_id][$relationship->related_person_id] = $this->normalizeParentSubtype($relationship->subtype);
            }
        }

        $families = [];
        $familyId = 1;
        $usedParents = [];

        foreach ($spouses as [$left, $right]) {
            $family = ['chil' => [], 'child_relations' => []];
            [$family['husb'], $family['wife']] = $this->assignPartners($people, $left, $right);

            $children = array_values(array_unique(array_merge($childrenByParent[$left] ?? [], $childrenByParent[$right] ?? [])));
            sort($children);
            $family['chil'] = $children;

            foreach ($children as $childId) {
                $family['child_relations'][$childId] = [
                    'husb' => isset($family['husb']) ? ($childSubtypeByParent[$family['husb']][$childId] ?? null) : null,
                    'wife' => isset($family['wife']) ? ($childSubtypeByParent[$family['wife']][$childId] ?? null) : null,
                ];
            }

            $families[$familyId++] = $family;
            $usedParents[$left] = true;
            $usedParents[$right] = true;
        }

        foreach ($childrenByParent as $parentId => $children) {
            if (isset($usedParents[$parentId])) {
                continue;
            }

            $family = ['chil' => array_values(array_unique($children)), 'child_relations' => []];

            if (($people[$parentId]->sex ?? 'unknown') === 'female') {
                $family['wife'] = $parentId;
            } else {
                $family['husb'] = $parentId;
            }

            foreach ($family['chil'] as $childId) {
                $family['child_relations'][$childId] = [
                    'husb' => isset($family['husb']) ? ($childSubtypeByParent[$family['husb']][$childId] ?? null) : null,
                    'wife' => isset($family['wife']) ? ($childSubtypeByParent[$family['wife']][$childId] ?? null) : null,
                ];
            }

            $families[$familyId++] = $family;
        }

        return $families;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function assignPartners(Collection $people, string $left, string $right): array
    {
        if (($people[$left]->sex ?? 'unknown') === 'female' && ($people[$right]->sex ?? 'unknown') !== 'female') {
            return [$right, $left];
        }

        return [$left, $right];
    }

    private function personXref(Person $person): string
    {
        return '@I'.$person->id.'@';
    }

    private function formatName(Person $person): string
    {
        $parts = array_values(array_filter([$person->given_name, $person->middle_name]));
        $given = trim(implode(' ', $parts));
        $surname = trim($person->surname);

        return trim($this->sanitize($given).' /'.$this->sanitize($surname).'/');
    }

    private function mapSex(string $sex): string
    {
        return match ($sex) {
            'male' => 'M',
            'female' => 'F',
            default => 'U',
        };
    }

    /**
     * @param  list<string>  $lines
     */
    private function appendEvent(array &$lines, string $tag, ?string $date, ?string $place): void
    {
        if (! $date && ! $place) {
            return;
        }

        $lines[] = '1 '.$tag;

        if ($date) {
            $lines[] = '2 DATE '.$this->sanitize($date);
        }

        if ($place) {
            $lines[] = '2 PLAC '.$this->sanitize($place);
        }
    }

    /**
     * @param  list<string>  $lines
     */
    private function appendMultiline(array &$lines, int $level, string $tag, string $value): void
    {
        $segments = preg_split("/\r\n|\n|\r/", trim($value)) ?: [];

        if ($segments === []) {
            return;
        }

        $first = array_shift($segments);
        $lines[] = $level.' '.$tag.' '.$this->sanitize($first);

        foreach ($segments as $segment) {
            $lines[] = ($level + 1).' CONT '.$this->sanitize($segment);
        }
    }

    private function sanitize(string $value): string
    {
        return str_replace(["\r", "\n"], ' ', trim($value));
    }

    private function pairKey(string $left, string $right): string
    {
        $ids = [$left, $right];
        sort($ids);

        return implode(':', $ids);
    }

    private function normalizeParentSubtype(?string $subtype): ?string
    {
        $normalized = strtolower(trim((string) $subtype));

        return match ($normalized) {
            '', 'birth', 'biological' => null,
            'adopted', 'adoptive' => 'adoptive',
            'foster' => 'foster',
            'guardian', 'guardianship' => 'guardian',
            'step', 'stepchild', 'step-parent', 'step parent' => 'step',
            'sealing', 'sealed' => 'sealing',
            default => $normalized !== '' ? $normalized : null,
        };
    }

    private function sharedGedcomPedi(?string $fatherSubtype, ?string $motherSubtype): ?string
    {
        if ($fatherSubtype === null || $motherSubtype === null || $fatherSubtype !== $motherSubtype) {
            return null;
        }

        return match ($fatherSubtype) {
            'adoptive' => 'ADOPTED',
            'foster', 'guardian' => 'FOSTER',
            'sealing' => 'SEALING',
            default => null,
        };
    }

    private function gedcomParentRelationValue(?string $subtype): ?string
    {
        return match ($this->normalizeParentSubtype($subtype)) {
            null => null,
            'adoptive' => 'adopted',
            'foster' => 'foster',
            'guardian' => 'guardian',
            'step' => 'step',
            'sealing' => 'sealing',
            default => 'other',
        };
    }
}
