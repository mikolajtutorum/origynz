import { useEffect, useMemo, useRef, useState, type PointerEvent, type WheelEvent } from 'react';
import type { Person } from '@core/models';
import type { FamilyGraph } from '@core/tree/graph';
import { buildTreeLayout, seedExpansion, setCardDims, CARD_W, CARD_H, type Connector } from '@core/tree/layout';

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

const MIN_SCALE = 0.3;
const MAX_SCALE = 2.5;
const clampScale = (s: number) => Math.min(MAX_SCALE, Math.max(MIN_SCALE, s));

function initials(p: Person): string {
  return [p.given_name?.[0], p.surname?.[0]].filter(Boolean).join('').toUpperCase() || '?';
}

// A card only ever opens the person in the left sidebar — it never moves the
// tree. Re-centring is reserved for the Parents button (see below).
function GenCard({
  person,
  isFocus,
  isSelected,
  portrait,
  onOpen,
}: {
  person: Person;
  isFocus: boolean;
  isSelected: boolean;
  portrait: boolean;
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
      data-portrait={portrait || undefined}
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

interface View {
  scale: number;
  x: number;
  y: number;
}

type Gesture =
  | { mode: 'pan'; world: { x: number; y: number }; scale: number }
  | { mode: 'pinch'; world: { x: number; y: number }; startScale: number; startDist: number };

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
  const [view, setView] = useState<View>({ scale: 1, x: 0, y: 0 });
  const [find, setFind] = useState('');
  const [optionsOpen, setOptionsOpen] = useState(false);
  // Portrait (MyHeritage-style) cards on phones/tablets; wide cards on desktop.
  const [portrait, setPortrait] = useState(() => window.matchMedia('(max-width: 1023px)').matches);
  const boardRef = useRef<HTMLDivElement>(null);
  const centeredFor = useRef<string | null>(null);
  const viewRef = useRef(view);
  viewRef.current = view;

  // Multi-pointer gesture state: one finger pans, two fingers pinch-zoom
  // around their midpoint. Mouse drag pans; wheel zooms at the cursor.
  const pointers = useRef(new Map<number, { x: number; y: number }>());
  const gesture = useRef<Gesture | null>(null);
  const tapStart = useRef(new Map<number, { x: number; y: number; t: number }>());
  const lastTap = useRef<{ x: number; y: number; t: number } | null>(null);

  useEffect(() => {
    const mq = window.matchMedia('(max-width: 1023px)');
    const onChange = (e: MediaQueryListEvent) => setPortrait(e.matches);
    mq.addEventListener('change', onChange);
    return () => mq.removeEventListener('change', onChange);
  }, []);

  // Expansion is fully derived from the focus person and the two steppers, so a
  // re-root (or a stepper change) always rebuilds a fresh, consistent layout.
  const exp = useMemo(
    () => seedExpansion(graph, focusId, generations, descendants),
    [graph, focusId, generations, descendants],
  );
  const layout = useMemo(() => {
    setCardDims(portrait ? 148 : 188, portrait ? 186 : 84);
    return buildTreeLayout(graph, focusId, exp);
  }, [graph, focusId, exp, portrait]);

  const boardPoint = (e: { clientX: number; clientY: number }) => {
    const r = boardRef.current!.getBoundingClientRect();
    return { x: e.clientX - r.left, y: e.clientY - r.top };
  };

  const zoomAt = (point: { x: number; y: number }, nextScale: number) => {
    setView((v) => {
      const scale = clampScale(nextScale);
      const world = { x: (point.x - v.x) / v.scale, y: (point.y - v.y) / v.scale };
      return { scale, x: point.x - world.x * scale, y: point.y - world.y * scale };
    });
  };

  // Fit the current layout to the viewport (clamped so cards stay readable)
  // and keep the focus person in the middle — MyHeritage-style "open" view.
  const centerOnFocus = (fit = true) => {
    const el = boardRef.current;
    const card = layout?.cards.find((c) => c.person.id === focusId);
    if (!el || !layout || !card) return;
    const pad = 48;
    const fitScale = Math.min((el.clientWidth - pad) / layout.width, (el.clientHeight - pad) / layout.height);
    const scale = fit ? Math.min(1, Math.max(0.45, fitScale)) : viewRef.current.scale;
    setView({
      scale,
      x: el.clientWidth / 2 - (card.x + CARD_W / 2) * scale,
      y: el.clientHeight / 2 - (card.y + CARD_H / 2) * scale,
    });
  };

  // When the tree is re-centred on a new person (via a Parents button, a find
  // result, or a panel link), fit and pan so that person sits in the middle of
  // the viewport — keeping focus on them while their family opens around them.
  useEffect(() => {
    if (!layout || centeredFor.current === focusId) return;
    centeredFor.current = focusId;
    centerOnFocus(true);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [layout, focusId]);

  useEffect(() => {
    centerOnFocus(true);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [portrait]);

  const matches = useMemo(() => {
    const q = find.trim().toLowerCase();
    if (q.length < 2) return [];
    return [...graph.peopleById.values()].filter((p) => p.display_name.toLowerCase().includes(q)).slice(0, 6);
  }, [find, graph]);

  if (!layout) return <div className="p-8 text-neutral-500">Person not found in this tree.</div>;

  const onWheel = (e: WheelEvent) => {
    zoomAt(boardPoint(e), viewRef.current.scale * Math.exp(-e.deltaY * 0.0015));
  };

  const onPointerDown = (e: PointerEvent) => {
    // Capture on the original target (not the board) so click events keep
    // firing on cards/buttons after small drags — events still bubble here.
    (e.target as Element).setPointerCapture?.(e.pointerId);
    const p = boardPoint(e);
    pointers.current.set(e.pointerId, p);
    tapStart.current.set(e.pointerId, { ...p, t: Date.now() });

    const v = viewRef.current;
    const pts = [...pointers.current.values()];
    if (pts.length === 2) {
      const mid = { x: (pts[0].x + pts[1].x) / 2, y: (pts[0].y + pts[1].y) / 2 };
      gesture.current = {
        mode: 'pinch',
        world: { x: (mid.x - v.x) / v.scale, y: (mid.y - v.y) / v.scale },
        startScale: v.scale,
        startDist: Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y),
      };
    } else if (pts.length === 1) {
      gesture.current = { mode: 'pan', world: { x: (p.x - v.x) / v.scale, y: (p.y - v.y) / v.scale }, scale: v.scale };
    }
  };

  const onPointerMove = (e: PointerEvent) => {
    if (!pointers.current.has(e.pointerId)) return;
    const p = boardPoint(e);
    pointers.current.set(e.pointerId, p);
    const g = gesture.current;
    if (!g) return;

    if (g.mode === 'pinch' && pointers.current.size >= 2) {
      const pts = [...pointers.current.values()];
      const mid = { x: (pts[0].x + pts[1].x) / 2, y: (pts[0].y + pts[1].y) / 2 };
      const dist = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);
      const scale = clampScale((g.startScale * dist) / g.startDist);
      setView({ scale, x: mid.x - g.world.x * scale, y: mid.y - g.world.y * scale });
    } else if (g.mode === 'pan' && pointers.current.size === 1) {
      setView({ scale: g.scale, x: p.x - g.world.x * g.scale, y: p.y - g.world.y * g.scale });
    }
  };

  const onPointerEnd = (e: PointerEvent) => {
    // Double-tap on empty canvas zooms in around the tap point (touch only).
    const start = tapStart.current.get(e.pointerId);
    tapStart.current.delete(e.pointerId);
    if (e.pointerType === 'touch' && start && pointers.current.size === 1) {
      const p = boardPoint(e);
      const isTap = Date.now() - start.t < 300 && Math.hypot(p.x - start.x, p.y - start.y) < 12;
      const onControl = (e.target as Element).closest?.('.gen-card, button, input');
      if (isTap && !onControl) {
        const prev = lastTap.current;
        if (prev && Date.now() - prev.t < 350 && Math.hypot(p.x - prev.x, p.y - prev.y) < 40) {
          lastTap.current = null;
          zoomAt(p, viewRef.current.scale * 1.5);
        } else {
          lastTap.current = { ...p, t: Date.now() };
        }
      }
    }

    pointers.current.delete(e.pointerId);
    const pts = [...pointers.current.values()];
    if (pts.length === 1) {
      // Pinch ended with one finger still down: continue as a pan from here.
      const v = viewRef.current;
      gesture.current = {
        mode: 'pan',
        world: { x: (pts[0].x - v.x) / v.scale, y: (pts[0].y - v.y) / v.scale },
        scale: v.scale,
      };
    } else if (pts.length === 0) {
      gesture.current = null;
    }
  };

  const stepper = (label: string, value: number, set: (n: number) => void, big = false) => (
    <div className={big ? 'flex items-center justify-between gap-3' : 'gen-toolbar-control'}>
      <span className={big ? 'text-[15px] font-medium text-[#26303a]' : undefined}>{label}</span>
      <div className={big ? 'flex items-center gap-1' : 'contents'}>
        <button
          className={big ? 'flex h-11 w-11 items-center justify-center rounded-xl border border-[#d9dde2] bg-white text-xl text-[#4a5560]' : 'gen-toolbar-step'}
          onClick={() => set(Math.max(1, value - 1))}
        >
          −
        </button>
        <span className={big ? 'w-8 text-center text-[16px] font-semibold text-[#26303a]' : 'w-3 text-center font-medium'}>{value}</span>
        <button
          className={big ? 'flex h-11 w-11 items-center justify-center rounded-xl border border-[#d9dde2] bg-white text-xl text-[#4a5560]' : 'gen-toolbar-step'}
          onClick={() => set(Math.min(6, value + 1))}
        >
          +
        </button>
      </div>
    </div>
  );

  const findResults = matches.length > 0 && (
    <ul className="mt-1 overflow-hidden rounded-md border border-[#dde1e6] bg-white shadow-lg">
      {matches.map((p) => (
        <li key={p.id}>
          <button
            className="block w-full px-3 py-2.5 text-left text-sm hover:bg-[#f3f7fb] lg:py-2"
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
  );

  return (
    <div
      ref={boardRef}
      className="gen-board family-chart-board relative h-full w-full cursor-grab touch-none overflow-hidden active:cursor-grabbing"
      onWheel={onWheel}
      onPointerDown={onPointerDown}
      onPointerMove={onPointerMove}
      onPointerUp={onPointerEnd}
      onPointerCancel={onPointerEnd}
    >
      {/* Find — desktop floating box */}
      <div className="absolute left-3 top-3 z-10 w-60 max-lg:hidden">
        <input
          value={find}
          onChange={(e) => setFind(e.target.value)}
          placeholder="Find a person…"
          className="w-full rounded-md border border-[#dde1e6] bg-white px-3 py-1.5 text-sm shadow-sm outline-none"
        />
        {findResults}
      </div>

      {/* Steppers — desktop, top right */}
      <div className="absolute right-3 top-3 z-10 hidden gap-2 lg:flex">
        {stepper('Generations', generations, setGenerations)}
        {stepper('Descendants', descendants, setDescendants)}
      </div>

      {/* Mobile control row: full-width search + view options gear */}
      <div className="absolute inset-x-2 top-2 z-10 flex items-start gap-2 lg:hidden">
        <div className="relative min-w-0 flex-1">
          <svg className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#9aa6b2]" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" />
          </svg>
          <input
            value={find}
            onChange={(e) => setFind(e.target.value)}
            placeholder="Find a person…"
            className="h-11 w-full rounded-full border border-[#dde1e6] bg-white/95 pl-10 pr-4 text-sm text-[#26303a] shadow-sm outline-none backdrop-blur"
          />
          <div className="absolute inset-x-0 top-full">{findResults}</div>
        </div>
        <button
          type="button"
          onClick={() => setOptionsOpen(true)}
          className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-[#dde1e6] bg-white/95 text-[#4a5560] shadow-sm"
          aria-label="Tree view options"
        >
          <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="12" r="3" />
            <path d="M12 2v3m0 14v3M2 12h3m14 0h3M4.9 4.9l2.1 2.1m10 10 2.1 2.1M19.1 4.9 17 7m-10 10-2.1 2.1" />
          </svg>
        </button>
      </div>

      {/* Zoom / re-center controls */}
      <div className="absolute bottom-3 right-3 z-10 flex flex-col gap-1.5 lg:flex-row lg:gap-1">
        <button
          onClick={() => zoomAt({ x: boardRef.current!.clientWidth / 2, y: boardRef.current!.clientHeight / 2 }, view.scale * 1.25)}
          className="flex h-11 w-11 items-center justify-center rounded-full border border-[#dde1e6] bg-white text-xl leading-none shadow-sm lg:h-8 lg:w-8 lg:rounded-md lg:text-lg lg:shadow-none"
          aria-label="Zoom in"
        >
          +
        </button>
        <button
          onClick={() => zoomAt({ x: boardRef.current!.clientWidth / 2, y: boardRef.current!.clientHeight / 2 }, view.scale / 1.25)}
          className="flex h-11 w-11 items-center justify-center rounded-full border border-[#dde1e6] bg-white text-xl leading-none shadow-sm lg:h-8 lg:w-8 lg:rounded-md lg:text-lg lg:shadow-none"
          aria-label="Zoom out"
        >
          −
        </button>
        <button
          onClick={() => centerOnFocus(true)}
          className="flex h-11 w-11 items-center justify-center rounded-full border border-[#dde1e6] bg-white shadow-sm lg:h-8 lg:w-auto lg:rounded-md lg:px-2 lg:shadow-none"
          aria-label="Center on focus person"
        >
          <svg className="h-5 w-5 text-[#4a5560] lg:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round">
            <circle cx="12" cy="12" r="3" />
            <path d="M12 2v4m0 12v4M2 12h4m12 0h4" />
          </svg>
          <span className="text-xs max-lg:hidden">Center</span>
        </button>
      </div>

      {/* Mobile view-options bottom sheet */}
      {optionsOpen && (
        <>
          <div className="fixed inset-0 z-40 bg-black/45 lg:hidden" onClick={() => setOptionsOpen(false)} aria-hidden="true" />
          <div className="fixed inset-x-0 bottom-0 z-50 rounded-t-3xl bg-white p-5 pb-[calc(env(safe-area-inset-bottom)+1.5rem)] shadow-[0_-16px_48px_rgba(0,0,0,.3)] lg:hidden" role="dialog" aria-label="Tree view options">
            <div className="mx-auto mb-4 h-1 w-10 rounded-full bg-[#d9dde2]" />
            <h3 className="text-[16px] font-semibold text-[#26303a]">Tree view</h3>
            <p className="mt-0.5 text-[13px] text-[#6b7682]">How many generations to show around the focus person.</p>
            <div className="mt-5 space-y-4">
              {stepper('Generations (ancestors)', generations, setGenerations, true)}
              {stepper('Descendants', descendants, setDescendants, true)}
            </div>
            <button type="button" onClick={() => setOptionsOpen(false)} className="mt-6 flex h-12 w-full items-center justify-center rounded-full bg-[#26303a] text-[15px] font-semibold text-white">
              Done
            </button>
          </div>
        </>
      )}

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
                portrait={portrait}
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
