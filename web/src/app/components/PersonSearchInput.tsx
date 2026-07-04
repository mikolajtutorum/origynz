import { useEffect, useRef, useState } from 'react';
import { peopleApi } from '@core/api/endpoints/people';
import type { Person } from '@core/models';

// Debounced person search with a result dropdown. Used by the relationship calculator.
export function PersonSearchInput({
  label,
  onSelect,
  selected,
  treeId,
  excludeId,
}: {
  label: string;
  onSelect: (person: Person | null) => void;
  selected: Person | null;
  treeId?: string;
  excludeId?: string;
}) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<Person[]>([]);
  const [open, setOpen] = useState(false);
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    if (selected || query.trim().length < 2) {
      setResults([]);
      return;
    }
    if (timer.current) clearTimeout(timer.current);
    timer.current = setTimeout(() => {
      peopleApi
        .search(query.trim(), treeId)
        .then((r) => {
          setResults(excludeId ? r.filter((p) => p.id !== excludeId) : r);
          setOpen(true);
        })
        .catch(() => setResults([]));
    }, 300);
    return () => {
      if (timer.current) clearTimeout(timer.current);
    };
  }, [query, selected]);

  if (selected) {
    return (
      <div className="flex flex-col gap-1.5">
        <span className="o-label">{label}</span>
        <div className="flex items-center justify-between rounded-xl border border-line-strong bg-surface px-3.5 py-2.5 text-sm text-ink">
          <span>
            {selected.display_name}
            {selected.life_span ? <span className="text-ink-muted"> · {selected.life_span}</span> : null}
          </span>
          <button
            type="button"
            className="text-ink-muted transition hover:text-ink"
            onClick={() => {
              onSelect(null);
              setQuery('');
            }}
          >
            ✕
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="relative flex flex-col gap-1.5">
      <label className="o-label">{label}</label>
      <input
        value={query}
        onChange={(e) => setQuery(e.target.value)}
        placeholder="Search people…"
        className="o-input"
      />
      {open && results.length > 0 && (
        <ul className="absolute top-full z-10 mt-2 max-h-56 w-full overflow-auto rounded-2xl border border-line bg-surface py-1 o-pop">
          {results.map((p) => (
            <li key={p.id}>
              <button
                type="button"
                className="block w-full px-3.5 py-2.5 text-left text-sm text-ink-soft transition-colors hover:bg-fill hover:text-accent"
                onClick={() => {
                  onSelect(p);
                  setOpen(false);
                }}
              >
                {p.display_name}
                {p.life_span ? <span className="text-ink-muted"> · {p.life_span}</span> : null}
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
