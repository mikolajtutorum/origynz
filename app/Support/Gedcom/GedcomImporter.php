<?php

namespace App\Support\Gedcom;

use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\PersonRelationship;
use App\Models\User;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Facades\Activity;

class GedcomImporter
{
    /**
     * @return array{people_created:int, relationships_created:int, first_person_id:int|null, owner_selection_required:bool}
     */
    public function import(FamilyTree $tree, UploadedFile $file, User $user, ?Closure $onProgress = null): array
    {
        $onProgress && $onProgress(3, 'Reading file...');
        $content = $this->normalizeContent((string) $file->get());

        return $this->doImport($tree, $content, $user, $onProgress);
    }

    /**
     * @return array{people_created:int, relationships_created:int, first_person_id:int|null, owner_selection_required:bool}
     */
    public function importFromPath(FamilyTree $tree, string $filePath, User $user, ?Closure $onProgress = null): array
    {
        $onProgress && $onProgress(3, 'Reading file...');
        $content = $this->normalizeContent((string) file_get_contents($filePath));

        return $this->doImport($tree, $content, $user, $onProgress);
    }

    /**
     * @return array{people_created:int, relationships_created:int, first_person_id:int|null, owner_selection_required:bool}
     */
    private function doImport(FamilyTree $tree, string $content, User $user, ?Closure $onProgress): array
    {
        $onProgress && $onProgress(10, 'Parsing GEDCOM records...');
        $records = $this->parse($content);
        $onProgress && $onProgress(15, 'GEDCOM parsed successfully.');

        $userId = $user->id;
        $totalIndi = count($records['INDI']);

        return Activity::withoutLogs(function () use ($tree, $records, $user, $userId, $onProgress, $totalIndi) {
            return DB::transaction(function () use ($tree, $records, $user, $userId, $onProgress, $totalIndi) {
                $personMap = [];
                $sourceMap = [];
                $mediaMap = [];
                $peopleCreated = 0;
                $relationshipsCreated = 0;
                $firstImportedPersonId = null;
                $ownerSelectionRequired = false;
                $placeholderOwner = $this->placeholderOwnerPerson($tree, $userId);

                $this->applyHeadMetadata($tree, $records['HEAD'] ?? []);
                $onProgress && $onProgress(18, 'Applied tree metadata.');

            foreach ($records['SOUR'] as $xref => $record) {
                $sourceMap[$xref] = $tree->sources()->create([
                    'created_by' => $userId,
                    'title' => $record['TITL'] ?? 'Untitled source',
                    'author' => $record['AUTH'] ?? null,
                    'publication_facts' => $record['PUBL'] ?? null,
                    'text' => $record['TEXT'] ?? null,
                    'quality' => isset($record['QUAY']) ? (int) $record['QUAY'] : null,
                    'gedcom_rin' => $record['RIN'] ?? null,
                    'gedcom_updated_at_text' => $record['_UPD'] ?? null,
                    'source_type' => $record['_TYPE'] ?? null,
                    'source_medium' => $record['_MEDI'] ?? null,
                ]);
            }

            $onProgress && $onProgress(22, 'Created '.count($sourceMap).' sources.');

            foreach ($records['OBJE'] as $xref => $record) {
                $mediaMap[$xref] = $this->createMediaItem($tree, $userId, $record);
            }

            $onProgress && $onProgress(27, 'Created '.count($mediaMap).' media items.');

            $indiIndex = 0;
            $progressStep = max(1, (int) ($totalIndi / 100));

            foreach ($records['INDI'] as $xref => $record) {
                $sex = $this->mapSex($record['SEX'] ?? null);
                $name = $this->resolveImportedName($record, $sex);
                $birth = $this->primaryEventDate($record['EVENTS'] ?? [], 'BIRT');
                $death = $this->primaryEventDate($record['EVENTS'] ?? [], 'DEAT');
                $birthPlace = $this->primaryEventField($record['EVENTS'] ?? [], 'BIRT', 'PLAC');
                $deathPlace = $this->primaryEventField($record['EVENTS'] ?? [], 'DEAT', 'PLAC');

                $causeOfDeath = $this->primaryEventField($record['EVENTS'] ?? [], 'DEAT', 'CAUS');
                $burialPlace  = $this->primaryEventField($record['EVENTS'] ?? [], 'BURI', 'PLAC');

                $rname = trim((string) ($record['_RNAME'] ?? ''));
                $aka   = trim((string) ($record['_AKA']   ?? ''));
                $formerName = trim((string) ($record['_FORMERNAME'] ?? ''));

                $person = $tree->people()->create([
                    'created_by' => $userId,
                    'given_name' => $name['given_name'],
                    'middle_name' => $name['middle_name'],
                    'alternative_name' => $rname !== '' ? $rname : ($aka !== '' ? $aka : null),
                    'surname' => $name['surname'],
                    'birth_surname' => $name['birth_surname'] ?? ($formerName !== '' ? $formerName : null),
                    'prefix' => ($record['NPFX'] ?? '') !== '' ? $record['NPFX'] : null,
                    'suffix' => ($record['NSFX'] ?? '') !== '' ? $record['NSFX'] : null,
                    'nickname' => ($record['NICK'] ?? '') !== '' ? $record['NICK'] : null,
                    'sex' => $sex,
                    'birth_date' => $birth['date'],
                    'birth_date_text' => $birth['text'],
                    'death_date' => $death['date'],
                    'death_date_text' => $death['text'],
                    'birth_place' => $birthPlace,
                    'death_place' => $deathPlace,
                    'cause_of_death' => $causeOfDeath,
                    'burial_place' => $burialPlace,
                    'is_living' => ! $this->hasDeathEvent($record['EVENTS'] ?? []),
                    'headline' => $record['TITL'] ?? null,
                    'notes' => $record['NOTE'] ?? null,
                    'physical_description' => ($record['DSCR'] ?? '') !== '' ? $record['DSCR'] : null,
                    'gedcom_rin' => $record['RIN'] ?? null,
                    'gedcom_uid' => $record['_UID'] ?? null,
                    'gedcom_updated_at_text' => $record['_UPD'] ?? null,
                ]);

                $peopleCreated++;
                $personMap[$xref] = $person;
                $firstImportedPersonId ??= $person->id;

                $this->createImportedEvents($person, $record['EVENTS'] ?? [], $userId);

                foreach ($record['INLINE_OBJE'] ?? [] as $inlineMedia) {
                    $this->createMediaItem($tree, $userId, $inlineMedia, $person->id);
                }

                $indiIndex++;

                if ($onProgress && $totalIndi > 0 && ($indiIndex % $progressStep === 0 || $indiIndex === $totalIndi)) {
                    $pct = 27 + (int) ($indiIndex / $totalIndi * 48);
                    $onProgress($pct, "Creating persons: {$indiIndex} of {$totalIndi}");
                }
            }

            $onProgress && $onProgress(76, 'Linking source citations...');

            foreach ($records['INDI'] as $xref => $record) {
                $person = $personMap[$xref] ?? null;

                if (! $person) {
                    continue;
                }

                foreach ($record['OBJE_LINKS'] ?? [] as $mediaXref) {
                    $mediaItem = $mediaMap[$mediaXref] ?? null;

                    if ($mediaItem && ! $mediaItem->person_id) {
                        $mediaItem->update(['person_id' => $person->id]);
                    }
                }

                foreach ($record['SOUR_CIT'] ?? [] as $citation) {
                    $source = $sourceMap[$citation['xref']] ?? null;

                    if (! $source) {
                        continue;
                    }

                    $person->sourceCitations()->create([
                        'source_id' => $source->id,
                        'page' => $citation['PAGE'] ?? null,
                        'quotation' => $citation['NOTE'] ?? null,
                        'quality' => isset($citation['QUAY']) ? (int) $citation['QUAY'] : null,
                        'event_name' => $citation['EVEN'] ?? null,
                        'role' => $citation['ROLE'] ?? null,
                        'entry_date_text' => $citation['DATA']['DATE'] ?? null,
                        'entry_text' => $citation['DATA']['TEXT'] ?? null,
                    ]);
                }
            }

            $onProgress && $onProgress(85, 'Building family relationships...');

            foreach ($records['FAM'] as $familyXref => $record) {
                $parents = array_values(array_filter([
                    $record['HUSB'] ?? null,
                    $record['WIFE'] ?? null,
                ]));
                $children = $record['CHIL'] ?? [];
                $childLinks = collect($record['CHIL_LINKS'] ?? [])->keyBy('xref');

                if (count($parents) === 2) {
                    $left = $personMap[$parents[0]] ?? null;
                    $right = $personMap[$parents[1]] ?? null;

                    if ($left && $right) {
                        $relationship = $tree->relationships()->firstOrCreate(
                            [
                                'person_id' => $left->id,
                                'related_person_id' => $right->id,
                                'type' => 'spouse',
                            ],
                            $this->spouseRelationshipAttributes($record)
                        );

                        if ($relationship->wasRecentlyCreated) {
                            $relationshipsCreated++;
                        } else {
                            $this->fillRelationshipMetadata($relationship, $this->spouseRelationshipAttributes($record));
                        }
                    }
                }

                foreach ($parents as $parentXref) {
                    $parent = $personMap[$parentXref] ?? null;

                    if (! $parent) {
                        continue;
                    }

                    foreach ($children as $childXref) {
                        $child = $personMap[$childXref] ?? null;

                        if (! $child) {
                            continue;
                        }

                        $attributes = [
                            'person_id' => $parent->id,
                            'related_person_id' => $child->id,
                            'type' => 'parent',
                        ];
                        $relationshipSubtype = $this->parentRelationshipSubtypeForImport(
                            $record,
                            is_array($childLinks->get($childXref)) ? $childLinks->get($childXref) : [],
                            $records['INDI'][$childXref] ?? [],
                            $parentXref,
                            $familyXref
                        );
                        $values = array_filter([
                            'subtype' => $relationshipSubtype,
                        ], fn ($value) => $value !== null && $value !== '');

                        $created = $tree->relationships()->firstOrCreate($attributes, $values);

                        if ($created->wasRecentlyCreated) {
                            $relationshipsCreated++;
                        } elseif (($created->subtype === null || $created->subtype === '') && ($values['subtype'] ?? null) !== null) {
                            $created->update(['subtype' => $values['subtype']]);
                        }
                    }
                }
            }

            $onProgress && $onProgress(96, 'Matching owner person...');

            $matchedImportedOwner = $this->resolveImportedOwnerMatch($personMap, $user);

            if ($placeholderOwner && $matchedImportedOwner) {
                $tree->update(['owner_person_id' => $matchedImportedOwner->id]);
                $placeholderOwner->delete();
            } elseif (! $tree->owner_person_id && $personMap !== []) {
                $selectedOwner = $matchedImportedOwner;

                if (! $selectedOwner) {
                    /** @var Person $selectedOwner */
                    $selectedOwner = reset($personMap);
                    $ownerSelectionRequired = count($personMap) > 1;
                }

                $tree->update(['owner_person_id' => $selectedOwner->id]);
            } elseif ($placeholderOwner && $personMap !== []) {
                $ownerSelectionRequired = true;
            }

                return [
                    'people_created' => $peopleCreated,
                    'relationships_created' => $relationshipsCreated,
                    'first_person_id' => $firstImportedPersonId,
                    'owner_selection_required' => $ownerSelectionRequired,
                ];
            });
        });
    }

