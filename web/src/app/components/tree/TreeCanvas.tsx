import { useEffect, useMemo, useRef, useState, type PointerEvent, type WheelEvent } from 'react';
import type { Person } from '@core/models';
import type { FamilyGraph } from '@core/tree/graph';
import { buildTreeLayout, seedExpansion, CARD_W, CARD_H, type Connector } from '@core/tree/layout';

// Build an SVG path for a polyline, rounding each interior corner so the
// connectors read as smooth elbows. Points flagged `hop` mark where
// this horizontal run crosses another connector's vertical: the path jumps it
// with a small arc, so it stays unambiguous which family each line belongs to.
function roundedPath(points: Connector, radius = 12): string {
  const pts = points.filter((p, i) => i === 0 || p.x !== points[i - 1].x || p.y !== points[i - 1].y);
  if (pts.length < 2) return '';
  let d = `M ${pts[0].x} ${pts[0].y}`;
  for (let i = 1; i < pts.length - 1; i++) {
    const prev = pts[i - 1];
    const cur = pts[i];
    const next = pts[i + 1];
    if (cur.hop) {
      const dirX = Math.sign(next.x - prev.x) || 1;
      const hr = Math.min(7, Math.abs(cur.x - prev.x) / 2, Math.abs(next.x - cur.x) / 2);
      d += ` L ${cur.x - dirX * hr} ${cur.y} A ${hr} ${hr} 0 0 ${dirX === 1 ? 1 : 0} ${cur.x + dirX * hr} ${cur.y}`;
      continue;
    }
    const d1 = Math.hypot(cur.x - prev.x, cur.y - prev.y);
    const d2 = Math.hypot(next.x - cur.x, next.y - cur.y);
    const r = Math.min(radius, d1 / 2, d2 / 2);
    const a = { x: cur.x + ((prev.x - cur.x) / d1) * r, y: cur.y + ((prev.y - cur.y) / d1) * r };
    const b = { x: cur.x + ((next.x - cur.x) / d2) * r, y: cur.y + ((next.y - cur.y) / d2) * r };
    d += ` L ${a.x} ${a.y} Q ${cur.x} ${cur.y} ${b.x} ${b.y}`;
  }
  const last = pts[pts.length - 1];
  d += ` L ${last.x} ${last.y}`;
  return d;
}

const GENDER_FILL: Record<Person['sex'], string> = {
  male: '#5cc6e0',
  female: '#ef93b3',
  unknown: '#aab4be',
};

function initials(p: Person): string {
  return [p.given_name?.[0], p.surname?.[0]].filter(Boolean).join('').toUpperCase() || '?';
}

// A card only ever opens the person in the left sidebar — it never moves the
// tree. Re-centring is reserved for the Parents button (see below).
function GenCard({
  person,
  isFocus,
  isSelected,
  onOpen,
}: {
  person: Person;
  isFocus: boolean;
  isSelected: boolean;
  onOpen: () => void;
}) {
  return (
    <div
      role="button"
      tabIndex={0}
      onClick={onOpen}
      onKeyDown={(e) => {
        if (e.key === 'Enter' || e.key === ' ') onOpen();
      }}
      data-sex={person.sex}
      data-focus={isFocus || undefined}
      data-selected={isSelected || undefined}
      className="gen-card cursor-pointer"
    >
      {person.avatar_url ? (
        <img src={person.avatar_url} alt="" className="gen-card-avatar object-cover" />
      ) : (
        <span className="gen-card-avatar" style={{ background: GENDER_FILL[person.sex] }}>
          {initials(person)}
        </span>
      )}
      <span className="min-w-0 flex-1">
        <span className="gen-card-name">{person.display_name}</span>
        {person.life_span && <span className="gen-card-dates">{person.life_span}</span>}
      </span>
      <svg viewBox="0 0 24 24" fill="none" className="gen-card-edit h-3.5 w-3.5">
        <path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round" />
      </svg>
    </div>
  );
}

