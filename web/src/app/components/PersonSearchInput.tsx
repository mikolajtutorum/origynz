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
      <div className="flex flex-col gap-1">
        <span className="text-sm font-medium text-neutral-700">{label}</span>
        <div className="flex items-center justify-between rounded-md border border-neutral-300 px-3 py-2 text-sm">
          <span>
            {selected.display_name}
            {selected.life_span ? <span className="text-neutral-400"> · {selected.life_span}</span> : null}
          </span>
          <button
            type="button"
            className="text-neutral-400 hover:text-neutral-700"
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
    <div className="relative flex flex-col gap-1">
      <label className="text-sm font-medium text-neutral-700">{label}</label>
      <input
        value={query}
        onChange={(e) => setQuery(e.target.value)}
        placeholder="Search people…"
        className="rounded-md border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
      />
      {open && results.length > 0 && (
        <ul className="absolute top-full z-10 mt-1 max-h-56 w-full overflow-auto rounded-md border border-neutral-200 bg-white shadow-lg">
          {results.map((p) => (
            <li key={p.id}>
              <button
                type="button"
                className="block w-full px-3 py-2 text-left text-sm hover:bg-neutral-100"
                onClick={() => {
                  onSelect(p);
                  setOpen(false);
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
  );
}