    /**
     * @return array{HEAD: array<string, mixed>, INDI: array<string, array<string, mixed>>, FAM: array<string, array<string, mixed>>, SOUR: array<string, array<string, mixed>>, OBJE: array<string, array<string, mixed>>}
     */
    private function parse(string $content): array
    {
        $records = ['HEAD' => [], 'INDI' => [], 'FAM' => [], 'SOUR' => [], 'OBJE' => []];
        $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];
        $currentType = null;
        $currentXref = null;
        $currentEventIndex = null;
        $currentCitationIndex = null;
        $currentInlineObjectIndex = null;
        $currentFamilyLinkIndex = null;
        $currentFamilyLinkTag = null;
        $currentChildLinkIndex = null;
        $currentTextContext = null;
        $currentAddressContext = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (! preg_match('/^(\d+)\s+(?:(@[^@]+@)\s+)?([A-Z0-9_]+)(?:\s+(.*))?$/', $line, $matches)) {
                continue;
            }

            $level = (int) $matches[1];
            $xref = $matches[2] !== '' ? $matches[2] : null;
            $tag = $matches[3];
            $value = $matches[4] ?? null;

            if ($level === 0) {
                $currentEventIndex = null;
                $currentCitationIndex = null;
                $currentInlineObjectIndex = null;
                $currentFamilyLinkIndex = null;
                $currentFamilyLinkTag = null;
                $currentChildLinkIndex = null;
                $currentTextContext = null;
                $currentAddressContext = null;

                if ($tag === 'HEAD') {
                    $currentType = 'HEAD';
                    $currentXref = 'HEAD';
                    continue;
                }

                if ($xref && in_array($tag, ['INDI', 'FAM', 'SOUR', 'OBJE'], true)) {
                    $currentType = $tag;
                    $currentXref = $xref;
                    $records[$tag][$xref] = [];
                } else {
                    $currentType = null;
                    $currentXref = null;
                }

                continue;
            }