export function TreeCanvas({
  graph,
  focusId,
  selectedId,
  onSelect,
  onFocus,
  onAddRelative,
}: {
  graph: FamilyGraph;
  focusId: string;
  selectedId: string | null;
  onSelect: (personId: string) => void; // clicking a card only opens it in the left sidebar
  onFocus: (personId: string) => void; // the Parents button re-centres the tree on that person
  onAddRelative: (personId: string) => void; // open the radial add-relative chooser
}) {
  const [generations, setGenerations] = useState(2);
  const [descendants, setDescendants] = useState(1);
  const [view, setView] = useState({ scale: 1, x: 0, y: 0 });
  const [find, setFind] = useState('');
  const drag = useRef<{ x: number; y: number; ox: number; oy: number } | null>(null);
  const boardRef = useRef<HTMLDivElement>(null);
  const centeredFor = useRef<string | null>(null);

  // Expansion is fully derived from the focus person and the two steppers, so a
  // re-root (or a stepper change) always rebuilds a fresh, consistent layout.
  const exp = useMemo(
    () => seedExpansion(graph, focusId, generations, descendants),
    [graph, focusId, generations, descendants],
  );
  const layout = useMemo(() => buildTreeLayout(graph, focusId, exp), [graph, focusId, exp]);

  // When the tree is re-centred on a new person (via a Parents button, a find
  // result, or a panel link), pan so that person sits in the middle of the
  // viewport — keeping focus on them while their family opens around them.
  useEffect(() => {
    if (!layout || centeredFor.current === focusId) return;
    const el = boardRef.current;
    const card = layout.cards.find((c) => c.person.id === focusId);
    if (!el || !card) return;
    centeredFor.current = focusId;
    setView((v) => ({
      ...v,
      x: el.clientWidth / 2 - (card.x + CARD_W / 2) * v.scale,
      y: el.clientHeight / 2 - (card.y + CARD_H / 2) * v.scale,
    }));
  }, [layout, focusId]);

  const matches = useMemo(() => {
    const q = find.trim().toLowerCase();
    if (q.length < 2) return [];
    return [...graph.peopleById.values()].filter((p) => p.display_name.toLowerCase().includes(q)).slice(0, 6);
  }, [find, graph]);

  if (!layout) return <div className="p-8 text-neutral-500">Person not found in this tree.</div>;

  const onWheel = (e: WheelEvent) =>
    setView((v) => ({ ...v, scale: Math.min(2, Math.max(0.3, v.scale - e.deltaY * 0.0012)) }));
  const onPointerDown = (e: PointerEvent) => {
    drag.current = { x: e.clientX, y: e.clientY, ox: view.x, oy: view.y };
    (e.target as Element).setPointerCapture?.(e.pointerId);
  };
  const onPointerMove = (e: PointerEvent) => {
    const d = drag.current;
    if (!d) return;
    setView((v) => ({ ...v, x: d.ox + (e.clientX - d.x), y: d.oy + (e.clientY - d.y) }));
  };
  const endDrag = () => {
    drag.current = null;
  };
  const stepper = (label: string, value: number, set: (n: number) => void) => (
    <div className="gen-toolbar-control">
      <span>{label}</span>
      <button className="gen-toolbar-step" onClick={() => set(Math.max(1, value - 1))}>−</button>
      <span className="w-3 text-center font-medium">{value}</span>
      <button className="gen-toolbar-step" onClick={() => set(Math.min(6, value + 1))}>+</button>
    </div>
  );

  return (
    <div
      ref={boardRef}
      className="gen-board family-chart-board relative h-full w-full cursor-grab touch-none overflow-hidden active:cursor-grabbing"
      onWheel={onWheel}
      onPointerDown={onPointerDown}
      onPointerMove={onPointerMove}
      onPointerUp={endDrag}
      onPointerLeave={endDrag}
    >
      <div className="absolute left-3 top-3 z-10 w-60">
        <input
          value={find}
          onChange={(e) => setFind(e.target.value)}
          placeholder="Find a person…"
          className="w-full rounded-md border border-[#dde1e6] bg-white px-3 py-1.5 text-sm shadow-sm outline-none"
        />
        {matches.length > 0 && (
          <ul className="mt-1 overflow-hidden rounded-md border border-[#dde1e6] bg-white shadow-lg">
            {matches.map((p) => (
              <li key={p.id}>
                <button
                  className="block w-full px-3 py-2 text-left text-sm hover:bg-[#f3f7fb]"
                  onClick={() => {
                    onFocus(p.id);
                    setFind('');
                  }}
                >
                  {p.display_name}
                  {p.life_span ? <span className="text-neutral-400"> · {p.life_span}</span> : null}
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>

      <div className="absolute right-3 top-3 z-10 flex gap-2">
        {stepper('Generations', generations, setGenerations)}
        {stepper('Descendants', descendants, setDescendants)}
      </div>

      <div className="absolute bottom-3 right-3 z-10 flex gap-1">
        <button onClick={() => setView((v) => ({ ...v, scale: Math.min(2, v.scale + 0.2) }))} className="h-8 w-8 rounded-md border border-[#dde1e6] bg-white text-lg leading-none">+</button>
        <button onClick={() => setView((v) => ({ ...v, scale: Math.max(0.3, v.scale - 0.2) }))} className="h-8 w-8 rounded-md border border-[#dde1e6] bg-white text-lg leading-none">−</button>
        <button onClick={() => setView({ scale: 1, x: 0, y: 0 })} className="h-8 rounded-md border border-[#dde1e6] bg-white px-2 text-xs">Center</button>
      </div>

      <div
        className="absolute left-0 top-0 origin-top-left"
        style={{ transform: `translate(${view.x}px, ${view.y}px) scale(${view.scale})`, width: layout.width, height: layout.height }}
      >
        <svg className="absolute left-0 top-0 overflow-visible" width={layout.width} height={layout.height}>
          {layout.links.map((conn, i) => (
            <path
              key={i}
              d={roundedPath(conn)}
              fill="none"
              stroke="#c4ccd4"
              strokeWidth={2}
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          ))}
        </svg>
        {layout.cards.map((c) => {
          const id = c.person.id;
          return (
            <div
              key={id}
              className="group absolute left-0 top-0"
              // No transform transition here: the SVG connectors cannot animate
              // their shape, so cards and lines must jump together or the lines
              // visibly detach from the cards on every relayout.
              style={{
                width: CARD_W,
                height: CARD_H,
                transform: `translate(${c.x}px, ${c.y}px)`,
              }}
            >
              {/* Every person carries this control. It re-centres the tree on the
                  person and opens their wider family while keeping them in focus. */}
              <button
                type="button"
                title="Parents"
                onClick={(e) => {
                  e.stopPropagation();
                  onFocus(id);
                }}
                className="gen-dots gen-dots-top"
              >
                <span className="gen-pill" />
                <span className="gen-pill" />
              </button>
              <GenCard
                person={c.person}
                isFocus={id === focusId}
                isSelected={id === selectedId}
                onOpen={() => onSelect(id)}
              />
              <button
                type="button"
                title="Add a relative"
                onClick={(e) => {
                  e.stopPropagation();
                  onAddRelative(id);
                }}
                className="gen-add-tab"
              >
                +
              </button>
            </div>
          );
        })}
      </div>
    </div>
  );
}
