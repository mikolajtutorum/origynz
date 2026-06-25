import type { Person } from '../models';
import type { FamilyGraph } from './graph';

// Interactive family layout, built on the genealogy "union" model (the GEDCOM FAM
// record): the fundamental object is a couple + the children they share. Every
// person is placed by reserved-width recursion so a parent always sits centred
// above its children, and every connector is OWNED by a union — a spouse bar joins
// the union's two partner cards, and each child has exactly one comb up to its
// parent-union. Connectors are never inferred from how close two cards happen to
// land, so no stray lines appear between siblings, their partners or their kids.

export const CARD_W = 188;
export const CARD_H = 84;
const H_GAP = 40; // gap between sibling subtrees / distinct couples
const COUPLE_GAP = 12; // gap between the two cards of a tightly-packed couple
const COUPLE_STEP = CARD_W + COUPLE_GAP; // centre-to-centre distance of a couple
const ROW_GAP = 72; // vertical gap between generations
const ROW = CARD_H + ROW_GAP;
const CARD_CAP = 2000; // safety limit on total cards

export interface PlacedCard {
  person: Person;
  x: number; // top-left
  y: number;
}

export interface Point {
  x: number;
  y: number;
}

// A connector is a polyline; the renderer rounds its corners.
export type Connector = Point[];

export type Direction = 'parents' | 'children' | 'spouses' | 'siblings';

export interface ExpansionState {
  parents: Set<string>; // nodes whose parents are shown
  children: Set<string>; // nodes whose children are shown
  spouses: Set<string>; // nodes whose extra spouses are shown
  siblings: Set<string>; // nodes whose siblings are shown
}

export interface TreeLayout {
  cards: PlacedCard[];
  links: Connector[];
  width: number;
  height: number;
}

export function emptyExpansion(): ExpansionState {
  return { parents: new Set(), children: new Set(), spouses: new Set(), siblings: new Set() };
}

/** Seed expansion from the steppers: `up` ancestor generations, `down` descendant generations. */
export function seedExpansion(graph: FamilyGraph, focusId: string, up: number, down: number): ExpansionState {
  const exp = emptyExpansion();

  let frontier = [focusId];
  for (let d = 0; d < up; d++) {
    const next: string[] = [];
    for (const id of frontier) {
      exp.parents.add(id);
      for (const p of (graph.parentsOf.get(id) ?? []).slice(0, 2)) next.push(p);
    }
    frontier = next;
  }

  frontier = [focusId];
  for (let d = 0; d < down; d++) {
    const next: string[] = [];
    for (const id of frontier) {
      exp.children.add(id);
      for (const c of graph.childrenOf.get(id) ?? []) next.push(c);
    }
    frontier = next;
  }

  if (up >= 1) exp.siblings.add(focusId);
  return exp;
}

// ── internal block model (local x, absolute y by generation) ──
interface Block {
  cards: PlacedCard[];
  width: number;
  anchor: number; // x of this node's card centre
}

function shiftBlock(b: Block, dx: number): void {
  for (const c of b.cards) c.x += dx;
  b.anchor += dx;
}

function packRow(blocks: Block[], gap: number): number {
  let x = 0;
  blocks.forEach((b, i) => {
    shiftBlock(b, x);
    x += b.width + (i < blocks.length - 1 ? gap : 0);
  });
  return x;
}

// Minimal shift (dir: 1 = right, -1 = left) so the moving cards never overlap any
// obstacle in the same row, keeping `gap` between them.
function clearShift(moving: PlacedCard[], obstacles: PlacedCard[], dir: 1 | -1, gap: number): number {
  const rows = new Set(moving.map((m) => m.y));
  const relevant = obstacles.filter((o) => rows.has(o.y));
  let shift = 0;
  for (let iter = 0; iter <= relevant.length + 2; iter++) {
    let worst = 0;
    for (const m of moving) {
      for (const o of relevant) {
        if (m.y !== o.y) continue;
        const mx = m.x + shift;
        if (mx < o.x + CARD_W + gap && mx + CARD_W + gap > o.x) {
          if (dir === 1) worst = Math.max(worst, o.x + CARD_W + gap - mx);
          else worst = Math.min(worst, o.x - gap - (mx + CARD_W));
        }
      }
    }
    if (worst === 0) break;
    shift += worst;
  }
  return shift;
}