            if (! $currentType || ! $currentXref) {
                continue;
            }

            if ($level === 1) {
                $currentEventIndex = null;
                $currentCitationIndex = null;
                $currentInlineObjectIndex = null;
                $currentFamilyLinkIndex = null;
                $currentFamilyLinkTag = null;
                $currentChildLinkIndex = null;
                $currentTextContext = null;
                $currentAddressContext = null;
            }

            if ($currentType === 'HEAD') {
                $this->parseHeadRecord($records['HEAD'], $level, $tag, $value, $currentTextContext);
                continue;
            }

            if ($level === 1 && $currentType === 'INDI' && in_array($tag, ['FAMC', 'FAMS'], true)) {
                $records[$currentType][$currentXref][$tag] ??= [];
                $records[$currentType][$currentXref][$tag][] = ['xref' => $value];
                $currentFamilyLinkIndex = array_key_last($records[$currentType][$currentXref][$tag]);
                $currentFamilyLinkTag = $tag;
                continue;
            }

            if ($level === 1 && in_array($tag, $this->eventTags(), true) && in_array($currentType, ['INDI', 'FAM'], true)) {
                $records[$currentType][$currentXref]['EVENTS'] ??= [];
                $records[$currentType][$currentXref]['EVENTS'][] = [
                    'TAG' => $tag,
                    'VALUE' => $value ?? '',
                ];
                $currentEventIndex = array_key_last($records[$currentType][$currentXref]['EVENTS']);
                continue;
            }

            if ($level === 1 && $currentType === 'INDI' && $tag === 'OBJE') {
                if ($value !== null && str_starts_with($value, '@')) {
                    $records[$currentType][$currentXref]['OBJE_LINKS'] ??= [];
                    $records[$currentType][$currentXref]['OBJE_LINKS'][] = $value;
                    continue;
                }

                $records[$currentType][$currentXref]['INLINE_OBJE'] ??= [];
                $records[$currentType][$currentXref]['INLINE_OBJE'][] = [];
                $currentInlineObjectIndex = array_key_last($records[$currentType][$currentXref]['INLINE_OBJE']);
                continue;
            }

            if ($level === 1 && $currentType === 'INDI' && $tag === 'SOUR') {
                $records[$currentType][$currentXref]['SOUR_CIT'] ??= [];
                $records[$currentType][$currentXref]['SOUR_CIT'][] = ['xref' => $value];
                $currentCitationIndex = array_key_last($records[$currentType][$currentXref]['SOUR_CIT']);
                continue;
            }

            if ($level === 1 && $tag === 'CHIL') {
                $records[$currentType][$currentXref]['CHIL'] ??= [];
                $records[$currentType][$currentXref]['CHIL'][] = $value;
                $records[$currentType][$currentXref]['CHIL_LINKS'] ??= [];
                $records[$currentType][$currentXref]['CHIL_LINKS'][] = ['xref' => $value];
                $currentChildLinkIndex = array_key_last($records[$currentType][$currentXref]['CHIL_LINKS']);
                continue;
            }

            if ($level === 1 && in_array($tag, ['NAME', 'SEX', 'TITL', 'DSCR', 'NOTE', 'HUSB', 'WIFE', 'AUTH', 'PUBL', 'TEXT', 'FILE', 'RIN', '_UID', '_UPD', '_TYPE', '_MEDI'], true)) {
                $records[$currentType][$currentXref][$tag] = $value ?? '';

                if (in_array($tag, ['NOTE', 'TEXT'], true)) {
                    $currentTextContext = [$currentType, $currentXref, null, null, $tag];
                }

                continue;
            }

            if ($level === 2 && $currentType === 'INDI' && in_array($tag, ['GIVN', 'SURN', '_MARNM', '_RNAME', 'NPFX', 'NSFX', 'NICK', '_AKA', '_FORMERNAME'], true)) {
                $records[$currentType][$currentXref][$tag] = $value ?? '';
                continue;
            }

            if ($level === 2 && $currentType === 'INDI' && $currentFamilyLinkIndex !== null && in_array($tag, ['PEDI', '_PEDI'], true)) {
                $records[$currentType][$currentXref][$currentFamilyLinkTag][$currentFamilyLinkIndex][$tag] = $value ?? '';
                continue;
            }

            if ($level === 2 && $currentType === 'INDI' && $currentCitationIndex !== null && $tag === 'DATA') {
                $records[$currentType][$currentXref]['SOUR_CIT'][$currentCitationIndex]['DATA'] ??= [];
                continue;
            }

