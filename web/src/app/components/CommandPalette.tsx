import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { peopleApi } from '@core/api/endpoints/people';
import type { Person } from '@core/models';
import { useAuthStore } from '@core/auth/store';
import {
  IconGlobe,
  IconHome,
  IconImport,
  IconPhoto,
  IconSettings,
  IconShield,
  IconTree,
  IconUser,
} from './icons';

interface PageAction {
  label: string;
  to: string;
  hint: string;
  icon: React.ReactNode;
  keywords: string;
  adminOnly?: boolean;
}

const PAGES: PageAction[] = [
  { label: 'Home', to: '/dashboard', hint: 'Your workspace overview', icon: <IconHome />, keywords: 'home dashboard overview start' },
  { label: 'Family trees', to: '/trees', hint: 'Open or create a tree', icon: <IconTree />, keywords: 'trees family tree branches lineage' },
  { label: 'Photos & media', to: '/media', hint: 'Browse the media library', icon: <IconPhoto />, keywords: 'photos media images gallery library' },
  { label: 'Import GEDCOM', to: '/import', hint: 'Bring research from another tool', icon: <IconImport />, keywords: 'import gedcom upload migrate myheritage ancestry' },
  { label: 'Relationship calculator', to: '/relationship-calculator', hint: 'How are two people related?', icon: <IconGlobe />, keywords: 'global tree relationship calculator connection path related' },
  { label: 'Settings', to: '/settings', hint: 'Profile, security, API tokens', icon: <IconSettings />, keywords: 'settings profile security password tokens account' },
  { label: 'Administration', to: '/admin', hint: 'Site administration', icon: <IconShield />, keywords: 'admin users administration activity', adminOnly: true },
];

// Global jump-anywhere palette: filters page actions instantly and searches
// people across the user's trees; selecting a person opens their tree.
export function CommandPalette({ open, onClose }: { open: boolean; onClose: () => void }) {
  const navigate = useNavigate();
  const user = useAuthStore((s) => s.user);
  const [query, setQuery] = useState('');
  const [people, setPeople] = useState<Person[]>([]);
  const [index, setIndex] = useState(0);
  const inputRef = useRef<HTMLInputElement>(null);
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const pages = useMemo(() => {
    const q = query.trim().toLowerCase();
    return PAGES.filter((p) => !p.adminOnly || user?.is_super_admin).filter(
      (p) => !q || p.label.toLowerCase().includes(q) || p.keywords.includes(q),
    );
  }, [query, user?.is_super_admin]);

  // Debounced people search once the query looks like a name.
  useEffect(() => {
    if (!open) return;
    if (query.trim().length < 2) {
      setPeople([]);
      return;
    }
    if (timer.current) clearTimeout(timer.current);
    timer.current = setTimeout(() => {
      peopleApi
        .search(query.trim())
        .then((r) => setPeople(r.slice(0, 6)))
        .catch(() => setPeople([]));
    }, 250);
    return () => {
      if (timer.current) clearTimeout(timer.current);
    };
  }, [query, open]);

  useEffect(() => {
    if (open) {
      setQuery('');
      setPeople([]);
      setIndex(0);
      // Focus after the overlay paints.
      requestAnimationFrame(() => inputRef.current?.focus());
    }
  }, [open]);

  useEffect(() => setIndex(0), [query]);

  const items = useMemo(
    () => [
      ...pages.map((p) => ({ kind: 'page' as const, page: p })),
      ...people.map((p) => ({ kind: 'person' as const, person: p })),
    ],
    [pages, people],
  );

  const go = (item: (typeof items)[number]) => {
    onClose();
    if (item.kind === 'page') navigate(item.page.to);
    else navigate(`/trees/${item.person.family_tree_id}`);
  };

  const onKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setIndex((i) => Math.min(i + 1, items.length - 1));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setIndex((i) => Math.max(i - 1, 0));
    } else if (e.key === 'Enter' && items[index]) {
      e.preventDefault();
      go(items[index]);
    } else if (e.key === 'Escape') {
      onClose();
    }
  };

  if (!open) return null;

  let flatIndex = -1;

  return (
    <div
      className="fixed inset-0 z-50 flex items-start justify-center bg-black/60 p-4 pt-[12vh] backdrop-blur-sm"
      onClick={onClose}
      role="dialog"
      aria-label="Command palette"
    >
      <div
        className="o-rise w-full max-w-xl overflow-hidden rounded-2xl border border-edge bg-elevated o-pop"
        onClick={(e) => e.stopPropagation()}
        onKeyDown={onKeyDown}
      >
        <div className="flex items-center gap-3 border-b border-edge px-4">
          <svg className="h-[18px] w-[18px] shrink-0 text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round">
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" />
          </svg>
          <input
            ref={inputRef}
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Search people, or jump to a page…"
            className="h-13 w-full bg-transparent py-4 text-[15px] text-ink outline-none placeholder:text-ink-muted/70"
            aria-label="Search"
          />
          <span className="o-kbd shrink-0">esc</span>
        </div>

        <div className="max-h-[50vh] overflow-y-auto p-2">
          {pages.length > 0 && (
            <>
              <p className="px-3 pb-1 pt-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-ink-muted/80">Go to</p>
              {pages.map((p) => {
                flatIndex += 1;
                const i = flatIndex;
                return (
                  <button
                    key={p.to}
                    type="button"
                    onClick={() => go({ kind: 'page', page: p })}
                    onMouseEnter={() => setIndex(i)}
                    className={`flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition-colors ${
                      index === i ? 'bg-accent-soft text-accent' : 'text-ink-soft'
                    }`}
                  >
                    <span className={index === i ? 'text-accent' : 'text-ink-muted'}>{p.icon}</span>
                    <span className="flex-1 font-medium">{p.label}</span>
                    <span className="text-xs text-ink-muted">{p.hint}</span>
                  </button>
                );
              })}
            </>
          )}

          {people.length > 0 && (
            <>
              <p className="px-3 pb-1 pt-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-ink-muted/80">People</p>
              {people.map((p) => {
                flatIndex += 1;
                const i = flatIndex;
                return (
                  <button
                    key={p.id}
                    type="button"
                    onClick={() => go({ kind: 'person', person: p })}
                    onMouseEnter={() => setIndex(i)}
                    className={`flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition-colors ${
                      index === i ? 'bg-accent-soft text-accent' : 'text-ink-soft'
                    }`}
                  >
                    <span className={index === i ? 'text-accent' : 'text-ink-muted'}>
                      <IconUser />
                    </span>
                    <span className="flex-1 font-medium">{p.display_name}</span>
                    {p.life_span && <span className="text-xs text-ink-muted">{p.life_span}</span>}
                  </button>
                );
              })}
            </>
          )}

          {items.length === 0 && (
            <p className="px-3 py-8 text-center text-sm text-ink-muted">
              No matches. Try a person&apos;s name or a page like “photos”.
            </p>
          )}
        </div>

        <div className="flex items-center gap-4 border-t border-edge px-4 py-2.5 text-[11px] text-ink-muted">
          <span className="flex items-center gap-1.5">
            <span className="o-kbd">↑↓</span> navigate
          </span>
          <span className="flex items-center gap-1.5">
            <span className="o-kbd">↵</span> open
          </span>
        </div>
      </div>
    </div>
  );
}
