import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { interactionsApi } from '@core/api/endpoints/interactions';
import type { Person } from '@core/models';
import { useT } from '@core/i18n';

// Global-Tree community actions for a person: watch toggle + discussion thread.
export function InteractionPanel({ person }: { person: Person }) {
  const t = useT();
  const qc = useQueryClient();
  const [watching, setWatching] = useState<boolean | null>(null);
  const [comment, setComment] = useState('');

  const discussionsKey = ['discussions', person.id];
  const { data: discussions } = useQuery({
    queryKey: discussionsKey,
    queryFn: () => interactionsApi.discussions(person.id),
  });

  const toggleWatch = useMutation({
    mutationFn: () => interactionsApi.toggleWatch(person.id),
    onSuccess: (r) => setWatching(r.watching),
  });

  const post = useMutation({
    mutationFn: () => interactionsApi.postDiscussion(person.id, comment.trim()),
    onSuccess: () => {
      setComment('');
      qc.invalidateQueries({ queryKey: discussionsKey });
    },
  });

  const remove = useMutation({
    mutationFn: (id: string) => interactionsApi.deleteDiscussion(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: discussionsKey }),
  });

  return (
    <div>
      <div className="mb-3 flex items-center justify-between gap-3">
        <p className="text-[10px] font-semibold uppercase tracking-[0.2em] text-ink-muted/80">{t('Discussion')}</p>
        <button
          onClick={() => toggleWatch.mutate()}
          className={`o-btn-sm inline-flex min-h-8 items-center gap-1.5 rounded-full border px-3 text-[11px] font-semibold transition ${
            watching
              ? 'border-accent-edge bg-accent-soft text-accent'
              : 'border-edge bg-fill text-ink-soft hover:border-edge-strong hover:text-ink'
          }`}
        >
          <svg className="h-3 w-3" viewBox="0 0 24 24" fill={watching ? 'currentColor' : 'none'} stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round">
            <path d="m12 3 2.7 5.6 6.3.8-4.6 4.3 1.2 6.1L12 16.9 6.4 19.8l1.2-6.1L3 9.4l6.3-.8L12 3Z" />
          </svg>
          {watching ? t('Watching') : t('Watch')}
        </button>
      </div>

      <ul className="flex flex-col gap-2">
        {discussions?.length ? (
          discussions.map((d) => (
            <li key={d.id} className="rounded-xl border border-line bg-fill-faint px-3 py-2.5">
              <div className="flex items-start justify-between gap-2">
                <span className="text-[11px] font-semibold text-accent">{d.author}</span>
                {d.can_delete && (
                  <button onClick={() => remove.mutate(d.id)} className="text-ink-muted transition hover:text-danger" aria-label={t('Delete comment')}>
                    ✕
                  </button>
                )}
              </div>
              <p className="mt-1 text-[12.5px] leading-5 text-ink-soft">{d.body}</p>
            </li>
          ))
        ) : (
          <li className="text-[12.5px] text-ink-muted">{t('No comments yet — start the discussion.')}</li>
        )}
      </ul>

      <div className="mt-3 flex gap-2">
        <input
          value={comment}
          onChange={(e) => setComment(e.target.value)}
          onKeyDown={(e) => e.key === 'Enter' && comment.trim() && post.mutate()}
          placeholder={t('Add a comment…')}
          className="o-input min-w-0 flex-1 px-3 py-2 text-[12.5px]"
        />
        <button
          onClick={() => comment.trim() && post.mutate()}
          disabled={post.isPending}
          className="o-btn-primary o-btn-sm shrink-0"
        >
          {t('Post')}
        </button>
      </div>
    </div>
  );
}