export function buildTreeLayout(graph: FamilyGraph, focusId: string, exp: ExpansionState): TreeLayout | null {
  if (!graph.peopleById.has(focusId)) return null;

  const person = (id: string) => graph.peopleById.get(id);
  const parentsOf = (id: string) => graph.parentsOf.get(id) ?? [];
  const childrenOf = (id: string) => graph.childrenOf.get(id) ?? [];
  const spousesOf = (id: string) => graph.spousesOf.get(id) ?? [];

  const placed = new Set<string>();

  // The unions to render BELOW a person, grouped by co-parent. Imported data often
  // records only the shared child (not a spouse link), so a co-parent — anyone you
  // share a child with — defines a union just as an explicit spouse does.
  interface Union {
    co: Person | null; // the other parent, if shown-able
    kids: string[]; // their shared children, not yet placed
  }
  const downUnions = (id: string, force: boolean): Union[] => {
    const unions: Union[] = [];
    if (force || exp.children.has(id)) {
      const byCo = new Map<string, string[]>();
      for (const k of childrenOf(id)) {
        if (placed.has(k)) continue;
        const coId = parentsOf(k).find((p) => p !== id) ?? '';
        (byCo.get(coId) ?? byCo.set(coId, []).get(coId)!).push(k);
      }
      for (const [coId, kids] of byCo) {
        if (coId && placed.has(coId)) continue; // co-parent shown elsewhere
        unions.push({ co: coId ? person(coId) ?? null : null, kids });
      }
    }
    if (exp.spouses.has(id)) { // childless couples are opt-in, never forced
      for (const sId of spousesOf(id)) {
        if (placed.has(sId) || unions.some((u) => u.co?.id === sId)) continue;
        unions.push({ co: person(sId) ?? null, kids: [] }); // childless couple → still gets a bar
      }
    }
    return unions;
  };

  // ── descendant subtree: a person (with partner(s) beside them) and every shown
  //    descendant below, laid with reserved width so children sit under parents. ──
  function descBlock(id: string, gen: number, force = false): Block {
    placed.add(id);
    const me = person(id)!;
    const y = gen * ROW;
    const leaf: Block = { cards: [{ person: me, x: 0, y }], width: CARD_W, anchor: CARD_W / 2 };
    if (placed.size >= CARD_CAP) return leaf; // stop growing once the canvas is full

    const unions = downUnions(id, force);
    if (unions.length === 0) return leaf;

    // Real partner couples vs single-parent kid groups (co-parent unknown).
    const coUnions = unions.filter((u): u is { co: Person; kids: string[] } => Boolean(u.co));
    const soloKids = unions.filter((u) => !u.co).flatMap((u) => u.kids);

    // Build a union's children as reserved-width subtrees, packed left to right.
    const childForest = (kids: string[]) => {
      const blocks = kids.filter((k) => !placed.has(k)).map((k) => descBlock(k, gen + 1, force));
      const width = packRow(blocks, H_GAP);
      const cards = blocks.flatMap((b) => b.cards);
      const anchors = blocks.map((b) => b.anchor);
      const mid = anchors.length ? (anchors[0] + anchors[anchors.length - 1]) / 2 : width / 2;
      return { cards, width, mid, n: blocks.length };
    };

    // Lay a "unit" (the person + partners in a row) centred over a forest of kids.
    const overUnit = (unit: Person[], kids: string[]): Block => {
      const unitW = unit.length * CARD_W + (unit.length - 1) * COUPLE_GAP;
      const place = (left: number) => unit.map((p, i) => ({ person: p, x: left + i * (CARD_W + COUPLE_GAP), y }));
      const f = childForest(kids);
      if (f.n === 0) return { cards: place(0), width: unitW, anchor: CARD_W / 2 };
      let unitLeft = f.mid - unitW / 2; // unit centred over the children's midpoint
      let kidDx = 0;
      if (unitLeft < 0) { kidDx = -unitLeft; unitLeft = 0; }
      for (const c of f.cards) c.x += kidDx;
      return { cards: [...place(unitLeft), ...f.cards], width: Math.max(unitLeft + unitW, f.width + kidDx), anchor: unitLeft + CARD_W / 2 };
    };

    // ── single couple (or a lone parent): couple on top, children centred beneath
    //    the marriage point — the overwhelming common case. ──
    if (coUnions.length <= 1) {
      const co = coUnions[0]?.co ?? null;
      if (co) placed.add(co.id);
      return overUnit(co ? [me, co] : [me], [...(coUnions[0]?.kids ?? []), ...soloKids]);
    }

    // ── two couples: a partner on EACH side so both marriage points stay clean —
    //    [coL][me][coR], coL's kids under the left gap, coR's under the right. ──
    if (coUnions.length === 2 && soloKids.length === 0) {
      const [uL, uR] = coUnions;
      placed.add(uL.co.id);
      placed.add(uR.co.id);
      const fL = childForest(uL.kids);
      const fR = childForest(uR.kids);
      const D = Math.max(COUPLE_STEP, (fL.width + fR.width) / 2 + H_GAP); // widen so forests clear
      const coLC = -D, coRC = D; // local frame, me at 0
      for (const c of fL.cards) c.x += coLC / 2 - fL.mid;
      for (const c of fR.cards) c.x += coRC / 2 - fR.mid;
      const all: PlacedCard[] = [
        { person: uL.co, x: coLC - CARD_W / 2, y },
        { person: me, x: -CARD_W / 2, y },
        { person: uR.co, x: coRC - CARD_W / 2, y },
        ...fL.cards, ...fR.cards,
      ];
      const minX = Math.min(...all.map((c) => c.x));
      for (const c of all) c.x -= minX;
      return { cards: all, width: Math.max(...all.map((c) => c.x + CARD_W)), anchor: -CARD_W / 2 - minX + CARD_W / 2 };
    }

    // ── 3+ couples (a rare handful in thousands): lump partners to one side and
    //    centre all children below. Inner marriage bars may cross — accepted. ──
    coUnions.forEach((u) => placed.add(u.co.id));
    return overUnit([me, ...coUnions.map((u) => u.co)], [...coUnions.flatMap((u) => u.kids), ...soloKids]);
  }

  // ── ancestor pedigree (node at row `gen`, parents above) ──
  function ancBlock(id: string, gen: number): Block {
    placed.add(id);
    const me = person(id)!;
    const y = gen * ROW;
    const parentIds = exp.parents.has(id) ? parentsOf(id).slice(0, 2).filter((p) => !placed.has(p)) : [];
    if (parentIds.length === 0) return { cards: [{ person: me, x: 0, y }], width: CARD_W, anchor: CARD_W / 2 };

    const pBlocks = parentIds.map((p) => ancBlock(p, gen - 1));
    const packW = packRow(pBlocks, H_GAP);
    const anchors = pBlocks.map((b) => b.anchor);
    let mid = (anchors[0] + anchors[anchors.length - 1]) / 2;
    let nodeLeft = mid - CARD_W / 2;
    if (nodeLeft < 0) {
      pBlocks.forEach((b) => shiftBlock(b, -nodeLeft));
      mid -= nodeLeft;
      nodeLeft = 0;
    }
    const cards = [{ person: me, x: nodeLeft, y }, ...pBlocks.flatMap((b) => b.cards)];
    return { cards, width: Math.max(nodeLeft + CARD_W, packW), anchor: nodeLeft + CARD_W / 2 };
  }

  // ── tidy core: focus + full siblings under the parent couple, pedigree above,
  //    half-siblings under their own couple ──
  const parentRow = exp.parents.has(focusId) ? parentsOf(focusId).slice(0, 2) : [];
  const [A, B] = parentRow;

  let leftHalf: string[] = [];
  let central: string[] = [];
  let rightHalf: string[] = [];
  let Q: string | undefined;
  let R: string | undefined;
  if (exp.siblings.has(focusId) && parentRow.length > 0) {
    const aKids = new Set(A ? childrenOf(A) : []);
    const bKids = new Set(B ? childrenOf(B) : []);
    for (const cid of new Set([...aKids, ...bKids])) {
      if (cid === focusId) continue;
      const inA = aKids.has(cid);
      const inB = bKids.has(cid);
      if (inA && inB) central.push(cid);
      else if (inA) leftHalf.push(cid);
      else rightHalf.push(cid);
    }
    if (leftHalf.length) Q = parentsOf(leftHalf[0]).find((p) => p !== A);
    if (rightHalf.length) R = parentsOf(rightHalf[0]).find((p) => p !== B);
  }

  const centralKids = [...central, focusId];
  // Force-descend the whole down forest so every person is laid WITH their partner
  // and children as one reserved-width subtree (couples adjacent, kids beneath) —
  // never a bare card that later has a spouse bolted onto it.
  const centralBlocks = centralKids.map((cid) => descBlock(cid, 0, true));
  packRow(centralBlocks, H_GAP);
  const cxs = centralBlocks.map((b) => b.anchor);
  const centralMid = (Math.min(...cxs) + Math.max(...cxs)) / 2;
  let cards: PlacedCard[] = centralBlocks.flatMap((b) => b.cards);

  if (parentRow.length > 0) {
    const anchors = new Map<string, number>();
    const upBlocks: Block[] = [];

    const centralPids = [A, B].filter((x): x is string => Boolean(x));
    const cBlocks = centralPids.map((pid) => ancBlock(pid, -1));
    packRow(cBlocks, H_GAP);
    const cMid = cBlocks.length === 2 ? (cBlocks[0].anchor + cBlocks[1].anchor) / 2 : cBlocks[0].anchor;
    cBlocks.forEach((b) => shiftBlock(b, centralMid - cMid));
    centralPids.forEach((pid, i) => anchors.set(pid, cBlocks[i].anchor));
    upBlocks.push(...cBlocks);

    const buildSide = (partnerId: string | undefined, baseId: string, dir: 1 | -1, kids: string[]) => {
      if (kids.length === 0) return;
      const baseCenter = anchors.get(baseId)!;
      const obstacles = [...cards, ...upBlocks.flatMap((b) => b.cards)].filter((c) => c.person.id !== baseId);
      let unionMid = baseCenter;
      let partnerBlock: Block | undefined;
      if (partnerId) {
        partnerBlock = ancBlock(partnerId, -1);
        shiftBlock(partnerBlock, baseCenter + dir * COUPLE_STEP - partnerBlock.anchor);
        shiftBlock(partnerBlock, clearShift(partnerBlock.cards, obstacles, dir, H_GAP));
        anchors.set(partnerId, partnerBlock.anchor);
        upBlocks.push(partnerBlock);
        unionMid = (baseCenter + partnerBlock.anchor) / 2;
      }
      const kidBlocks = kids.map((k) => descBlock(k, 0, true));
      packRow(kidBlocks, H_GAP);
      const ks = kidBlocks.map((b) => b.anchor);
      const kMid = (Math.min(...ks) + Math.max(...ks)) / 2;
      kidBlocks.forEach((b) => shiftBlock(b, unionMid - kMid));
      const row0 = obstacles.filter((c) => c.y === 0);
      const extra = clearShift(kidBlocks.flatMap((b) => b.cards), row0, dir, H_GAP);
      if (extra !== 0) {
        if (partnerBlock && partnerId) {
          shiftBlock(partnerBlock, 2 * extra);
          anchors.set(partnerId, partnerBlock.anchor);
        }
        kidBlocks.forEach((b) => shiftBlock(b, extra));
      }
      cards = [...cards, ...kidBlocks.flatMap((b) => b.cards)];
    };
    if (A) buildSide(Q, A, -1, leftHalf);
    if (B) buildSide(R, B, 1, rightHalf);

    cards = [...cards, ...upBlocks.flatMap((b) => b.cards)];
  }

  // ── extended family: every shown person's not-yet-placed descendants, each laid
  //    as a reserved-width subtree (children under their parents) and rigid-shifted
  //    as one unit to clear what's already placed — alignment is preserved within
  //    each subtree; only the graft to an existing card can sit off to one side. ──
  const pos = new Map<string, PlacedCard>();
  for (const c of cards) pos.set(c.person.id, c);
  const focusCenterX = (pos.get(focusId)?.x ?? 0) + CARD_W / 2;
  const queue = cards.map((c) => c.person.id);
  for (let qi = 0; qi < queue.length && cards.length < CARD_CAP; qi++) {
    const pid = queue[qi];
    const pcard = pos.get(pid);
    if (!pcard) continue;
    const genP = Math.round(pcard.y / ROW);

    const byCo = new Map<string, string[]>();
    for (const k of childrenOf(pid)) {
      if (placed.has(k)) continue;
      const coId = parentsOf(k).find((p) => p !== pid) ?? '';
      (byCo.get(coId) ?? byCo.set(coId, []).get(coId)!).push(k);
    }

    // Place a moving set of cards with the SMALLEST shift that clears `cards` —
    // trying both directions so a grafted family lands on its nearer side instead
    // of always being shoved around the whole central cone.
    const nearestClear = (moving: PlacedCard[]): number => {
      const r = clearShift(moving, cards, 1, H_GAP);
      const l = clearShift(moving, cards, -1, H_GAP);
      return Math.abs(l) < Math.abs(r) ? l : r;
    };

    for (const [coId, kids] of byCo) {
      const dir: 1 | -1 = pcard.x + CARD_W / 2 < focusCenterX ? -1 : 1;
      const coPerson = coId ? person(coId) : undefined;
      let coCard = coId ? pos.get(coId) : undefined;
      if (coPerson && !coCard) {
        const cp: PlacedCard = { person: coPerson, x: pcard.x + dir * COUPLE_STEP, y: pcard.y };
        cp.x += nearestClear([cp]);
        cards.push(cp); pos.set(coId, cp); placed.add(coId); queue.push(coId);
        coCard = cp;
      }
      const adjacent = !!coCard && coCard.y === pcard.y && Math.abs(coCard.x - pcard.x) <= COUPLE_STEP + CARD_W;
      const midX = adjacent ? (pcard.x + coCard!.x) / 2 + CARD_W / 2 : pcard.x + CARD_W / 2;

      const blocks = kids.filter((k) => !placed.has(k)).map((k) => descBlock(k, genP + 1, true));
      if (blocks.length === 0) continue;
      packRow(blocks, H_GAP);
      const anchors = blocks.map((b) => b.anchor);
      const kmid = (anchors[0] + anchors[anchors.length - 1]) / 2;
      blocks.forEach((b) => shiftBlock(b, midX - kmid));
      // One shift for the WHOLE packed forest so siblings keep their spacing and
      // clear as a unit — shifting each block on its own lets them stack.
      const forest = blocks.flatMap((b) => b.cards);
      const sh = nearestClear(forest);
      for (const c of forest) {
        c.x += sh;
        cards.push(c); pos.set(c.person.id, c); placed.add(c.person.id); queue.push(c.person.id);
      }
    }
  }

  // ── final safety net: nudge apart any cards that still genuinely overlap (rare,
  //    only where a grafted family could not be fully cleared). This only removes
  //    true overlaps — it does NOT re-flow rows — so children stay under parents. ──
  const rows = new Map<number, PlacedCard[]>();
  for (const c of cards) (rows.get(c.y) ?? rows.set(c.y, []).get(c.y)!).push(c);
  for (const row of rows.values()) {
    row.sort((a, b) => a.x - b.x);
    for (let i = 1; i < row.length; i++) {
      const need = row[i - 1].x + CARD_W - row[i].x; // >0 means they overlap
      if (need > 0) row[i].x += need;
    }
  }

  // ── connectors, owned by unions and drawn from final card positions ──
  const links: Connector[] = [];
  interface DrawUnion { parents: PlacedCard[]; kids: PlacedCard[] }
  const unionsByKey = new Map<string, DrawUnion>();
  const unionFor = (parentIds: string[]): DrawUnion => {
    const ps = parentIds.filter((p) => pos.has(p));
    const key = ps.slice().sort().join('|');
    let u = unionsByKey.get(key);
    if (!u) { u = { parents: ps.map((p) => pos.get(p)!), kids: [] }; unionsByKey.set(key, u); }
    return u;
  };
  for (const c of cards) {
    const ps = parentsOf(c.person.id).filter((p) => pos.has(p));
    if (ps.length) unionFor(ps).kids.push(c);
  }
  for (const c of cards) for (const s of spousesOf(c.person.id)) {
    if (pos.has(s)) unionFor([c.person.id, s]); // ensure a childless couple still gets a bar
  }

  for (const u of unionsByKey.values()) {
    const ps = u.parents;
    if (ps.length === 0) continue;
    // spouse bar between the two ACTUAL partners — but never drawn across a card
    // that happens to sit between them (a rare non-adjacent couple); the shared
    // child combs still convey the relationship in that case.
    if (ps.length === 2 && ps[0].y === ps[1].y) {
      const [L, Rt] = ps[0].x < ps[1].x ? [ps[0], ps[1]] : [ps[1], ps[0]];
      const between = cards.some((c) => c.y === L.y && c !== L && c !== Rt && c.x + CARD_W > L.x + CARD_W && c.x < Rt.x);
      if (!between) links.push([{ x: L.x + CARD_W, y: L.y + CARD_H / 2 }, { x: Rt.x, y: Rt.y + CARD_H / 2 }]);
    }
    if (u.kids.length === 0) continue;
    const prow = Math.max(...ps.map((p) => p.y));
    const centers = ps.map((p) => p.x + CARD_W / 2);
    const jx = ps.length >= 2 ? (Math.min(...centers) + Math.max(...centers)) / 2 : centers[0];
    const jy = ps.length >= 2 ? prow + CARD_H / 2 : prow + CARD_H;
    const kidsByRow = new Map<number, PlacedCard[]>();
    for (const k of u.kids) (kidsByRow.get(k.y) ?? kidsByRow.set(k.y, []).get(k.y)!).push(k);
    for (const [krow, ks] of kidsByRow) {
      const bar = krow - ROW_GAP / 2;
      for (const k of ks) {
        const a = k.x + CARD_W / 2;
        links.push([{ x: jx, y: jy }, { x: jx, y: bar }, { x: a, y: bar }, { x: a, y: krow }]);
      }
    }
  }

  // ── normalise to a padded, positive canvas ──
  const PAD = 60;
  const xs = cards.flatMap((c) => [c.x, c.x + CARD_W]);
  const ys = cards.flatMap((c) => [c.y, c.y + CARD_H]);
  const minX = Math.min(...xs);
  const minY = Math.min(...ys);
  const offX = -minX + PAD;
  const offY = -minY + PAD;
  for (const c of cards) {
    c.x += offX;
    c.y += offY;
  }
  for (const conn of links) for (const p of conn) {
    p.x += offX;
    p.y += offY;
  }

  return {
    cards,
    links,
    width: Math.max(...xs) - minX + PAD * 2,
    height: Math.max(...ys) - minY + PAD * 2,
  };
}
