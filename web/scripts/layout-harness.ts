// @ts-nocheck — standalone dev script, run with Node's type stripping (not part of the build).
// Headless invariant harness for the tree layout. Parses the real MyHeritage
// GEDCOM export into the same FamilyGraph the SPA builds, runs buildTreeLayout
// for a spread of focus people, and checks the geometric invariants that the
// connectors and placement must satisfy. Run inside ddev:
//   ddev exec "cd web && node --experimental-strip-types scripts/layout-harness.ts"
import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { buildFamilyGraph } from '../src/core/tree/graph.ts';
import { buildTreeLayout, seedExpansion, CARD_W, CARD_H } from '../src/core/tree/layout.ts';
import type { Person, Relationship } from '../src/core/models';

const here = dirname(fileURLToPath(import.meta.url));
const gedPath = resolve(here, '../../6hf808_65310083h36xhda2a8052o_A.ged');

// ── minimal GEDCOM parse → people[] + relationships[] (parent + spouse) ──
function parseGedcom(text: string) {
  const lines = text.replace(/^﻿/, '').replace(/\r\n/g, '\n').split('\n');
  type Indi = { id: string; name: string; sex: Person['sex'] };
  const indi = new Map<string, Indi>();
  const fam = new Map<string, { husb?: string; wife?: string; chil: string[] }>();
  let cur: string | null = null;
  let curType: 'INDI' | 'FAM' | null = null;
  for (const ln of lines) {
    const head = ln.match(/^0 @([^@]+)@ (\w+)/);
    if (head) {
      const [, id, type] = head;
      if (type === 'INDI') { cur = id; curType = 'INDI'; indi.set(id, { id, name: '?', sex: 'unknown' }); }
      else if (type === 'FAM') { cur = id; curType = 'FAM'; fam.set(id, { chil: [] }); }
      else { cur = null; curType = null; }
      continue;
    }
    if (!cur) continue;
    if (curType === 'INDI') {
      const rec = indi.get(cur)!;
      const nm = ln.match(/^1 NAME (.*)/);
      if (nm) rec.name = nm[1].replace(/\//g, '').trim();
      const sx = ln.match(/^1 SEX (\w)/);
      if (sx) rec.sex = sx[1] === 'M' ? 'male' : sx[1] === 'F' ? 'female' : 'unknown';
    } else if (curType === 'FAM') {
      const f = fam.get(cur)!;
      const h = ln.match(/^1 HUSB @([^@]+)@/); if (h) f.husb = h[1];
      const w = ln.match(/^1 WIFE @([^@]+)@/); if (w) f.wife = w[1];
      const c = ln.match(/^1 CHIL @([^@]+)@/); if (c) f.chil.push(c[1]);
    }
  }

  const people: Person[] = [...indi.values()].map((p) => {
    const [given, ...rest] = p.name.split(' ');
    return {
      id: p.id,
      given_name: given ?? '',
      surname: rest.join(' '),
      display_name: p.name,
      sex: p.sex,
      life_span: '',
      avatar_url: null,
    } as unknown as Person;
  });

  const relationships: Relationship[] = [];
  let rid = 0;
  for (const f of fam.values()) {
    const parents = [f.husb, f.wife].filter(Boolean) as string[];
    if (parents.length === 2) {
      relationships.push({ id: `r${rid++}`, type: 'spouse', person_id: parents[0], related_person_id: parents[1] } as unknown as Relationship);
    }
    for (const k of f.chil) for (const p of parents) {
      relationships.push({ id: `r${rid++}`, type: 'parent', person_id: p, related_person_id: k } as unknown as Relationship);
    }
  }
  return { people, relationships, fam, indi };
}

const { people, relationships, fam } = parseGedcom(readFileSync(gedPath, 'utf8'));
const graph = buildFamilyGraph(people, relationships);
console.log(`parsed: ${people.length} people, ${relationships.length} relationships, ${fam.size} families`);

// Real union membership straight from the FAM records (ground truth, independent
// of the layout): set of {parentA|parentB} couple keys, and parent→child edges.
const coupleKeys = new Set<string>();
const childParents = new Map<string, Set<string>>(); // childId -> parent ids
for (const f of fam.values()) {
  const parents = [f.husb, f.wife].filter(Boolean) as string[];
  if (parents.length === 2) coupleKeys.add([parents[0], parents[1]].sort().join('|'));
  for (const k of f.chil) {
    const set = childParents.get(k) ?? new Set<string>();
    for (const p of parents) set.add(p);
    childParents.set(k, set);
  }
}

type Pt = { x: number; y: number };
function cardAt(cards: { person: Person; x: number; y: number }[], x: number, y: number) {
  // a card whose row matches y and whose left edge ~ x (spouse-bar endpoint anchors)
  return cards.find((c) => Math.abs(c.y + CARD_H / 2 - y) < 1 && (Math.abs(c.x - x) < 1 || Math.abs(c.x + CARD_W - x) < 1));
}

function checkFocus(focusId: string, up = 3, down = 3) {
  const exp = seedExpansion(graph, focusId, up, down);
  const layout = buildTreeLayout(graph, focusId, exp);
  if (!layout) return null;
  const { cards, links } = layout;
  const byId = new Map(cards.map((c) => [c.person.id, c]));

  // I1: card overlaps (allow shared edges; flag real overlaps > 1px)
  let overlaps = 0;
  for (let i = 0; i < cards.length; i++) for (let j = i + 1; j < cards.length; j++) {
    const a = cards[i], b = cards[j];
    if (a.x + CARD_W - 1 > b.x && b.x + CARD_W - 1 > a.x && a.y + CARD_H - 1 > b.y && b.y + CARD_H - 1 > a.y) overlaps++;
  }

  // classify connectors
  const bars = links.filter((l) => l.length === 2);
  const combs = links.filter((l) => l.length >= 4);

  // I2a: spouse bar must join two cards that are a real couple, and cross no card.
  let phantomBars = 0, barsCrossingCard = 0;
  for (const b of bars as Pt[][]) {
    const [p, q] = [b[0], b[b.length - 1]];
    const L = cardAt(cards, p.x, p.y);
    const R = cardAt(cards, q.x, q.y);
    if (!L || !R) { phantomBars++; continue; }
    if (!coupleKeys.has([L.person.id, R.person.id].sort().join('|'))) phantomBars++;
    const lo = Math.min(p.x, q.x), hi = Math.max(p.x, q.x);
    for (const c of cards) if (c.y + CARD_H / 2 === p.y && c.person.id !== L.person.id && c.person.id !== R.person.id) {
      if (c.x + CARD_W - 2 > lo && c.x + 2 < hi) { barsCrossingCard++; break; }
    }
  }

  // I2b/I3: every comb's child endpoint maps to a real child, the comb's junction
  // sits over its real parents, and — the user's actual complaint — the CHILD CARD
  // sits horizontally under its parents (not stranded across the row).
  let phantomCombs = 0, combOverStranger = 0, childNotUnderParents = 0, combsChecked = 0;
  let childOffSum = 0, childOffMax = 0, childStranded = 0, crossLink = 0; // child center vs parent-union midpoint
  let combCrossesCard = 0; // the comb's horizontal run passes over an unrelated card
  for (const comb of combs as Pt[][]) {
    const top = comb[0];            // junction (jx, jy)
    const bottom = comb[comb.length - 1]; // (childCenter, childTop)
    const child = cards.find((c) => Math.abs(c.x + CARD_W / 2 - bottom.x) < 1 && Math.abs(c.y - bottom.y) < 1);
    if (!child) { phantomCombs++; continue; }
    combsChecked++;
    const realParents = [...(childParents.get(child.person.id) ?? new Set())].filter((p) => byId.has(p));
    if (realParents.length === 0) { phantomCombs++; continue; }
    const pcards = realParents.map((p) => byId.get(p)!);
    const prow = Math.max(...pcards.map((p) => p.y));
    const lo = Math.min(...pcards.map((p) => p.x));
    const hi = Math.max(...pcards.map((p) => p.x + CARD_W));
    if (top.x < lo - 2 || top.x > hi + 2) childNotUnderParents++;
    for (const c of cards) {
      if (c.y !== prow) continue;
      if (realParents.includes(c.person.id)) continue;
      if (top.x > c.x + 2 && top.x < c.x + CARD_W - 2) { combOverStranger++; break; }
    }
    // --- the real metric: is the child under its parents? ---
    // Separate the GENUINE bug (child not under an ADJACENT couple) from the
    // inherent CROSS-LINK case (the two displayed parents live in different
    // branches, so there is no single couple to sit under).
    const childCenter = child.x + CARD_W / 2;
    const adjacentCouple = pcards.length < 2 || (pcards[0].y === pcards[1].y && Math.abs(pcards[0].x - pcards[1].x) <= CARD_W + 200);
    if (!adjacentCouple) { crossLink++; }
    else {
      const off = Math.abs(childCenter - (lo + hi) / 2);
      childOffSum += off; childOffMax = Math.max(childOffMax, off);
      if (childCenter < lo - CARD_W || childCenter > hi + CARD_W) childStranded++; // >1 card outside its couple
    }
    // the comb's horizontal run (3rd segment) — does it pass over a non-relative card?
    const barY = comb[1]?.y;
    const runLo = Math.min(top.x, childCenter), runHi = Math.max(top.x, childCenter);
    for (const c of cards) {
      if (Math.abs(c.y + CARD_H / 2 - (barY ?? -1)) > CARD_H / 2) continue;
      if (c.person.id === child.person.id || realParents.includes(c.person.id)) continue;
      if (c.x + CARD_W - 4 > runLo && c.x + 4 < runHi) { combCrossesCard++; break; }
    }
  }

  return { focusId, n: cards.length, overlaps, bars: bars.length, combs: combs.length, phantomBars, barsCrossingCard, phantomCombs, combOverStranger, childNotUnderParents, combsChecked,
    childStranded, crossLink, combCrossesCard, childOffMax: Math.round(childOffMax), childOffAvg: childStranded + combsChecked ? Math.round(childOffSum / Math.max(1, combsChecked - crossLink)) : 0 };
}

// Pick foci: the most-connected people (lots of descendants → exercises extended family),
// plus a few multi-union people.
const degree = (id: string) => (graph.childrenOf.get(id)?.length ?? 0) + (graph.parentsOf.get(id)?.length ?? 0) + (graph.spousesOf.get(id)?.length ?? 0);
const multiUnion = people.filter((p) => (graph.spousesOf.get(p.id)?.length ?? 0) >= 2).slice(0, 4).map((p) => p.id);
const topConnected = [...people].sort((a, b) => degree(b.id) - degree(a.id)).slice(0, 6).map((p) => p.id);
const foci = [...new Set([...topConnected, ...multiUnion])];

const totals = { overlaps: 0, phantomBars: 0, barsCrossingCard: 0, phantomCombs: 0, combOverStranger: 0, childStranded: 0, combCrossesCard: 0 };
for (const f of foci) {
  const r = checkFocus(f);
  if (!r) continue;
  const name = graph.peopleById.get(f)?.display_name ?? f;
  console.log(
    `focus ${f} (${name.slice(0, 20).padEnd(20)}) cards=${String(r.n).padStart(4)} overlap=${r.overlaps} ` +
    `| phantomBar=${r.phantomBars} barXcard=${r.barsCrossingCard} phantomComb=${r.phantomCombs} ` +
    `| coupleStranded=${String(r.childStranded).padStart(3)} crossLink=${String(r.crossLink).padStart(3)} combXcard=${String(r.combCrossesCard).padStart(3)} ` +
    `coupleOff(avg/max)=${r.childOffAvg}/${r.childOffMax}`,
  );
  for (const k of Object.keys(totals) as (keyof typeof totals)[]) totals[k] += (r as unknown as Record<string, number>)[k];
}
console.log('\nTOTALS', JSON.stringify(totals));
const bad = Object.values(totals).reduce((a, b) => a + b, 0);
console.log(bad === 0 ? '\n✅ ALL INVARIANTS HOLD' : `\n❌ ${bad} invariant/quality violations`);
