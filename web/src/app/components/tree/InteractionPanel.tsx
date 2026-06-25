import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { interactionsApi } from '@core/api/endpoints/interactions';
import type { Person } from '@core/models';

// Global-Tree community actions for a person: watch toggle + discussion thread.
export function InteractionPanel({ person }: { person: Person }) {
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
    <div className="mt-4 border-t border-neutral-200 pt-4">
      <div className="mb-3 flex items-center justify-between">
        <h3 className="text-sm font-semibold text-neutral-700">Community</h3>
        <button
          onClick={() => toggleWatch.mutate()}
          className="rounded-md border border-neutral-300 px-2 py-1 text-xs hover:bg-neutral-50"
        >
          {watching === null ? 'Watch' : watching ? '★ Watching' : 'Watch'}
        </button>
      </div>

      <ul className="mb-3 flex max-h-40 flex-col gap-2 overflow-auto">
        {discussions?.length ? (
          discussions.map((d) => (
            <li key={d.id} className="rounded-md bg-neutral-50 px-2 py-1.5 text-xs">
              <div className="flex justify-between">
                <span className="font-medium text-neutral-700">{d.author}</span>
                {d.can_delete && (
                  <button onClick={() => remove.mutate(d.id)} className="text-neutral-400 hover:text-red-600">
                    ✕
                  </button>
                )}
              </div>
              <p className="text-neutral-600">{d.body}</p>
            </li>
          ))
        ) : (
          <li className="text-xs text-neutral-400">No comments yet.</li>
        )}
      </ul>

      <div className="flex gap-2">
        <input
          value={comment}
          onChange={(e) => setComment(e.target.value)}
          placeholder="Add a comment…"
          className="min-w-0 flex-1 rounded-md border border-neutral-300 px-2 py-1 text-xs"
        />
        <button
          onClick={() => comment.trim() && post.mutate()}
          disabled={post.isPending}
          className="rounded-md bg-neutral-900 px-3 py-1 text-xs text-white disabled:opacity-50"
        >
          Post
        </button>
      </div>
    </div>
  );
}
