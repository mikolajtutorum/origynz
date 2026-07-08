import { useEffect, useMemo, useState } from 'react';
import { useT } from '@core/i18n';
import { useTrees } from '@core/queries/trees';
import {
  useDismissCandidate,
  useMergeCandidate,
  useMergePreview,
  useMergeSuggestions,
  useScanTree,
  useTreeMergeCandidates,
} from '@core/queries/merge';
import type { MergeCandidate, MergePersonHeader } from '@core/api/endpoints/merge';
import { AppLayout } from '../components/AppLayout';
import { FullScreenSpinner, Spinner } from '../components/Spinner';
import { Button, Modal } from '../components/ui';

function scoreTone(score: number): string {
  if (score >= 85) return 'o-chip-brand';
  if (score >= 70) return 'o-chip-muted';
  return 'o-chip-muted';
}

function PersonSummary({ p }: { p: MergePersonHeader }) {
  const t = useT();
  return (
    <div className="min-w-0">
      <p className="truncate font-semibold text-ink">{p.display_name}</p>
      <p className="truncate text-xs text-ink-muted">
        {p.life_span || t('Dates unknown')}
        {p.birth_place ? ` · ${p.birth_place}` : ''}
      </p>
      {p.tree_name && <p className="truncate text-[11px] text-ink-muted">{p.tree_name}</p>}
    </div>
  );
}

function CandidateCard({
  candidate,
  onCompare,
}: {
  candidate: MergeCandidate;
  onCompare: (c: MergeCandidate) => void;
}) {
  const t = useT();
  const dismiss = useDismissCandidate();
  return (
    <li className="o-card flex flex-col gap-4 p-5">
      <div className="flex items-center justify-between gap-3">
        <span className={`${scoreTone(candidate.similarity_score)} uppercase tracking-[0.12em]`}>
          {t('{score}% match', { score: candidate.similarity_score })}
        </span>
        <button
          onClick={() => window.confirm('Dismiss this suggestion?') && dismiss.mutate(candidate.id)}
          className="text-xs font-medium text-ink-muted transition hover:text-ink hover:underline"
          disabled={dismiss.isPending}
        >
          {t('Dismiss')}
        </button>
      </div>

      <div className="grid grid-cols-[1fr_auto_1fr] items-center gap-3">
        <PersonSummary p={candidate.person_a} />
        <span className="text-ink-muted" aria-hidden>
          ↔
        </span>
        <PersonSummary p={candidate.person_b} />
      </div>

      <Button variant="secondary" onClick={() => onCompare(candidate)} className="o-btn-sm self-start">
        {t('Compare & merge')}
      </Button>
    </li>
  );
}