            if ($level === 2 && $currentType === 'INDI' && $currentCitationIndex !== null && in_array($tag, ['PAGE', 'QUAY', 'EVEN'], true)) {
                $records[$currentType][$currentXref]['SOUR_CIT'][$currentCitationIndex][$tag] = $value ?? '';
                continue;
            }

            if ($level === 3 && $currentType === 'INDI' && $currentCitationIndex !== null && in_array($tag, ['DATE', 'TEXT', 'ROLE'], true)) {
                if ($tag === 'ROLE') {
                    $records[$currentType][$currentXref]['SOUR_CIT'][$currentCitationIndex][$tag] = $value ?? '';
                } else {
                    $records[$currentType][$currentXref]['SOUR_CIT'][$currentCitationIndex]['DATA'][$tag] = $value ?? '';

                    if ($tag === 'TEXT') {
                        $currentTextContext = [$currentType, $currentXref, 'citation_data', $currentCitationIndex, $tag];
                    }
                }

                continue;
            }

            if ($level === 2 && $currentEventIndex !== null && in_array($tag, ['DATE', 'PLAC', 'NOTE', 'TYPE', 'AGE', 'CAUS', 'EMAIL'], true)) {
                $records[$currentType][$currentXref]['EVENTS'][$currentEventIndex][$tag] = $value ?? '';

                if ($tag === 'NOTE') {
                    $currentTextContext = [$currentType, $currentXref, 'event', $currentEventIndex, $tag];
                }

                continue;
            }

            if ($level === 2 && $currentType === 'FAM' && $currentChildLinkIndex !== null && in_array($tag, ['PEDI', '_PEDI', '_FREL', '_MREL'], true)) {
                $records[$currentType][$currentXref]['CHIL_LINKS'][$currentChildLinkIndex][$tag] = $value ?? '';
                continue;
            }

            if ($level === 2 && $currentEventIndex !== null && $tag === 'ADDR') {
                $records[$currentType][$currentXref]['EVENTS'][$currentEventIndex]['ADDR'] ??= [];
                $currentAddressContext = [$currentType, $currentXref, $currentEventIndex];
                continue;
            }

            if ($level === 3 && $currentAddressContext !== null && in_array($tag, ['ADR1', 'CITY', 'CTRY'], true)) {
                [$addressType, $addressXref, $addressEventIndex] = $currentAddressContext;
                $records[$addressType][$addressXref]['EVENTS'][$addressEventIndex]['ADDR'][$tag] = $value ?? '';
                continue;
            }

            if ($level === 2 && $currentInlineObjectIndex !== null && in_array($tag, ['FORM', 'FILE', 'TITL', 'NOTE', '_FILESIZE', '_PRIM', '_CUTOUT', '_PARENTRIN', '_PERSONALPHOTO', '_PHOTO_RIN', '_PRIM_CUTOUT', '_PARENTPHOTO', '_POSITION'], true)) {
                $records[$currentType][$currentXref]['INLINE_OBJE'][$currentInlineObjectIndex][$tag] = $value ?? '';

                if ($tag === 'NOTE') {
                    $currentTextContext = [$currentType, $currentXref, 'inline_object', $currentInlineObjectIndex, $tag];
                }

                continue;
            }

            if ($level === 2 && $currentType === 'OBJE' && in_array($tag, ['FORM', 'TITL', 'NOTE', '_FILESIZE', '_PRIM', '_CUTOUT', '_PARENTRIN', '_PERSONALPHOTO', '_PHOTO_RIN', '_PRIM_CUTOUT', '_PARENTPHOTO', '_POSITION'], true)) {
                $records[$currentType][$currentXref][$tag] = $value ?? '';

                if ($tag === 'NOTE') {
                    $currentTextContext = [$currentType, $currentXref, null, null, $tag];
                }

                continue;
            }

