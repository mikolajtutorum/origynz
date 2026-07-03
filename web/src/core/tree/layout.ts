import type { Person } from '../models';
import type { FamilyGraph } from './graph';

// Interactive family layout, built on the genealogy "union" model (the GEDCOM FAM
// record): the fundamental object is a couple + the children they share. The rules
// are applied identically to EVERY person shown:
//
//  · A spouse always renders beside their partner — married-without-children
//    couples get a card and a marriage bar just like couples with children.
//  · Couples are sex-ordered (husband left, wife right) and sit a constant
//    COUPLE_GAP apart wherever the surrounding subtrees allow.
//  · Children hang in birth order (falling back to GEDCOM CHIL order) from ONE
//    junction: the middle of the marriage bar when the couple is adjacent, or the
//    bottom of the single/nearest parent card otherwise. Placement and connectors
//    share the same adjacency test, so the trunk is always above the children it
//    owns and never starts in empty space or crosses the card between a split
//    couple.
//  · The generation window is uniform: ancestors up to `up` rows above the focus,
//    and every shown line (the focus's, siblings', aunts'/uncles') descends to the
//    same `down` rows below — the same depth rule for all people.
//
// Every person is placed by reserved-width recursion so a parent always sits
// centred above its children, and every connector is OWNED by a union. Connectors
// are never inferred from how close two cards happen to land, so no stray lines
// appear between siblings, their partners or their kids.

export const CARD_W = 188;
export const CARD_H = 84;
const H_GAP = 40; // gap between sibling subtrees / distinct couples
const COUPLE_GAP = 12; // gap between the two cards of a tightly-packed couple
const COUPLE_STEP = CARD_W + COUPLE_GAP; // centre-to-centre distance of a couple
const ROW_GAP = 96; // vertical gap between generations — roomy enough that the
// route band (upper) and the child-bar band (lower) read as separate lanes
const ROW = CARD_H + ROW_GAP;
const CARD_CAP = 6000; // safety limit on total cards
const BAR_LIFT = 12; // vertical step between de-conflicted lanes (routes and
// child bars) — wide enough that parallel runs read as clearly separate lines

export interface PlacedCard {
  person: Person;
  x: number; // top-left
  y: number;
}

export interface Point {
  x: number;
  y: number;
  hop?: boolean; // horizontal run hops over a foreign vertical here (small arc)
}

// A connector is a polyline; the renderer rounds its corners.
export type Connector = Point[];

export type Direction = 'parents' | 'children' | 'spouses' | 'siblings';

export interface ExpansionState {
  parents: Set<string>; // nodes whose parents are shown
  children: Set<string>; // nodes whose children are shown
  siblings: Set<string>; // nodes whose siblings are shown
  blood: Set<string>; // the focus's blood cone: only THESE people's spouses render;
  // an in-law's other marriages stay hidden until the tree is re-focused on them
}

export interface TreeLayout {
  cards: PlacedCard[];
  links: Connector[];
  width: number;
  height: number;
}

export function emptyExpansion(): ExpansionState {
  return { parents: new Set(), children: new Set(), siblings: new Set(), blood: new Set() };
}

/**
 * Seed expansion from the steppers: `up` ancestor generations, `down` descendant
 * generations. The descendant rule is a uniform window — a person's children are
 * shown whenever that person sits above the bottom row, so the focus, siblings,
 * aunts/uncles and cousins all descend by exactly the same rule.
 */