function MergeModal({ candidate, onClose }: { candidate: MergeCandidate; onClose: () => void }) {
  const t = useT();
  const { data: preview, isLoading } = useMergePreview(candidate.id);
  const merge = useMergeCandidate();
  const [surviving, setSurviving] = useState<'a' | 'b'>('a');
  const [decisions, setDecisions] = useState<Record<string, 'a' | 'b'>>({});
  const [error, setError] = useState<string | null>(null);

  // Seed per-field choices from the server's suggestions once loaded.
  useEffect(() => {
    if (preview) {
      const seed: Record<string, 'a' | 'b'> = {};
      for (const f of preview.fields) seed[f.field] = f.suggested;
      setDecisions(seed);
    }
  }, [preview]);

  const survivingHeader = preview ? (surviving === 'a' ? preview.person_a : preview.person_b) : null;
  const absorbedHeader = preview ? (surviving === 'a' ? preview.person_b : preview.person_a) : null;

  const onSubmit = async () => {
    setError(null);
    try {
      await merge.mutateAsync({ id: candidate.id, payload: { surviving, decisions } });
      onClose();
    } catch {
      setError(t('Merge failed. You may not have manage access to both trees.'));
    }
  };

  // Only fields where the two sides differ need a decision; identical/blank rows are informational.
  const conflictFields = useMemo(() => preview?.fields.filter((f) => f.conflict) ?? [], [preview]);

  return (
    <Modal title={t('Compare & merge')} onClose={onClose}>
      {isLoading || !preview ? (
        <div className="py-10">
          <Spinner />
        </div>
      ) : (
        <div className="flex flex-col gap-5">
          <p className="text-sm text-ink-muted">
            {t('Choose which profile survives. The other is absorbed into it — its relationships, photos, events and sources move over, then it is retired.')}
          </p>

          {/* Survivor picker */}
          <div className="grid grid-cols-2 gap-3">
            {(['a', 'b'] as const).map((side) => {
              const p = side === 'a' ? preview.person_a : preview.person_b;
              const active = surviving === side;
              return (
                <button
                  key={side}
                  onClick={() => setSurviving(side)}
                  className={`rounded-xl border p-3 text-left transition ${
                    active ? 'border-emerald-400 bg-emerald-400/10' : 'border-line hover:border-line-strong'
                  }`}
                >
                  <span className="o-label mb-1 block">{active ? t('Keep this profile') : t('Absorb this one')}</span>
                  <PersonSummary p={p} />
                  <p className="mt-1.5 text-[11px] text-ink-muted">
                    {t('{rel} rel · {media} photos · {events} events · {sources} sources', {
                      rel: p.counts.relationships,
                      media: p.counts.media,
                      events: p.counts.events,
                      sources: p.counts.sources,
                    })}
                  </p>
                </button>
              );
            })}
          </div>

          {/* Field conflicts */}
          {conflictFields.length > 0 ? (
            <div className="flex flex-col gap-2.5">
              <p className="o-label">{t('Resolve conflicting fields')}</p>
              {conflictFields.map((f) => (
                <div key={f.field} className="rounded-lg border border-line p-2.5">
                  <p className="mb-1.5 text-xs font-medium text-ink-soft">{f.label}</p>
                  <div className="grid grid-cols-2 gap-2">
                    {(['a', 'b'] as const).map((side) => {
                      const val = side === 'a' ? f.value_a : f.value_b;
                      const chosen = decisions[f.field] === side;
                      return (
                        <button
                          key={side}
                          onClick={() => setDecisions((d) => ({ ...d, [f.field]: side }))}
                          className={`truncate rounded-md border px-2.5 py-1.5 text-left text-xs transition ${
                            chosen
                              ? 'border-emerald-400 bg-emerald-400/10 text-ink'
                              : 'border-line text-ink-muted hover:border-line-strong'
                          }`}
                          title={val ?? '—'}
                        >
                          {val ?? <span className="italic">empty</span>}
                        </button>
                      );
                    })}
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-sm text-ink-muted">{t('No conflicting fields — values will be combined automatically.')}</p>
          )}

          {survivingHeader && absorbedHeader && (
            <p className="text-xs text-ink-muted">
              <span className="font-medium text-ink-soft">{survivingHeader.display_name}</span> {t('will absorb')}{' '}
              <span className="font-medium text-ink-soft">{absorbedHeader.display_name}</span>.
            </p>
          )}

          {error && <div className="o-alert-error">{error}</div>}

          <div className="flex justify-end gap-3">
            <Button variant="secondary" onClick={onClose}>
              {t('Cancel')}
            </Button>
            <Button onClick={onSubmit} loading={merge.isPending}>
              {t('Merge profiles')}
            </Button>
          </div>
        </div>
      )}
    </Modal>
  );
}

function CandidateGrid({
  candidates,
  onCompare,
  empty,
}: {
  candidates: MergeCandidate[];
  onCompare: (c: MergeCandidate) => void;
  empty: string;
}) {
  if (candidates.length === 0) {
    return <div className="o-empty text-sm">{empty}</div>;
  }
  return (
    <ul className="grid grid-cols-1 gap-4 lg:grid-cols-2">
      {candidates.map((c) => (
        <CandidateCard key={c.id} candidate={c} onCompare={onCompare} />
      ))}
    </ul>
  );
}

export function Duplicates() {
  const t = useT();
  const { data: trees, isLoading: treesLoading } = useTrees();
  const [treeId, setTreeId] = useState<string | undefined>();
  const [active, setActive] = useState<MergeCandidate | null>(null);

  // Default the picker to the first tree once trees load.
  useEffect(() => {
    if (!treeId && trees && trees.length > 0) setTreeId(trees[0].id);
  }, [trees, treeId]);

  const treeCandidates = useTreeMergeCandidates(treeId);
  const suggestions = useMergeSuggestions();
  const scan = useScanTree(treeId);

  if (treesLoading) {
    return (
      <AppLayout>
        <FullScreenSpinner />
      </AppLayout>
    );
  }

  return (
    <AppLayout>
      <div className="space-y-8">
        <header className="max-w-2xl space-y-2">
          <p className="o-eyebrow">{t('Data quality')}</p>
          <h1 className="o-display text-3xl sm:text-4xl">{t('Merge duplicates')}</h1>
          <p className="text-[15px] leading-7 text-ink-muted">
            {t('Find people who appear more than once — within a tree or across trees on the Global Tree — and merge their profiles into one.')}
          </p>
        </header>

        {/* Within-tree duplicates */}
        <section className="space-y-4">
          <div className="flex flex-wrap items-end justify-between gap-3">
            <div className="flex items-end gap-3">
              <label className="flex flex-col gap-1.5">
                <span className="o-label">{t('Family trees')}</span>
                <select
                  value={treeId ?? ''}
                  onChange={(e) => setTreeId(e.target.value)}
                  className="o-input min-w-[14rem]"
                >
                  {trees?.map((tree) => (
                    <option key={tree.id} value={tree.id}>
                      {tree.name}
                    </option>
                  ))}
                </select>
              </label>
            </div>
            <Button
              variant="secondary"
              onClick={() => scan.mutate()}
              loading={scan.isPending}
              disabled={!treeId}
            >
              {t('Scan for duplicates')}
            </Button>
          </div>

          {scan.data && (
            <p className="text-sm text-ink-muted">
              {scan.data.created > 0
                ? t('Found {count} new potential duplicates.', { count: scan.data.created })
                : t('No new duplicates found in this tree.')}
            </p>
          )}

          {treeCandidates.isLoading ? (
            <Spinner />
          ) : (
            <CandidateGrid
              candidates={treeCandidates.data ?? []}
              onCompare={setActive}
              empty={t('No duplicates detected in this tree yet. Run a scan to check.')}
            />
          )}
        </section>

        {/* Cross-tree suggested connections */}
        <section className="space-y-4">
          <div>
            <h2 className="o-display text-xl">{t('Suggested connections')}</h2>
            <p className="mt-1 text-sm text-ink-muted">
              {t('People in your trees that look like the same person in someone else’s tree on the Global Tree.')}
            </p>
          </div>
          {suggestions.isLoading ? (
            <Spinner />
          ) : (
            <CandidateGrid
              candidates={suggestions.data ?? []}
              onCompare={setActive}
              empty={t('No cross-tree suggestions right now.')}
            />
          )}
        </section>
      </div>

      {active && <MergeModal candidate={active} onClose={() => setActive(null)} />}
    </AppLayout>
  );
}
