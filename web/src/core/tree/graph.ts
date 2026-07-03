import type { Person, Relationship } from '../models';

// Derives family adjacency from raw relationships. A 'parent' relationship has
// person_id = parent and related_person_id = child; 'spouse' is symmetric.
export interface FamilyGraph {
  peopleById: Map<string, Person>;
  parentsOf: Map<string, string[]>; // childId -> parentIds
  childrenOf: Map<string, string[]>; // parentId -> childIds
  spousesOf: Map<string, string[]>;
}

function push(map: Map<string, string[]>, key: string, value: string) {
  const list = map.get(key) ?? [];
  if (!list.includes(value)) list.push(value);
  map.set(key, list);
}

export function buildFamilyGraph(people: Person[], relationships: Relationship[]): FamilyGraph {
  const peopleById = new Map(people.map((p) => [p.id, p]));
  const parentsOf = new Map<string, string[]>();
  const childrenOf = new Map<string, string[]>();
  const spousesOf = new Map<string, string[]>();

  for (const rel of relationships) {
    if (rel.type === 'parent') {
      push(childrenOf, rel.person_id, rel.related_person_id);
      push(parentsOf, rel.related_person_id, rel.person_id);
    } else if (rel.type === 'spouse') {
      push(spousesOf, rel.person_id, rel.related_person_id);
      push(spousesOf, rel.related_person_id, rel.person_id);
    }
  }

  // The layout renders lists in adjacency order, so fix that order here once:
  // children eldest-first by birth date (undated children keep their imported
  // GEDCOM CHIL position, which is already birth order), parents father-first.
  const stableSort = (map: Map<string, string[]>, rank: (id: string) => string | number) => {
    for (const [key, list] of map) {
      map.set(
        key,
        list
          .map((id, i) => [id, i] as const)
          .sort(([idA, iA], [idB, iB]) => {
            const a = rank(idA);
            const b = rank(idB);
            if (a < b) return -1;
            if (a > b) return 1;
            return iA - iB;
          })
          .map(([id]) => id),
      );
    }
  };
  stableSort(childrenOf, (id) => peopleById.get(id)?.birth_date || '9999-99-99');
  const sexOrder = { male: 0, unknown: 1, female: 2 } as const;
  stableSort(parentsOf, (id) => sexOrder[peopleById.get(id)?.sex ?? 'unknown']);

  return { peopleById, parentsOf, childrenOf, spousesOf };
}

export interface FocusFamily {
  focus: Person;
  parents: Person[];
  spouses: Person[];
  children: Person[];
  siblings: Person[];
}

export function focusFamily(graph: FamilyGraph, focusId: string): FocusFamily | null {
  const focus = graph.peopleById.get(focusId);
  if (!focus) return null;

  const resolve = (ids: string[] = []) =>
    ids.map((id) => graph.peopleById.get(id)).filter((p): p is Person => Boolean(p));

  const parentIds = graph.parentsOf.get(focusId) ?? [];

  // Siblings = other children of the focus person's parents.
  const siblingIds = new Set<string>();
  for (const parentId of parentIds) {
    for (const childId of graph.childrenOf.get(parentId) ?? []) {
      if (childId !== focusId) siblingIds.add(childId);
    }
  }

  return {
    focus,
    parents: resolve(parentIds),
    spouses: resolve(graph.spousesOf.get(focusId)),
    children: resolve(graph.childrenOf.get(focusId)),
    siblings: resolve([...siblingIds]),
  };
}