            if (($tag === 'CONT' || $tag === 'CONC') && $currentTextContext !== null) {
                $this->appendContinuedText($records, $currentTextContext, $value ?? '', $tag === 'CONT');
            }
        }

        return $records;
    }

    /**
     * @return array{given_name:string,middle_name:?string,surname:string}
     */
    private function parseName(string $value): array
    {
        preg_match('/^(.*?)\s*\/(.*?)\//', trim($value), $matches);
        $givenBlock = trim($matches[1] ?? $value);
        $surname = trim($matches[2] ?? '');
        $givenParts = array_values(array_filter(explode(' ', $givenBlock)));

        return [
            'given_name' => array_shift($givenParts) ?: 'Unknown',
            'middle_name' => $givenParts !== [] ? implode(' ', $givenParts) : null,
            'surname' => $surname !== '' ? $surname : 'Unknown',
        ];
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array{given_name:string,middle_name:?string,surname:string,birth_surname:?string}
     */
    private function resolveImportedName(array $record, string $sex): array
    {
        $parsed = $this->parseName((string) ($record['NAME'] ?? ''));
        $givenBlock = trim((string) ($record['GIVN'] ?? ''));
        $givenParts = $givenBlock !== '' ? array_values(array_filter(explode(' ', $givenBlock))) : [];
        $baseSurname = trim((string) ($record['SURN'] ?? $parsed['surname']));
        $marriedSurname = trim((string) ($record['_MARNM'] ?? ''));

        $givenName = $givenParts !== [] ? (array_shift($givenParts) ?: 'Unknown') : $parsed['given_name'];
        $middleName = $givenBlock !== '' ? ($givenParts !== [] ? implode(' ', $givenParts) : null) : $parsed['middle_name'];
        $surname = $baseSurname !== '' ? $baseSurname : $parsed['surname'];
        $birthSurname = null;

        if ($sex === 'female' && $marriedSurname !== '') {
            $birthSurname = $surname !== 'Unknown' ? $surname : null;
            $surname = $marriedSurname;
        }

        return [
            'given_name' => $givenName,
            'middle_name' => $middleName,
            'surname' => $surname !== '' ? $surname : 'Unknown',
            'birth_surname' => $birthSurname,
        ];
    }

    private function mapSex(?string $value): string
    {
        return match (strtoupper(trim((string) $value))) {
            'M' => 'male',
            'F' => 'female',
            default => 'unknown',
        };
    }

    private function normalizeContent(string $content): string
    {
        $content = $this->stripUtf8Bom($content);

        if ($content === '' || mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        $normalizedLines = [];

        foreach (preg_split("/\r\n|\n|\r/", $content) ?: [] as $line) {
            $normalizedLines[] = $this->normalizeLine($line, $content);
        }

        return implode("\n", $normalizedLines);
    }

    private function extractDeclaredCharset(string $content): ?string
    {
        if (! preg_match('/(?:^|\R)1 CHAR ([^\r\n]+)/', $content, $matches)) {
            return null;
        }

        return strtoupper(trim($matches[1]));
    }

    private function mapGedcomCharset(?string $charset): ?string
    {
        return match ($charset) {
            'ASCII' => 'ASCII',
            'ANSI' => 'CP1252',
            'ANSEL' => 'CP1252',
            'UTF-8', 'UNICODE' => 'UTF-8',
            default => null,
        };
    }

    private function stripUtf8Bom(string $content): string
    {
        return str_starts_with($content, "\xEF\xBB\xBF") ? substr($content, 3) : $content;
    }

    private function normalizeLine(string $line, string $fullContent): string
    {
        $line = $this->stripUtf8Bom($line);

        if ($line === '' || mb_check_encoding($line, 'UTF-8')) {
            return $line;
        }

        $declaredCharset = $this->mapGedcomCharset($this->extractDeclaredCharset($fullContent));
        $candidates = array_values(array_unique(array_filter([
            $declaredCharset !== 'UTF-8' ? $declaredCharset : null,
            'CP1250',
            'ISO-8859-2',
            'CP852',
            'CP1252',
        ])));

        foreach ($candidates as $encoding) {
            $converted = @iconv($encoding, 'UTF-8//IGNORE', $line);

            if ($converted !== false && $converted !== '' && mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        return $line;
    }

    public function shouldPromptForOwnerSelection(FamilyTree $tree, string $userId): bool
    {
        return $this->placeholderOwnerPerson($tree, $userId) !== null;
    }

    /**
     * @param  array<string, Person>  $personMap
     */
    private function resolveImportedOwnerMatch(array $personMap, User $user): ?Person
    {
        if ($personMap === []) {
            return null;
        }

        $scores = collect($personMap)
            ->map(fn (Person $person) => [
                'person' => $person,
                'score' => $this->ownerCandidateScore($person, $user),
            ])
            ->sortByDesc('score')
            ->values();

        $best = $scores->first();

        if (! $best) {
            return null;
        }

        $runnerUpScore = $scores->get(1)['score'] ?? null;

        if (! $this->isConfidentOwnerMatch($best['person'], $best['score'], $runnerUpScore, $user)) {
            return null;
        }

        return $best['person'];
    }

    private function ownerCandidateScore(Person $person, User $user): int
    {
        $score = $person->is_living ? 25 : 0;

        $nameParts = collect([
            $user->first_name,
            $user->middle_name,
            $user->last_name,
            $user->name,
        ])
            ->filter()
            ->flatMap(fn (string $value) => preg_split('/\s+/', mb_strtolower(trim($value))) ?: [])
            ->filter()
            ->unique()
            ->values();

        $normalizedFirstName = $user->first_name ? $this->normalizeOwnerCandidateText($user->first_name) : null;
        $normalizedLastName = $user->last_name ? $this->normalizeOwnerCandidateText($user->last_name) : null;

        $personParts = collect([
            $person->given_name,
            $person->middle_name,
            $person->surname,
            $person->birth_surname,
        ])
            ->filter()
            ->map(fn (string $value) => $this->normalizeOwnerCandidateText($value))
            ->values();

        foreach ($nameParts as $part) {
            $normalizedPart = $this->normalizeOwnerCandidateText($part);

            if ($normalizedPart === '') {
                continue;
            }

            if ($personParts->contains($normalizedPart)) {
                $score += 14;
                continue;
            }

            if ($personParts->contains(fn (string $value) => str_starts_with($value, $normalizedPart))) {
                $score += 6;
            }
        }

        if ($normalizedFirstName && $this->normalizeOwnerCandidateText($person->given_name) === $normalizedFirstName) {
            $score += 90;
        }

        if ($normalizedLastName && (
            $this->normalizeOwnerCandidateText($person->surname) === $normalizedLastName
            || $this->normalizeOwnerCandidateText((string) $person->birth_surname) === $normalizedLastName
        )) {
            $score += 25;
        }

        if ($user->birth_date && $person->birth_date) {
            $yearDifference = abs($user->birth_date->year - $person->birth_date->year);

            if ($user->birth_date->isSameDay($person->birth_date)) {
                $score += 140;
            } elseif ($yearDifference === 0) {
                $score += 70;
            } elseif ($yearDifference <= 1) {
                $score += 35;
            } elseif ($yearDifference >= 20) {
                $score -= 25;
            }
        } elseif ($user->birth_date && $person->birth_date_text) {
            preg_match('/\b(\d{4})\b/', $person->birth_date_text, $matches);
            $birthYear = (int) ($matches[1] ?? 0);

            if ($birthYear > 0) {
                $yearDifference = abs($user->birth_date->year - $birthYear);

                if ($yearDifference === 0) {
                    $score += 40;
                } elseif ($yearDifference <= 1) {
                    $score += 20;
                } elseif ($yearDifference >= 20) {
                    $score -= 15;
                }
            }
        }

        return $score;
    }

    private function isConfidentOwnerMatch(Person $person, int $score, ?int $runnerUpScore, User $user): bool
    {
        if ($score < 120) {
            return false;
        }

        if ($runnerUpScore !== null && ($score - $runnerUpScore) < 20) {
            return false;
        }

        $normalizedFirstName = $this->normalizeOwnerCandidateText($user->first_name ?: '');
        $normalizedLastName = $this->normalizeOwnerCandidateText($user->last_name ?: '');
        $personGivenName = $this->normalizeOwnerCandidateText($person->given_name);
        $personSurname = $this->normalizeOwnerCandidateText($person->surname);
        $personBirthSurname = $this->normalizeOwnerCandidateText((string) $person->birth_surname);

        $hasExactNameMatch = $normalizedFirstName !== ''
            && $normalizedLastName !== ''
            && $personGivenName === $normalizedFirstName
            && ($personSurname === $normalizedLastName || $personBirthSurname === $normalizedLastName);

        $hasExactBirthDateMatch = $user->birth_date
            && $person->birth_date
            && $user->birth_date->isSameDay($person->birth_date);

        return $hasExactNameMatch || (bool) $hasExactBirthDateMatch;
    }

    private function normalizeOwnerCandidateText(?string $value): string
    {
        return (string) Str::of((string) $value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish();
    }

    private function placeholderOwnerPerson(FamilyTree $tree, string $userId): ?Person
    {
        if (! $tree->owner_person_id) {
            return null;
        }

        /** @var Person|null $ownerPerson */
        $ownerPerson = $tree->people()->find($tree->owner_person_id);

        if (! $ownerPerson || $ownerPerson->created_by !== $userId) {
            return null;
        }

        $hasRelationships = $tree->relationships()
            ->where(function ($query) use ($ownerPerson) {
                $query->where('person_id', $ownerPerson->id)
                    ->orWhere('related_person_id', $ownerPerson->id);
            })
            ->exists();

        $isAutoCreatedOwner = $ownerPerson->headline === 'Account holder'
            && $ownerPerson->notes === 'This profile was created automatically for the tree owner.';

        if ($hasRelationships || ! $isAutoCreatedOwner) {
            return null;
        }

        return $ownerPerson;
    }

    /**
     * @return list<string>
     */
    private function eventTags(): array
    {
        return [
            'BIRT',
            'DEAT',
            'BURI',
            'CHR',
            'BAPM',
            'FCOM',
            'CONF',
            'GRAD',
            'EDUC',
            'IMMI',
            'EMIG',
            'RETI',
            'RESI',
            'OCCU',
            'RELI',
            'NATI',
            'EVEN',
            'CENS',
            'CHRA',
            'NATU',
            'MARR',
            'DIV',
            'ENGA',
        ];
    }

    /**
     * @param  array<string, mixed>  $head
     */
    private function applyHeadMetadata(FamilyTree $tree, array $head): void
    {
        $source = $head['SOUR'] ?? [];
        $attributes = array_filter([
            'gedcom_source_system' => is_array($source) ? ($source['VALUE'] ?? null) : null,
            'gedcom_source_version' => is_array($source) ? ($source['VERS'] ?? null) : null,
            'gedcom_language' => $head['LANG'] ?? null,
            'gedcom_destination' => $head['DEST'] ?? null,
            'gedcom_exported_at_text' => $head['DATE'] ?? null,
            'gedcom_file_label' => $head['FILE'] ?? null,
            'gedcom_project_guid' => $head['_PROJECT_GUID'] ?? null,
            'gedcom_site_id' => $head['_EXPORTED_FROM_SITE_ID'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        if ($attributes !== []) {
            $tree->update($attributes);
        }
    }

    /**
     * @param  array<string, mixed>  $head
     * @param  array<int, string|int|null>|null  $currentTextContext
     */
    private function parseHeadRecord(array &$head, int $level, string $tag, ?string $value, ?array &$currentTextContext): void
    {
        if ($level === 1 && in_array($tag, ['LANG', 'DEST', 'DATE', 'FILE', '_PROJECT_GUID', '_EXPORTED_FROM_SITE_ID'], true)) {
            $head[$tag] = $value ?? '';

            return;
        }

        if ($level === 1 && $tag === 'SOUR') {
            $head['SOUR'] = ['VALUE' => $value ?? ''];

            return;
        }

        if ($level === 2 && isset($head['SOUR']) && in_array($tag, ['NAME', 'VERS', 'CORP', '_RTLSAVE'], true)) {
            $head['SOUR'][$tag] = $value ?? '';
        }
    }

    /**
     * @param  array<string, mixed>  $records
     * @param  array<int, string|int|null>  $context
     */
    private function appendContinuedText(array &$records, array $context, string $value, bool $withBreak): void
    {
        [$type, $xref, $scope, $index, $field] = $context;
        $separator = $withBreak ? "\n" : '';

        if ($scope === 'event') {
            $existing = (string) ($records[$type][$xref]['EVENTS'][$index][$field] ?? '');
            $records[$type][$xref]['EVENTS'][$index][$field] = $existing.$separator.$value;

            return;
        }

        if ($scope === 'inline_object') {
            $existing = (string) ($records[$type][$xref]['INLINE_OBJE'][$index][$field] ?? '');
            $records[$type][$xref]['INLINE_OBJE'][$index][$field] = $existing.$separator.$value;

            return;
        }

        if ($scope === 'citation_data') {
            $existing = (string) ($records[$type][$xref]['SOUR_CIT'][$index]['DATA'][$field] ?? '');
            $records[$type][$xref]['SOUR_CIT'][$index]['DATA'][$field] = $existing.$separator.$value;

            return;
        }

        $existing = (string) ($records[$type][$xref][$field] ?? '');
        $records[$type][$xref][$field] = $existing.$separator.$value;
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @return array{date: ?string, text: ?string}
     */
    private function primaryEventDate(array $events, string $tag): array
    {
        foreach ($events as $event) {
            if (($event['TAG'] ?? null) !== $tag) {
                continue;
            }

            return GedcomDate::toStorage($event['DATE'] ?? null);
        }

        return ['date' => null, 'text' => null];
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     */
    private function primaryEventField(array $events, string $tag, string $field): ?string
    {
        foreach ($events as $event) {
            if (($event['TAG'] ?? null) === $tag) {
                return $event[$field] ?? null;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     */
    private function hasDeathEvent(array $events): bool
    {
        foreach ($events as $event) {
            if (($event['TAG'] ?? null) === 'DEAT') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function createMediaItem(FamilyTree $tree, string $userId, array $record, ?string $personId = null)
    {
        $fileReference = $record['FILE'] ?? 'imported-media';
        $storedMedia = $this->downloadExternalMediaIfPossible((string) $fileReference);
        $resolvedFileName = $storedMedia['file_name'] ?? $this->resolveImportedFileName((string) $fileReference, $record['TITL'] ?? null);

        return $tree->mediaItems()->create([
            'person_id' => $personId,
            'uploaded_by' => $userId,
            'title' => $record['TITL'] ?? basename((string) $fileReference),
            'file_name' => $resolvedFileName,
            'file_path' => $storedMedia['file_path'] ?? null,
            'external_reference' => $fileReference,
            'mime_type' => $storedMedia['mime_type'] ?? ($record['FORM'] ?? null),
            'file_size' => $storedMedia['file_size'] ?? (isset($record['_FILESIZE']) ? (int) $record['_FILESIZE'] : 0),
            'description' => $record['NOTE'] ?? null,
            'is_primary' => ($record['_PRIM'] ?? null) === 'Y',
            'gedcom_rin' => $record['RIN'] ?? null,
            'gedcom_updated_at_text' => $record['_UPD'] ?? null,
            'gedcom_external_id' => $record['_PHOTO_RIN'] ?? null,
            'gedcom_parent_external_id' => $record['_PARENTRIN'] ?? null,
            'crop_position' => $record['_POSITION'] ?? null,
            'is_cutout' => ($record['_CUTOUT'] ?? null) === 'Y',
            'is_personal_photo' => ($record['_PERSONALPHOTO'] ?? null) === 'Y',
            'is_parent_photo' => ($record['_PARENTPHOTO'] ?? null) === 'Y',
            'is_primary_cutout' => ($record['_PRIM_CUTOUT'] ?? null) === 'Y',
        ]);
    }

    /**
     * @return array{file_path:string,file_name:string,mime_type:?string,file_size:int}|array{}
     */
    private function downloadExternalMediaIfPossible(string $fileReference): array
    {
        if (! filter_var($fileReference, FILTER_VALIDATE_URL)) {
            return [];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'KinfolkAtlasGedcomImporter/1.0',
                ])
                ->get($fileReference);
        } catch (\Throwable) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $body = $response->body();

        if ($body === '') {
            return [];
        }

        $mimeType = $response->header('Content-Type');
        $fileName = $this->resolveImportedFileName($fileReference, null, $mimeType);
        $path = 'media-items/imported/'.Str::uuid()->toString().'-'.$fileName;

        Storage::disk('local')->put($path, $body);

        return [
            'file_path' => $path,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'file_size' => strlen($body),
        ];
    }

    private function resolveImportedFileName(string $fileReference, ?string $fallbackTitle = null, ?string $mimeType = null): string
    {
        $path = parse_url($fileReference, PHP_URL_PATH) ?: $fileReference;
        $candidate = basename((string) $path);

        if ($candidate === '' || $candidate === '.' || $candidate === '/' || ! str_contains($candidate, '.')) {
            $base = Str::slug((string) ($fallbackTitle ?: 'imported-media'));
            $extension = $this->extensionFromMimeType($mimeType);

            return trim(($base !== '' ? $base : 'imported-media').($extension ? '.'.$extension : ''), '.');
        }

        return $candidate;
    }

    private function extensionFromMimeType(?string $mimeType): ?string
    {
        if (! $mimeType) {
            return null;
        }

        $normalized = strtolower(trim(explode(';', $mimeType)[0]));

        return match ($normalized) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
            'image/tiff' => 'tif',
            'application/pdf' => 'pdf',
            default => null,
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     */
    private function createImportedEvents(Person $person, array $events, string $userId): void
    {
        foreach ($events as $index => $event) {
            $tag = $event['TAG'] ?? null;

            if (! $tag || in_array($tag, ['BIRT', 'DEAT'], true)) {
                continue;
            }

            $date = GedcomDate::toStorage($event['DATE'] ?? null);
            $address = $event['ADDR'] ?? [];
            $label = $this->eventLabel($tag, $event);

            $person->events()->create([
                'family_tree_id' => $person->family_tree_id,
                'created_by' => $userId,
                'type' => strtolower($tag),
                'label' => $label,
                'category' => $this->eventCategory($tag),
                'event_date' => $date['date'],
                'event_date_text' => $date['text'],
                'place' => $event['PLAC'] ?? null,
                'value' => $this->eventValue($tag, $event),
                'age' => $event['AGE'] ?? null,
                'cause' => $event['CAUS'] ?? null,
                'email' => $event['EMAIL'] ?? null,
                'address_line1' => $address['ADR1'] ?? null,
                'city' => $address['CITY'] ?? null,
                'country' => $address['CTRY'] ?? null,
                'description' => $event['NOTE'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function eventLabel(string $tag, array $event): string
    {
        if ($tag === 'EVEN' && filled($event['TYPE'] ?? null)) {
            return (string) $event['TYPE'];
        }

        return match ($tag) {
            'BURI' => 'Burial',
            'CHR' => 'Christening',
            'BAPM' => 'Baptism',
            'FCOM' => 'First communion',
            'CONF' => 'Confirmation',
            'GRAD' => 'Graduation',
            'EDUC' => 'Education',
            'IMMI' => 'Immigration',
            'EMIG' => 'Emigration',
            'RETI' => 'Retirement',
            'RESI' => 'Residence',
            'OCCU' => 'Occupation',
            'RELI' => 'Religion',
            'NATI' => 'Nationality',
            'CENS' => 'Census',
            'CHRA' => 'Adult christening',
            'NATU' => 'Naturalization',
            'MARR' => 'Marriage',
            'DIV' => 'Divorce',
            'ENGA' => 'Engagement',
            default => 'Event',
        };
    }

    private function eventCategory(string $tag): string
    {
        return match ($tag) {
            'RESI' => 'residence',
            'OCCU', 'EDUC', 'GRAD' => 'career',
            'IMMI', 'EMIG' => 'migration',
            'BURI' => 'burial',
            'CHR', 'BAPM', 'FCOM', 'CONF', 'CHRA', 'RELI' => 'faith',
            'CENS' => 'fact',
            'NATU' => 'migration',
            default => 'fact',
        };
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function eventValue(string $tag, array $event): ?string
    {
        $value = trim((string) ($event['VALUE'] ?? ''));

        if ($value !== '') {
            return $value;
        }

        if ($tag === 'RELI' || $tag === 'NATI') {
            return trim((string) ($event['VALUE'] ?? '')) ?: null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function spouseRelationshipAttributes(array $record): array
    {
        $marriage = $this->firstFamilyEvent($record, 'MARR');
        $divorce = $this->firstFamilyEvent($record, 'DIV');

        return array_filter([
            'start_date' => $marriage['date'] ?? null,
            'start_date_text' => $marriage['text'] ?? null,
            'end_date' => $divorce['date'] ?? null,
            'end_date_text' => $divorce['text'] ?? null,
            'place' => $marriage['place'] ?? ($divorce['place'] ?? null),
            'subtype' => $marriage['type'] ?? ($marriage !== null ? 'married' : null),
            'description' => $this->combineRelationshipDescription($marriage['note'] ?? null, $divorce['note'] ?? null),
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $familyRecord
     * @param  array<string, mixed>  $childLink
     * @param  array<string, mixed>  $individualRecord
     */
    private function parentRelationshipSubtypeForImport(
        array $familyRecord,
        array $childLink,
        array $individualRecord,
        string $parentXref,
        string $familyXref
    ): ?string {
        $familySubtype = $this->childLinkSubtypeForParent($familyRecord, $childLink, $parentXref);

        if ($familySubtype !== null) {
            return $familySubtype;
        }

        return $this->individualFamilyLinkSubtypeForParent($individualRecord, $familyXref, $parentXref, $familyRecord);
    }

    /**
     * @param  array<string, mixed>  $familyRecord
     * @param  array<string, mixed>  $childLink
     */
    private function childLinkSubtypeForParent(array $familyRecord, array $childLink, string $parentXref): ?string
    {
        $fatherXref = $familyRecord['HUSB'] ?? null;
        $motherXref = $familyRecord['WIFE'] ?? null;

        if ($parentXref === $fatherXref) {
            return $this->normalizeParentRelationshipSubtype($childLink['_FREL'] ?? $childLink['PEDI'] ?? null);
        }

        if ($parentXref === $motherXref) {
            return $this->normalizeParentRelationshipSubtype($childLink['_MREL'] ?? $childLink['PEDI'] ?? null);
        }

        return $this->normalizeParentRelationshipSubtype($childLink['PEDI'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $individualRecord
     * @param  array<string, mixed>  $familyRecord
     */
    private function individualFamilyLinkSubtypeForParent(
        array $individualRecord,
        ?string $familyXref,
        string $parentXref,
        array $familyRecord
    ): ?string {
        if ($familyXref === null) {
            return null;
        }

        foreach ($individualRecord['FAMC'] ?? [] as $familyLink) {
            if (($familyLink['xref'] ?? null) !== $familyXref) {
                continue;
            }

            $specific = $this->normalizeParentRelationshipSubtype(
                $this->familyLinkSpecificPediValue($familyLink['_PEDI'] ?? null, $parentXref, $familyRecord)
            );

            if ($specific !== null) {
                return $specific;
            }

            return $this->normalizeParentRelationshipSubtype($familyLink['PEDI'] ?? null);
        }

        return null;
    }

    /**
     * Supports values like "step (father)" and "adopted (mother)" from custom GEDCOM exports.
     *
     * @param  array<string, mixed>  $familyRecord
     */
    private function familyLinkSpecificPediValue(?string $value, string $parentXref, array $familyRecord): ?string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        if (! preg_match('/^(.*?)\s*\((father|mother)\)$/i', $raw, $matches)) {
            return $raw;
        }

        $target = strtolower($matches[2]);
        $matchesParent = ($target === 'father' && $parentXref === ($familyRecord['HUSB'] ?? null))
            || ($target === 'mother' && $parentXref === ($familyRecord['WIFE'] ?? null));

        return $matchesParent ? trim($matches[1]) : null;
    }

    private function normalizeParentRelationshipSubtype(?string $value): ?string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '', 'birth', 'biological' => null,
            'adopted', 'adoptive' => 'adoptive',
            'foster' => 'foster',
            'guardian', 'guardianship' => 'guardian',
            'step', 'stepchild', 'step-parent', 'step parent' => 'step',
            'sealing', 'sealed' => 'sealing',
            'other' => 'other',
            default => $normalized !== '' ? $normalized : null,
        };
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array{date:?string,text:?string,place:?string,type:?string,note:?string}|null
     */
    private function firstFamilyEvent(array $record, string $tag): ?array
    {
        foreach ($record['EVENTS'] ?? [] as $event) {
            if (($event['TAG'] ?? null) !== $tag) {
                continue;
            }

            $date = GedcomDate::toStorage($event['DATE'] ?? null);

            return [
                'date' => $date['date'],
                'text' => $date['text'],
                'place' => $event['PLAC'] ?? null,
                'type' => $event['TYPE'] ?? null,
                'note' => $event['NOTE'] ?? null,
            ];
        }

        return null;
    }

    private function combineRelationshipDescription(?string $left, ?string $right): ?string
    {
        $parts = array_values(array_filter([$left, $right]));

        return $parts === [] ? null : implode("\n", $parts);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function fillRelationshipMetadata(PersonRelationship $relationship, array $attributes): void
    {
        $changed = false;

        foreach ($attributes as $key => $value) {
            if (($relationship->{$key} ?? null) === null && $value !== null && $value !== '') {
                $relationship->{$key} = $value;
                $changed = true;
            }
        }

        if ($changed) {
            $relationship->save();
        }
    }
}
