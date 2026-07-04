import type { Person, Sex } from '@core/models';
import type { FamilyGraph } from '@core/tree/graph';

// How one person relates to another, derived entirely from the client-side
// family graph: BFS for the shortest chain of parent/child/spouse edges, then
// map the move pattern to a genealogical label ("grandfather", "first cousin
// once removed", "sister-in-law", …).

type Move = 'U' | 'D' | 'S'; // up to a parent, down to a child, sideways to a spouse

const MAX_DEPTH = 14;

export function kinshipMoves(graph: FamilyGraph, fromId: string, toId: string): Move[] | null {
  if (fromId === toId) return [];

  const prev = new Map<string, { id: string; move: Move }>();
  const seen = new Set<string>([fromId]);
  let frontier = [fromId];
  let depth = 0;

  while (frontier.length && depth < MAX_DEPTH) {
    const next: string[] = [];
    for (const id of frontier) {
      const neighbours: [string[], Move][] = [
        [graph.parentsOf.get(id) ?? [], 'U'],
        [graph.childrenOf.get(id) ?? [], 'D'],
        [graph.spousesOf.get(id) ?? [], 'S'],
      ];
      for (const [ids, move] of neighbours) {
        for (const n of ids) {
          if (seen.has(n)) continue;
          seen.add(n);
          prev.set(n, { id, move });
          if (n === toId) {
            const moves: Move[] = [];
            let cur = toId;
            while (cur !== fromId) {
              const p = prev.get(cur)!;
              moves.unshift(p.move);
              cur = p.id;
            }
            return moves;
          }
          next.push(n);
        }
      }
    }
    frontier = next;
    depth += 1;
  }

  return null;
}

function bySex(sex: Sex, male: string, female: string, neutral: string): string {
  return sex === 'male' ? male : sex === 'female' ? female : neutral;
}

const ORDINALS = ['first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'ninth', 'tenth'];

function ordinal(n: number): string {
  return ORDINALS[n - 1] ?? `${n}th`;
}

function greats(n: number): string {
  return 'great-'.repeat(Math.max(0, n));
}

// Label a move pattern (as seen from the home person) for `target`.
function labelPattern(pattern: string, sex: Sex): string | null {
  if (pattern === '') return null;
  if (pattern === 'S') return bySex(sex, 'husband', 'wife', 'partner');

  const pure = /^(U*)(D*)$/.exec(pattern);
  if (pure) {
    const m = pure[1].length;
    const n = pure[2].length;

    if (m > 0 && n === 0) {
      if (m === 1) return bySex(sex, 'father', 'mother', 'parent');
      return greats(m - 2) + bySex(sex, 'grandfather', 'grandmother', 'grandparent');
    }
    if (m === 0 && n > 0) {
      if (n === 1) return bySex(sex, 'son', 'daughter', 'child');
      return greats(n - 2) + bySex(sex, 'grandson', 'granddaughter', 'grandchild');
    }
    if (m === 1 && n === 1) return bySex(sex, 'brother', 'sister', 'sibling');
    if (n === 1) return greats(m - 2) + bySex(sex, 'uncle', 'aunt', 'uncle/aunt');
    if (m === 1) return greats(n - 2) + bySex(sex, 'nephew', 'niece', 'nephew/niece');

    const degree = Math.min(m, n) - 1;
    const removed = Math.abs(m - n);
    const base = `${ordinal(degree)} cousin`;
    if (removed === 0) return base;
    if (removed === 1) return `${base} once removed`;
    if (removed === 2) return `${base} twice removed`;
    return `${base} ${removed}× removed`;
  }

  // Single sideways step at a boundary: in-law and step relations.
  if (pattern.startsWith('S')) {
    const inner = labelPattern(pattern.slice(1), sex);
    if (pattern === 'SD') return bySex(sex, 'stepson', 'stepdaughter', 'stepchild');
    if (inner) return `${inner}-in-law`;
  }
  if (pattern.endsWith('S')) {
    if (pattern === 'US') return bySex(sex, 'stepfather', 'stepmother', 'step-parent');
    if (pattern === 'DS') return bySex(sex, 'son-in-law', 'daughter-in-law', 'child-in-law');
    const inner = labelPattern(pattern.slice(0, -1), sex);
    if (inner) return `${inner}'s ${bySex(sex, 'husband', 'wife', 'partner')}`;
  }

  return null;
}

export interface Kinship {
  label: string;
  steps: number;
}

// "How is `target` related to `home`?" — e.g. target is home's grandfather.
export function kinshipToHome(graph: FamilyGraph, homeId: string, target: Person): Kinship | null {
  if (homeId === target.id) return null;
  const moves = kinshipMoves(graph, homeId, target.id);
  if (!moves || moves.length === 0) return null;
  const label = labelPattern(moves.join(''), target.sex);
  return {
    label: label ?? `relative · ${moves.length} steps apart`,
    steps: moves.length,
  };
}