export function seedExpansion(graph: FamilyGraph, focusId: string, up: number, down: number): ExpansionState {
  const exp = emptyExpansion();

  // Lineal ancestors, remembering each one's row (negative = above the focus).
  const row = new Map<string, number>([[focusId, 0]]);
  let frontier = [focusId];
  for (let d = 1; d <= up; d++) {
    const next: string[] = [];
    for (const id of frontier) {
      exp.parents.add(id);
      for (const p of (graph.parentsOf.get(id) ?? []).slice(0, 2)) {
        if (!row.has(p)) {
          row.set(p, -d);
          next.push(p);
        }
      }
    }
    frontier = next;
  }

  // Flow down from the focus and every ancestor: anyone above row `down` shows
  // their children, so all collateral lines share the focus line's bottom row.
  const queue = [...row.keys()];
  for (let qi = 0; qi < queue.length; qi++) {
    const id = queue[qi];
    const r = row.get(id)!;
    if (r >= down) continue;
    exp.children.add(id);
    for (const k of graph.childrenOf.get(id) ?? []) {
      if ((row.get(k) ?? Infinity) > r + 1) {
        row.set(k, r + 1);
        queue.push(k);
      }
    }
  }

  if (up >= 1) exp.siblings.add(focusId);
  exp.blood = new Set(row.keys()); // everyone reached through parent/child edges
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

// Male partner left, female right; unknown sexes keep
// their given order so the layout stays deterministic.
const sexRank = (p: Person): number => (p.sex === 'male' ? 0 : p.sex === 'female' ? 2 : 1);

export function buildTreeLayout(graph: FamilyGraph, focusId: string, exp: ExpansionState): TreeLayout | null {
  if (!graph.peopleById.has(focusId)) return null;

  const person = (id: string) => graph.peopleById.get(id);
  const parentsOf = (id: string) => graph.parentsOf.get(id) ?? [];
  const childrenOf = (id: string) => graph.childrenOf.get(id) ?? [];
  const spousesOf = (id: string) => graph.spousesOf.get(id) ?? [];
  const hasKidsWith = (a: string, b: string) => childrenOf(a).some((k) => parentsOf(k).includes(b));

  const placed = new Set<string>();

  // A partner's OWN other spouses ride along, chained on the partner's outer
  // side, so a multi-marriage chain stays contiguous and its routed connectors
  // only ever pass over sibling spouse cards — never across a foreign family's
  // trunk. Spouses-of-spouses join too (a remarried in-law's own chain).
  const extrasOf = (partner: Person, excludeId: string): Person[] => {
    const out: Person[] = [];
    const seen = new Set<string>([partner.id, excludeId]);
    // depth-first: each spouse's own sub-chain follows immediately after them,
    // so a spouse-of-spouse sits ADJACENT to their partner (a plain bar) instead
    // of being flattened to the end of the chain behind unrelated cards
    const dfs = (anchor: Person) => {
      if (!exp.blood.has(anchor.id)) return; // in-laws keep their other marriages private until refocused
      const own: Person[] = [];
      for (const sId of spousesOf(anchor.id)) {
        if (seen.has(sId) || placed.has(sId)) continue;
        const sp = person(sId);
        if (sp) { own.push(sp); seen.add(sId); }
      }
      // childless marriages sit nearest, marriages with children farthest —
      // their routed connector then owns the deepest lane and its child
      // trunk drops clear of the shallower routes nested inside it
      own.sort((a, b) => Number(hasKidsWith(anchor.id, a.id)) - Number(hasKidsWith(anchor.id, b.id)));
      for (const ex of own) {
        out.push(ex);
        dfs(ex);
      }
    };
    dfs(partner);
    out.forEach((e) => placed.add(e.id));
    return out;
  };

  // Append a chain of extra-spouse cards to one side of a block's anchor card.
  const chainExtras = (b: Block, extras: Person[], side: 1 | -1, y: number): Block => {
    if (extras.length === 0) return b;
    const ex = extras.map((p, i) => ({ person: p, x: b.anchor - CARD_W / 2 + side * (i + 1) * COUPLE_STEP, y }));
    const cards = [...b.cards, ...ex];
    const minX = Math.min(...cards.map((c) => c.x));
    for (const c of cards) c.x -= minX;
    return { cards, width: Math.max(...cards.map((c) => c.x + CARD_W)), anchor: b.anchor - minX };
  };

  // The unions to render BELOW a person, grouped by co-parent. Imported data often
  // records only the shared child (not a spouse link), so a co-parent — anyone you
  // share a child with — defines a union just as an explicit spouse does. Spouses
  // themselves ALWAYS render, children or none, so a childless couple still gets
  // its two cards and marriage bar.
  interface Union {
    co: Person | null; // the other parent, if shown-able
    kids: string[]; // their shared children, not yet placed
  }
  const downUnions = (id: string): Union[] => {
    const unions: Union[] = [];
    if (exp.children.has(id)) {
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
    if (exp.blood.has(id)) {
      for (const sId of spousesOf(id)) {
        if (placed.has(sId) || unions.some((u) => u.co?.id === sId)) continue;
        const sp = person(sId);
        if (sp) unions.push({ co: sp, kids: [] }); // childless couple → still gets a bar
      }
    }
    return unions;
  };

  // ── descendant subtree: the person with their WHOLE marriage chain in a row
  //    (spouses, spouses' other spouses, …) and every union's children beneath
  //    that union's own slot, packed strictly in slot order. Kid groups can
  //    therefore never interleave, which is what keeps combs from ever sweeping
  //    across a neighbouring union's trunk. ──
  function descBlock(id: string, gen: number): Block {
    placed.add(id);
    const me = person(id)!;
    const y = gen * ROW;
    const leaf: Block = { cards: [{ person: me, x: 0, y }], width: CARD_W, anchor: CARD_W / 2 };
    if (placed.size >= CARD_CAP) return leaf; // stop growing once the canvas is full

    const unions = downUnions(id);
    if (unions.length === 0) return leaf;

    const coUnions = unions.filter((u): u is { co: Person; kids: string[] } => Boolean(u.co));
    const soloKids = unions.filter((u) => !u.co).flatMap((u) => u.kids);

    // — 1. the chain: me at slot 0, partners at ±1, ±2… (slot × COUPLE_STEP) —
    interface ChainUnion { aSlot: number; bSlot: number | null; kids: string[] }
    const slotOf = new Map<string, number>([[id, 0]]);
    let loSlot = 0, hiSlot = 0;
    const takeSlot = (p: Person, side: 1 | -1): number => {
      const slot = side === 1 ? ++hiSlot : --loSlot;
      slotOf.set(p.id, slot);
      placed.add(p.id);
      return slot;
    };
    const chainUnions: ChainUnion[] = [];
    const meMale = me.sex !== 'female';
    coUnions.forEach((u, i) => {
      // one couple: sex rule picks the side; two: one on each side (first
      // marriage on the sex side); three+: all chained outward in order
      const side: 1 | -1 =
        coUnions.length === 1 ? (sexRank(u.co) < sexRank(me) ? -1 : 1)
        : coUnions.length === 2 ? (meMale ? (i === 0 ? 1 : -1) : (i === 0 ? -1 : 1))
        : meMale ? 1 : -1;
      chainUnions.push({ aSlot: 0, bSlot: takeSlot(u.co, side), kids: u.kids });
    });
    if (soloKids.length) chainUnions.push({ aSlot: 0, bSlot: null, kids: soloKids });
    // partners' own other spouses ride along on their partner's outer side, so
    // multi-marriage webs stay contiguous; childless nearest, kid-ful farthest.
    // Depth-first: each spouse's sub-chain follows immediately after them, so a
    // spouse-of-spouse ends up ADJACENT to their partner (a plain bar).
    const chainWalk = (anchor: Person) => {
      if (!exp.blood.has(anchor.id)) return; // in-laws' other marriages need a refocus
      const aSlot = slotOf.get(anchor.id)!;
      const side: 1 | -1 = aSlot >= 0 ? 1 : -1;
      const own = spousesOf(anchor.id)
        .filter((s) => !placed.has(s) && !slotOf.has(s) && s !== id)
        .map((s) => person(s))
        .filter((p): p is Person => Boolean(p))
        .sort((a, b) => Number(hasKidsWith(anchor.id, a.id)) - Number(hasKidsWith(anchor.id, b.id)));
      for (const ex of own) {
        // the anchor's children only render if the anchor's row is expanded —
        // the same window rule every other union follows
        const kids = exp.children.has(anchor.id)
          ? childrenOf(anchor.id).filter((k) => !placed.has(k) && parentsOf(k).includes(ex.id))
          : [];
        chainUnions.push({ aSlot, bSlot: takeSlot(ex, side), kids });
        chainWalk(ex);
      }
    };
    for (const u of coUnions) chainWalk(u.co);

    // — 2. each union's desired junction x (chain-local, me centre = 0) —
    const junctionX = (u: ChainUnion): number => {
      if (u.bSlot === null) return u.aSlot * COUPLE_STEP; // solo: under the parent
      if (Math.abs(u.bSlot - u.aSlot) === 1) return ((u.aSlot + u.bSlot) / 2) * COUPLE_STEP; // marriage bar midpoint
      return u.bSlot * COUPLE_STEP; // split couple: under the far partner (route end)
    };
    const withKids = chainUnions.filter((u) => u.kids.length > 0).sort((a, b) => junctionX(a) - junctionX(b));

    // — 3. forests in slot order, each pulled toward its junction but never
    //      overtaking the previous forest — order is what prevents crossings —
    const kidCards: PlacedCard[] = [];
    const forestMidOf = new Map<ChainUnion, number>();
    let prevRight = -Infinity;
    let sumOffset = 0;
    let nCore = 0;
    for (const u of withKids) {
      const blocks: Block[] = [];
      for (const k of u.kids) if (!placed.has(k)) blocks.push(descBlock(k, gen + 1)); // re-check: an earlier chain may have placed k
      if (blocks.length === 0) continue;
      const w = packRow(blocks, H_GAP);
      const anchors = blocks.map((b) => b.anchor);
      const mid = (anchors[0] + anchors[anchors.length - 1]) / 2;
      const left = Math.max(junctionX(u) - mid, prevRight + H_GAP);
      for (const b of blocks) shiftBlock(b, left);
      prevRight = left + w;
      forestMidOf.set(u, left + mid);
      if (u.bSlot === null || Math.abs(u.bSlot - u.aSlot) === 1) {
        sumOffset += left + mid - junctionX(u); // core unions steer the drift
        nCore++;
      }
      kidCards.push(...blocks.flatMap((b) => b.cards));
    }
    // recentre: me + the ADJACENT partner sit over their own children; every
    // FURTHER kid-ful partner is centred over THEIR children instead (the
    // marriage line stretches, never the kids' comb), and childless partners
    // chain compactly beside the previous card.
    const drift = nCore ? sumOffset / nCore : 0;
    const relocateX = new Map<number, number>(); // slot -> x over own forest
    for (const u of withKids) {
      if (u.bSlot !== null && Math.abs(u.bSlot - u.aSlot) > 1 && forestMidOf.has(u)) {
        relocateX.set(u.bSlot, forestMidOf.get(u)! - CARD_W / 2);
      }
    }
    const slotPerson = new Map<number, string>([...slotOf.entries()].map(([pid, s]) => [s, pid]));
    const chainCards: PlacedCard[] = [{ person: me, x: -CARD_W / 2 + drift, y }];
    for (const side of [1, -1] as const) {
      let edge = side === 1 ? CARD_W / 2 + drift : -CARD_W / 2 + drift;
      for (let s = side; slotPerson.has(s); s += side) {
        const desired = relocateX.get(s);
        const x = side === 1
          ? Math.max(desired ?? -Infinity, edge + COUPLE_GAP)
          : Math.min(desired ?? Infinity, edge - COUPLE_GAP - CARD_W);
        chainCards.push({ person: person(slotPerson.get(s)!)!, x, y });
        edge = side === 1 ? x + CARD_W : x;
      }
    }

    const all = [...chainCards, ...kidCards];
    const minX = Math.min(...all.map((c) => c.x));
    for (const c of all) c.x -= minX;
    return { cards: all, width: Math.max(...all.map((c) => c.x + CARD_W)), anchor: drift - CARD_W / 2 - minX + CARD_W / 2 };
  }

  // ── ancestor pedigree (node at row `gen`, parents above). The parent couple
  //    packs at COUPLE_GAP — same spacing as every other couple — and only widens
  //    when their own ancestor trees force it. The block RESERVES width for this
  //    level's collateral children too (my siblings and half-siblings, laid as
  //    full descendant subtrees fanning to my outward `side`), so aunts/uncles
  //    are never grafted into leftover space with long sweeping combs. ──
  function ancBlock(id: string, gen: number, side: 1 | -1): Block {
    placed.add(id);
    const me = person(id)!;
    const y = gen * ROW;
    const parentIds = exp.parents.has(id) ? parentsOf(id).slice(0, 2).filter((p) => !placed.has(p)) : [];
    if (parentIds.length === 0) return { cards: [{ person: me, x: 0, y }], width: CARD_W, anchor: CARD_W / 2 };

    const pBlocks = parentIds.map((p, i) =>
      ancBlock(p, gen - 1, parentIds.length === 2 ? (i === 0 ? -1 : 1) : side));
    // extra spouses of each parent chain on that parent's outer side — the
    // remarriage becomes an adjacent bar instead of a route sweeping under the
    // other side of the pedigree
    if (parentIds.length === 2) {
      pBlocks[0] = chainExtras(pBlocks[0], extrasOf(person(parentIds[0])!, parentIds[1]), -1, y - ROW);
      pBlocks[1] = chainExtras(pBlocks[1], extrasOf(person(parentIds[1])!, parentIds[0]), 1, y - ROW);
    } else {
      pBlocks[0] = chainExtras(pBlocks[0], extrasOf(person(parentIds[0])!, id), side, y - ROW);
    }
    const packW = packRow(pBlocks, COUPLE_GAP);
    const anchors = pBlocks.map((b) => b.anchor);
    let mid = (anchors[0] + anchors[anchors.length - 1]) / 2;
    let nodeLeft = mid - CARD_W / 2;
    if (nodeLeft < 0) {
      pBlocks.forEach((b) => shiftBlock(b, -nodeLeft));
      mid -= nodeLeft;
      nodeLeft = 0;
    }
    // NOTE: this level's collateral children (aunts/uncles and their families)
    // are NOT reserved inside the block — their subtrees descend into the same
    // rows as the central band, which is positioned independently, so reserving
    // them here collides with it. The extended pass grafts them outward against
    // the real canvas instead: wider, but never overlapping.
    const cards = [{ person: me, x: nodeLeft, y }, ...pBlocks.flatMap((b) => b.cards)];
    return { cards, width: Math.max(nodeLeft + CARD_W, packW), anchor: nodeLeft + CARD_W / 2 };
  }

  // ── tidy core: focus + full siblings under the parent couple, pedigree above,
  //    half-siblings under their own couple. Parents come father-first from the
  //    graph, and the sibling band is in birth order with the focus in their own
  //    birth slot — not appended at the end. ──
  const parentRow = exp.parents.has(focusId) ? parentsOf(focusId).slice(0, 2) : [];
  const [A, B] = parentRow;

  const leftHalf: string[] = [];
  const central: string[] = [];
  const rightHalf: string[] = [];
  let Q: string | undefined;
  let R: string | undefined;
  let centralKids = [focusId];
  if (exp.siblings.has(focusId) && parentRow.length > 0) {
    const aKids = A ? childrenOf(A) : [];
    const bKids = B ? childrenOf(B) : [];
    const aSet = new Set(aKids);
    const bSet = new Set(bKids);
    const ordered = [...aKids, ...bKids.filter((k) => !aSet.has(k))];
    // With a single recorded parent, a sibling with the same (absent) co-parent
    // is a FULL sibling and belongs in the central birth-order band, not off to
    // one side like a half-sibling.
    const focusCo = A && !B ? parentsOf(focusId).find((p) => p !== A) ?? '' : null;
    for (const cid of ordered) {
      if (cid === focusId) continue;
      const inA = aSet.has(cid);
      if (inA && bSet.has(cid)) central.push(cid);
      else if (inA && focusCo !== null && (parentsOf(cid).find((p) => p !== A) ?? '') === focusCo) central.push(cid);
      else if (inA) leftHalf.push(cid);
      else rightHalf.push(cid);
    }
    if (leftHalf.length) Q = parentsOf(leftHalf[0]).find((p) => p !== A);
    if (rightHalf.length) R = parentsOf(rightHalf[0]).find((p) => p !== B);
    centralKids = ordered.filter((cid) => cid === focusId || central.includes(cid));
    if (!centralKids.includes(focusId)) centralKids.push(focusId);
  }

  const centralBlocks: Block[] = [];
  for (const cid of centralKids) if (!placed.has(cid)) centralBlocks.push(descBlock(cid, 0));
  packRow(centralBlocks, H_GAP);
  const cxs = centralBlocks.map((b) => b.anchor);
  const centralMid = (Math.min(...cxs) + Math.max(...cxs)) / 2;
  let cards: PlacedCard[] = centralBlocks.flatMap((b) => b.cards);

  if (parentRow.length > 0) {
    const anchors = new Map<string, number>();
    const upBlocks: Block[] = [];

    const centralPids = [A, B].filter((x): x is string => Boolean(x));
    const cBlocks = centralPids.map((pid, i) =>
      ancBlock(pid, -1, centralPids.length === 2 ? (i === 0 ? -1 : 1) : 1));
    if (centralPids.length === 2) {
      cBlocks[0] = chainExtras(cBlocks[0], extrasOf(person(centralPids[0])!, centralPids[1]), -1, -ROW);
      cBlocks[1] = chainExtras(cBlocks[1], extrasOf(person(centralPids[1])!, centralPids[0]), 1, -ROW);
    } else {
      cBlocks[0] = chainExtras(cBlocks[0], extrasOf(person(centralPids[0])!, focusId), 1, -ROW);
    }
    packRow(cBlocks, COUPLE_GAP);
    const cMid = cBlocks.length === 2 ? (cBlocks[0].anchor + cBlocks[1].anchor) / 2 : cBlocks[0].anchor;
    cBlocks.forEach((b) => shiftBlock(b, centralMid - cMid));
    centralPids.forEach((pid, i) => anchors.set(pid, cBlocks[i].anchor));
    upBlocks.push(...cBlocks);

    const buildSide = (partnerId: string | undefined, baseId: string, dir: 1 | -1, kids: string[]) => {
      if (kids.length === 0) return;
      const baseCenter = anchors.get(baseId)!;
      const obstacles = [...cards, ...upBlocks.flatMap((b) => b.cards)].filter((c) => c.person.id !== baseId);
      let unionMid = baseCenter;
      if (partnerId) {
        // the co-parent may already be on canvas (chained beside the base as an
        // extra spouse) — reuse that card instead of placing a duplicate
        const existing = [...cards, ...upBlocks.flatMap((b) => b.cards)].find((c) => c.person.id === partnerId);
        if (existing) {
          anchors.set(partnerId, existing.x + CARD_W / 2);
          unionMid = (baseCenter + existing.x + CARD_W / 2) / 2;
        } else {
          const partnerBlock = ancBlock(partnerId, -1, dir);
          shiftBlock(partnerBlock, baseCenter + dir * COUPLE_STEP - partnerBlock.anchor);
          shiftBlock(partnerBlock, clearShift(partnerBlock.cards, obstacles, dir, H_GAP));
          anchors.set(partnerId, partnerBlock.anchor);
          upBlocks.push(partnerBlock);
          unionMid = (baseCenter + partnerBlock.anchor) / 2;
        }
      }
      const kidBlocks: Block[] = [];
      for (const k of kids) if (!placed.has(k)) kidBlocks.push(descBlock(k, 0));
      if (kidBlocks.length === 0) return;
      packRow(kidBlocks, H_GAP);
      const ks = kidBlocks.map((b) => b.anchor);
      const kMid = (Math.min(...ks) + Math.max(...ks)) / 2;
      kidBlocks.forEach((b) => shiftBlock(b, unionMid - kMid));
      // Half-siblings clear the WHOLE central band, not just the nearest card:
      // parked inside it, their comb stubs would cross the full siblings' bar.
      const forest = kidBlocks.flatMap((b) => b.cards);
      const band = obstacles.filter((c) => c.y === 0);
      if (band.length) {
        const bandEdge = dir === -1 ? Math.min(...band.map((c) => c.x)) : Math.max(...band.map((c) => c.x + CARD_W));
        const row0 = forest.filter((c) => c.y === 0);
        const jump = dir === -1
          ? Math.min(0, bandEdge - H_GAP - Math.max(...row0.map((c) => c.x + CARD_W)))
          : Math.max(0, bandEdge + H_GAP - Math.min(...row0.map((c) => c.x)));
        if (jump !== 0) kidBlocks.forEach((b) => shiftBlock(b, jump));
      }
      // The couple stays rigid at COUPLE_STEP: a blocked kid group slides aside on
      // its own and the connector elbows over — never a stretched marriage bar.
      const extra = clearShift(forest, obstacles, dir, H_GAP);
      if (extra !== 0) kidBlocks.forEach((b) => shiftBlock(b, extra));
      cards = [...cards, ...forest];
    };
    if (A) buildSide(Q, A, -1, leftHalf);
    if (B) buildSide(R, B, 1, rightHalf);

    cards = [...cards, ...upBlocks.flatMap((b) => b.cards)];
  }

  // ── extended family: every shown person's spouses (always) and, when their
  //    children are expanded, their not-yet-placed descendants — each laid as a
  //    reserved-width subtree and rigid-shifted as one unit to clear what's
  //    already placed. Placement anchors mirror the connector rule exactly:
  //    children centre under the marriage bar when the couple is adjacent, or
  //    under this parent's card when it is not. ──
  const pos = new Map<string, PlacedCard>();
  for (const c of cards) pos.set(c.person.id, c);
  const focusCenterX = (pos.get(focusId)?.x ?? 0) + CARD_W / 2;

  // Shared adjacency test — the SAME rule decides card placement and, later,
  // whether a marriage bar is drawn and where the child trunk starts.
  const adjacentCouple = (a: PlacedCard, b: PlacedCard): boolean => {
    if (a.y !== b.y) return false;
    const [L, Rt] = a.x < b.x ? [a, b] : [b, a];
    return !cards.some((c) => c !== L && c !== Rt && c.y === L.y && c.x + CARD_W > L.x + CARD_W && c.x < Rt.x);
  };

  // Place a moving set of cards with the SMALLEST shift that clears `cards` —
  // trying both directions so a grafted family lands on its nearer side instead
  // of always being shoved around the whole central cone.
  const nearestClear = (moving: PlacedCard[]): number => {
    const r = clearShift(moving, cards, 1, H_GAP);
    const l = clearShift(moving, cards, -1, H_GAP);
    return Math.abs(l) < Math.abs(r) ? l : r;
  };

  const queue = cards.map((c) => c.person.id);
  for (let qi = 0; qi < queue.length && cards.length < CARD_CAP; qi++) {
    const pid = queue[qi];
    const pcard = pos.get(pid);
    if (!pcard) continue;
    const genP = Math.round(pcard.y / ROW);
    const dir: 1 | -1 = pcard.x + CARD_W / 2 < focusCenterX ? -1 : 1;

    const graftCard = (p: Person, side: 1 | -1): PlacedCard => {
      const cp: PlacedCard = { person: p, x: pcard.x + side * COUPLE_STEP, y: pcard.y };
      cp.x += nearestClear([cp]);
      cards.push(cp); pos.set(p.id, cp); placed.add(p.id); queue.push(p.id);
      return cp;
    };
    // Grafted partners keep the sex rule where possible: a husband lands on the
    // left, a wife on the right; unknown sexes fall back to the outward side.
    const sideFor = (p: Person, fallback: 1 | -1): 1 | -1 =>
      p.sex === 'male' ? -1 : p.sex === 'female' ? 1 : fallback;

    // Spouses always render beside their partner — childless couples included.
    // Newcomers stack OUTWARD past the spouses already beside the person, so the
    // whole marriage chain stays contiguous and its routed connectors only pass
    // over sibling spouse cards, never across a foreign family's trunk. Each
    // spouse takes whichever flank leaves them nearest their partner.
    const spouseSet = new Set(spousesOf(pid));
    const clusterEdge = (side: 1 | -1): number => {
      let edge = side === 1 ? pcard.x + CARD_W : pcard.x;
      for (;;) {
        const nxt = cards.find((c) =>
          c.y === pcard.y && spouseSet.has(c.person.id) &&
          (side === 1 ? c.x >= edge - 1 && c.x <= edge + H_GAP + 1 : c.x + CARD_W >= edge - H_GAP - 1 && c.x + CARD_W <= edge + 1));
        if (!nxt) return edge;
        edge = side === 1 ? nxt.x + CARD_W : nxt.x;
      }
    };
    // A spouse already sitting beside this person pins the chain: newcomers go
    // to the OPPOSITE flank, so their routes never sweep across that partner's
    // family. Without a pinned side, take whichever flank lands nearest.
    const pinned = ((): { side: 1 | -1; id: string } | null => {
      for (const c of cards) {
        if (c.y !== pcard.y || !spouseSet.has(c.person.id)) continue;
        if (Math.abs(c.x - (pcard.x + CARD_W)) <= H_GAP + 1) return { side: 1, id: c.person.id };
        if (Math.abs(pcard.x - (c.x + CARD_W)) <= H_GAP + 1) return { side: -1, id: c.person.id };
      }
      return null;
    })();
    const pinnedSide = pinned?.side ?? null;
    // only BLOOD relatives bring their spouses along — an in-law's other
    // marriages stay hidden until the tree is re-focused on them
    const graftSpouseIds = exp.blood.has(pid)
      ? [...spousesOf(pid)].sort((a, b) => Number(hasKidsWith(pid, a)) - Number(hasKidsWith(pid, b))) // childless nearest
      : [];
    for (const sId of graftSpouseIds) {
      if (placed.has(sId) || cards.length >= CARD_CAP) continue;
      const sp = person(sId);
      if (!sp) continue;
      const candidate = (side: 1 | -1): PlacedCard => {
        const edge = clusterEdge(side);
        const cp: PlacedCard = { person: sp, x: side === 1 ? edge + COUPLE_GAP : edge - COUPLE_GAP - CARD_W, y: pcard.y };
        cp.x += clearShift([cp], cards, side, COUPLE_GAP); // outward-only: stay on this flank
        return cp;
      };
      let chosen: PlacedCard;
      if (pinnedSide !== null) chosen = candidate(pinnedSide === 1 ? -1 : 1);
      else {
        const cR = candidate(1);
        const cL = candidate(-1);
        const pc = pcard.x + CARD_W / 2;
        const dL = Math.abs(cL.x + CARD_W / 2 - pc);
        const dR = Math.abs(cR.x + CARD_W / 2 - pc);
        chosen = Math.abs(dL - dR) < 1 ? (sideFor(sp, dir) === -1 ? cL : cR) : dL < dR ? cL : cR;
      }
      cards.push(chosen); pos.set(sp.id, chosen); placed.add(sp.id); queue.push(sp.id);
    }

    if (!exp.children.has(pid)) continue;

    const byCo = new Map<string, string[]>();
    for (const k of childrenOf(pid)) {
      if (placed.has(k)) continue;
      const coId = parentsOf(k).find((p) => p !== pid) ?? '';
      (byCo.get(coId) ?? byCo.set(coId, []).get(coId)!).push(k);
    }

    for (const [coId, kids] of byCo) {
      const coPerson = coId ? person(coId) : undefined;
      let coCard = coId ? pos.get(coId) : undefined;
      if (coPerson && !coCard && cards.length < CARD_CAP) {
        coCard = graftCard(coPerson, sideFor(coPerson, dir));
      }

      const unplacedKids = kids.filter((k) => !placed.has(k));
      if (unplacedKids.length === 0) continue;

      const commit = (forest: PlacedCard[], sh: number) => {
        for (const c of forest) {
          c.x += sh;
          cards.push(c); pos.set(c.person.id, c); placed.add(c.person.id); queue.push(c.person.id);
        }
      };

      // An in-law co-parent with no ties of their own moves
      // OVER the children once they're placed — the marriage line stretches
      // across the row, the children hang straight down from their parent.
      const settleCoParent = (coC: PlacedCard | undefined, forest: PlacedCard[]) => {
        if (!coC || exp.blood.has(coC.person.id) || adjacentCouple(pcard, coC)) return;
        const tops = forest.filter((c) => c.y === (genP + 1) * ROW);
        if (tops.length === 0) return;
        const mid = tops.reduce((s, c) => s + c.x + CARD_W / 2, 0) / tops.length;
        const others = cards.filter((c) => c !== coC && c.y === coC.y);
        const probe: PlacedCard = { person: coC.person, x: mid - CARD_W / 2, y: coC.y };
        const r = clearShift([probe], others, 1, COUPLE_GAP);
        const l = clearShift([probe], others, -1, COUPLE_GAP);
        coC.x = probe.x + (Math.abs(l) < Math.abs(r) ? l : r);
      };

      // Children of this union already on canvas (e.g. the lineal parent placed
      // by the pedigree): new siblings go OUTWARD from the focus corridor — the
      // father's siblings fan left, the mother's fan right — so this union's
      // comb only spans its own flank and NEVER sweeps across the other side's
      // trunks. Crossing-free lines take precedence over strict birth order
      // here; within the group the birth order is kept.
      const placedSibs = childrenOf(pid)
        .filter((k) => pos.has(k) && (parentsOf(k).find((p) => p !== pid) ?? '') === coId)
        .map((k) => pos.get(k)!);
      if (placedSibs.length > 0) {
        const leftEdge = Math.min(...placedSibs.map((s) => s.x));
        const rightEdge = Math.max(...placedSibs.map((s) => s.x)) + CARD_W;
        // "Outward" is measured against the pivot's OWN partner (the father
        // sits left of the mother, so his siblings fan left) — only a pivot
        // with no placed partner falls back to the focus corridor.
        const pivot = placedSibs.find((s) => spousesOf(s.person.id).some((x) => pos.has(x))) ?? placedSibs[0];
        const partnerCards = spousesOf(pivot.person.id).map((s) => pos.get(s)).filter((c): c is PlacedCard => Boolean(c));
        const side: 1 | -1 = partnerCards.length
          ? (pivot.x < partnerCards.reduce((s, c) => s + c.x, 0) / partnerCards.length ? -1 : 1)
          : (leftEdge + rightEdge) / 2 < focusCenterX ? -1 : 1;
        const blocks: Block[] = [];
        for (const k of unplacedKids) if (!placed.has(k)) blocks.push(descBlock(k, genP + 1));
        if (blocks.length === 0) continue;
        const w = packRow(blocks, H_GAP);
        const forest = blocks.flatMap((b) => b.cards);
        const target = side === -1 ? leftEdge - H_GAP - w : rightEdge + H_GAP;
        for (const c of forest) c.x += target;
        // outward-only clearing keeps the whole group on its own flank
        commit(forest, clearShift(forest, cards, side, H_GAP));
        settleCoParent(coId ? pos.get(coId) : undefined, forest);
        continue;
      }

      // A fresh family: placement anchor mirrors the connector rule — the bar
      // midpoint when the couple is adjacent, else this parent's card centre.
      const adjacent = !!coCard && adjacentCouple(pcard, coCard);
      const midX = adjacent ? (pcard.x + coCard!.x) / 2 + CARD_W / 2 : pcard.x + CARD_W / 2;
      const blocks: Block[] = [];
      for (const k of unplacedKids) if (!placed.has(k)) blocks.push(descBlock(k, genP + 1));
      if (blocks.length === 0) continue;
      packRow(blocks, H_GAP);
      const anchors = blocks.map((b) => b.anchor);
      const kmid = (anchors[0] + anchors[anchors.length - 1]) / 2;
      blocks.forEach((b) => shiftBlock(b, midX - kmid));
      // One shift for the WHOLE packed forest so siblings keep their spacing and
      // clear as a unit — shifting each block on its own lets them stack. If the
      // person has a partner pinned beside them and these children belong to a
      // DIFFERENT union, the forest clears outward, away from that partner —
      // otherwise its comb would sweep across the pinned couple's trunk.
      const forest = blocks.flatMap((b) => b.cards);
      const sh = pinned !== null && pinned.id !== coId
        ? clearShift(forest, cards, pinned.side === 1 ? -1 : 1, H_GAP)
        : nearestClear(forest);
      commit(forest, sh);
      settleCoParent(coCard, forest);
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

  // One junction per union. An adjacent couple gets a straight marriage bar and
  // the trunk drops from its midpoint. A SPLIT couple on the same row (extra
  // spouses, a partner shown with their own blood family) gets a ROUTED marriage
  // connector — out of the card side, dipping under the cards between them, and
  // into the partner (the multi-marriage elbow); their children hang
  // from a junction ON that route. Only co-parents with no spouse record at all
  // fall back to hanging the children from the nearest parent's card bottom.
  interface Comb { jx: number; jy: number; krow: number; kidXs: number[] }
  const combs: Comb[] = [];
  interface Route { L: PlacedCard; R: PlacedCard; kids: PlacedCard[]; lvl: number }
  const routesByRow = new Map<number, Route[]>();
  const ROUTE_OFF = 10; // stub length out of a card edge before the route dips

  const pushCombs = (junction: Point, kids: PlacedCard[]) => {
    const kidsByRow = new Map<number, PlacedCard[]>();
    for (const k of kids) (kidsByRow.get(k.y) ?? kidsByRow.set(k.y, []).get(k.y)!).push(k);
    for (const [krow, ks] of kidsByRow) {
      combs.push({ jx: junction.x, jy: junction.y, krow, kidXs: ks.map((k) => k.x + CARD_W / 2) });
    }
  };

  interface BarUnion { L: PlacedCard; R: PlacedCard; kids: PlacedCard[] }
  const barUnions: BarUnion[] = [];
  const soloJunctions: { p: PlacedCard; kids: PlacedCard[] }[] = [];
  for (const u of unionsByKey.values()) {
    const ps = u.parents;
    if (ps.length === 0) continue;
    if (ps.length === 2 && adjacentCouple(ps[0], ps[1])) {
      const [L, Rt] = ps[0].x < ps[1].x ? [ps[0], ps[1]] : [ps[1], ps[0]];
      links.push([{ x: L.x + CARD_W, y: L.y + CARD_H / 2 }, { x: Rt.x, y: Rt.y + CARD_H / 2 }]);
      if (u.kids.length) barUnions.push({ L, R: Rt, kids: u.kids }); // junction placed after routes are known
      continue;
    }
    if (ps.length === 2 && ps[0].y === ps[1].y && spousesOf(ps[0].person.id).includes(ps[1].person.id)) {
      const [L, Rt] = ps[0].x < ps[1].x ? [ps[0], ps[1]] : [ps[1], ps[0]];
      const route: Route = { L, R: Rt, kids: u.kids, lvl: 0 };
      (routesByRow.get(L.y) ?? routesByRow.set(L.y, []).get(L.y)!).push(route);
      continue; // drawn below, once stacking levels are known
    }
    if (u.kids.length === 0) continue;
    const kmid = u.kids.reduce((s, k) => s + k.x + CARD_W / 2, 0) / u.kids.length;
    const near = ps.reduce((best, p) => {
      const d = Math.abs(p.x + CARD_W / 2 - kmid);
      const bd = Math.abs(best.x + CARD_W / 2 - kmid);
      return d < bd || (d === bd && p.x < best.x) ? p : best;
    });
    soloJunctions.push({ p: near, kids: u.kids });
  }

  // Routes live in the UPPER band of the row gap (child bars use the lower band,
  // so the two can never collide). Nested routes stack narrow-shallow / wide-deep:
  // an inner route's side stub then never reaches an outer route's horizontal,
  // and stubs sharing an anchor card run collinear instead of crossing.
  for (const [rowY, list] of routesByRow) {
    const span = (r: Route): [number, number] => [r.L.x + CARD_W, r.R.x];
    const overlaps = (a: Route, b: Route) => span(a)[0] < span(b)[1] && span(b)[0] < span(a)[1];
    // Partners of the SAME person share one lane: their routes overlap into a
    // single visible line running under the cards between them, with a rise
    // into each partner — never a separate nested lane per marriage.
    const uses = new Map<string, number>();
    for (const r of list) for (const c of [r.L, r.R]) uses.set(c.person.id, (uses.get(c.person.id) ?? 0) + 1);
    const groupOf = (r: Route): string =>
      (uses.get(r.L.person.id) ?? 0) >= (uses.get(r.R.person.id) ?? 0) ? `${r.L.person.id}|R` : `${r.R.person.id}|L`;
    const groups = new Map<string, Route[]>();
    for (const r of list) {
      const key = groupOf(r);
      (groups.get(key) ?? groups.set(key, []).get(key)!).push(r);
    }
    const gspan = (rs: Route[]): [number, number] =>
      [Math.min(...rs.map((r) => span(r)[0])), Math.max(...rs.map((r) => span(r)[1]))];
    const assigned: Route[][] = [];
    for (const rs of [...groups.values()].sort((a, b) =>
      (gspan(a)[1] - gspan(a)[0]) - (gspan(b)[1] - gspan(b)[0]) || gspan(a)[0] - gspan(b)[0])) {
      const [s, e] = gspan(rs);
      const used = assigned
        .filter((o) => { const [os, oe] = gspan(o); return s < oe && os < e; })
        .map((o) => o[0].lvl);
      let lvl = 0;
      while (used.includes(lvl) && lvl < 3) lvl++;
      rs.forEach((r) => (r.lvl = lvl));
      assigned.push(rs);
    }
    for (const r of list) {
      const midY = rowY + CARD_H / 2;
      const dy = rowY + CARD_H + 10 + r.lvl * BAR_LIFT;
      links.push([
        { x: r.L.x + CARD_W, y: midY },
        { x: r.L.x + CARD_W + ROUTE_OFF, y: midY },
        { x: r.L.x + CARD_W + ROUTE_OFF, y: dy },
        { x: r.R.x - ROUTE_OFF, y: dy },
        { x: r.R.x - ROUTE_OFF, y: midY },
        { x: r.R.x, y: midY },
      ]);
      if (r.kids.length) {
        const kmid = r.kids.reduce((s, k) => s + k.x + CARD_W / 2, 0) / r.kids.length;
        const leftEnd = r.L.x + CARD_W + ROUTE_OFF;
        const rightEnd = r.R.x - ROUTE_OFF;
        let jx = Math.min(Math.max(kmid, leftEnd), rightEnd);
        // if a DEEPER route runs beneath this one, the trunk would cut it —
        // snap the junction to a span end instead: at a shared anchor the
        // trunk then runs collinear with the outer route's stub, not across it
        const deeper = list.filter((o) => o !== r && o.lvl > r.lvl && overlaps(o, r));
        const cut = (x: number) => deeper.some((o) => x > span(o)[0] + ROUTE_OFF + 0.5 && x < span(o)[1] - ROUTE_OFF - 0.5);
        if (cut(jx)) jx = !cut(rightEnd) ? rightEnd : !cut(leftEnd) ? leftEnd : jx;
        pushCombs({ x: jx, y: dy }, r.kids);
      }
    }
  }

  // Adjacent-couple and single-parent junctions, placed AFTER routes so the
  // trunk can dodge any route lane running beneath: it snaps beside the card
  // edge, outside the routes' rise stubs, instead of cutting their horizontals.
  const routeCut = (rowY: number, x: number) =>
    (routesByRow.get(rowY) ?? []).some((r) => x > r.L.x + CARD_W + ROUTE_OFF + 0.5 && x < r.R.x - ROUTE_OFF - 0.5);
  for (const b of barUnions) {
    let jx = (b.L.x + b.R.x) / 2 + CARD_W / 2;
    if (routeCut(b.L.y, jx)) {
      const candR = b.R.x - ROUTE_OFF + 4;
      const candL = b.L.x + CARD_W + ROUTE_OFF - 4;
      jx = !routeCut(b.L.y, candR) ? candR : !routeCut(b.L.y, candL) ? candL : jx;
    }
    pushCombs({ x: jx, y: b.L.y + CARD_H / 2 }, b.kids);
  }
  for (const s of soloJunctions) {
    let jx = s.p.x + CARD_W / 2;
    if (routeCut(s.p.y, jx)) {
      const candR = s.p.x + CARD_W - 4;
      const candL = s.p.x + 4;
      jx = !routeCut(s.p.y, candR) ? candR : !routeCut(s.p.y, candL) ? candL : jx;
    }
    pushCombs({ x: jx, y: s.p.y + CARD_H }, s.kids);
  }

  // Combs that share a child row get de-conflicted bar heights: overlapping spans
  // step DOWNWARD by BAR_LIFT (toward the children, away from the route band) so
  // two families' children never share one ambiguous horizontal line.
  const combRows = new Map<number, Comb[]>();
  for (const c of combs) (combRows.get(c.krow) ?? combRows.set(c.krow, []).get(c.krow)!).push(c);
  for (const [krow, list] of combRows) {
    const span = (c: Comb): [number, number] => [Math.min(c.jx, ...c.kidXs), Math.max(c.jx, ...c.kidXs)];
    const inSpan = (c: Comb, x: number) => x > span(c)[0] + 0.5 && x < span(c)[1] - 0.5;
    // narrow combs first: an inner family keeps the shallow lane, a wide sweep
    // dives beneath it, so trunks meet as few foreign bars as possible. A comb
    // that would cross a foreign trunk prefers a lane BELOW that comb's bar.
    list.sort((a, b) => (span(a)[1] - span(a)[0]) - (span(b)[1] - span(b)[0]) || span(a)[0] - span(b)[0]);
    const taken: { d: number; s: number; e: number; c: Comb }[] = [];
    for (const c of list) {
      const [s, e] = span(c);
      let d = 0;
      for (;;) {
        const clash = taken.some((t) =>
          // same lane, overlapping span → merge/ambiguity
          (t.d === d && s < t.e + H_GAP / 2 && e > t.s - H_GAP / 2) ||
          // my bar would cut a placed comb's trunk (their trunk spans down TO
          // their bar; staying at or below their lane clears it… only above cuts)
          (t.d > d && inSpan(c, t.c.jx)) ||
          // my bar would cut a placed comb's kid stubs (they fall FROM their bar)
          (t.d < d && t.c.kidXs.some((x) => inSpan(c, x))));
        if (!clash || d >= 3) break;
        d++;
      }
      taken.push({ d, s, e, c });
      const bar = krow - ROW_GAP / 2 + d * BAR_LIFT;
      // ONE shared bus per union: a single trunk, arms with rounded elbows to
      // the two outermost children only, and plain stubs for the ones between —
      // never a separate full line per child piling up in the same corridor.
      const xs = [...c.kidXs].sort((p, q) => p - q);
      const lo = xs[0];
      const hi = xs[xs.length - 1];
      const loArm = lo < c.jx - 0.5;
      const hiArm = hi > c.jx + 0.5;
      if (loArm) links.push([{ x: c.jx, y: c.jy }, { x: c.jx, y: bar }, { x: lo, y: bar }, { x: lo, y: krow }]);
      if (hiArm) links.push([{ x: c.jx, y: c.jy }, { x: c.jx, y: bar }, { x: hi, y: bar }, { x: hi, y: krow }]);
      if (!loArm && !hiArm) links.push([{ x: c.jx, y: c.jy }, { x: c.jx, y: bar }, { x: lo, y: bar }, { x: lo, y: krow }]);
      for (const x of xs) {
        if ((x === lo && loArm) || (x === hi && hiArm) || (!loArm && !hiArm && x === lo)) continue;
        links.push([{ x, y: bar }, { x, y: krow }]); // middle child: stub off the bus
      }
    }
  }

  // ── crossing hops: dense multi-marriage rows (and unmerged duplicate people)
  //    can make a handful of crossings geometrically unavoidable. Wherever a
  //    horizontal run must cross ANOTHER connector's vertical, it hops over it
  //    with a small arc, so it always stays readable which family a line
  //    belongs to. Placement rules above keep these rare. ──
  interface VSeg { x: number; y1: number; y2: number; li: number }
  const vsegs: VSeg[] = [];
  links.forEach((l, li) => {
    for (let i = 1; i < l.length; i++) {
      const a = l[i - 1], b = l[i];
      if (a.x === b.x && a.y !== b.y) vsegs.push({ x: a.x, y1: Math.min(a.y, b.y), y2: Math.max(a.y, b.y), li });
    }
  });
  links.forEach((l, li) => {
    for (let i = 1; i < l.length; i++) {
      const a = l[i - 1], b = l[i];
      if (a.y !== b.y || a.x === b.x) continue;
      const [x1, x2] = a.x < b.x ? [a.x, b.x] : [b.x, a.x];
      const hopXs = [...new Set(
        vsegs
          .filter((v) => v.li !== li && v.x > x1 + 0.5 && v.x < x2 - 0.5 && a.y > v.y1 + 0.5 && a.y < v.y2 - 0.5)
          .map((v) => v.x),
      )].sort((p, q) => (a.x < b.x ? p - q : q - p));
      if (hopXs.length === 0) continue;
      l.splice(i, 0, ...hopXs.map((x) => ({ x, y: a.y, hop: true })));
      i += hopXs.length;
    }
  });

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
