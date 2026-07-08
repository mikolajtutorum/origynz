import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  useCreateCitation,
  useCreateSource,
  useDeleteCitation,
  usePersonCitations,
  useTreeSources,
} from '@core/queries/sources';
import { integrationsApi } from '@core/api/endpoints/integrations';
import { useT } from '@core/i18n';

function Field({ d, className = 'h-3.5 w-3.5' }: { d: string; className?: string }) {
  return (
    <svg viewBox="0 0 24 24" fill="none" className={className}>
      <path d={d} stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

export function SourcesTab({
  personId,
  treeId,
  canManage,
}: {
  personId: string;
  treeId: string;
  canManage: boolean;
}) {
  const t = useT();
  const { data: citations, isLoading } = usePersonCitations(personId);
  const { data: sources } = useTreeSources(treeId);
  const { data: research } = useQuery({
    queryKey: ['research-links', personId],
    queryFn: () => integrationsApi.researchLinks(personId),
  });
  const createCitation = useCreateCitation(personId, treeId);
  const createSource = useCreateSource(treeId);
  const deleteCitation = useDeleteCitation(personId);

  const [adding, setAdding] = useState(false);
  const [mode, setMode] = useState<'existing' | 'new'>('existing');
  const [sourceId, setSourceId] = useState('');
  const [newTitle, setNewTitle] = useState('');
  const [newUrl, setNewUrl] = useState('');
  const [page, setPage] = useState('');
  const [quotation, setQuotation] = useState('');
  const [error, setError] = useState<string | null>(null);

  const reset = () => {
    setAdding(false);
    setMode('existing');
    setSourceId('');
    setNewTitle('');
    setNewUrl('');
    setPage('');
    setQuotation('');
    setError(null);
  };

  const submit = async () => {
    setError(null);
    try {
      let citeSourceId = sourceId;
      if (mode === 'new') {
        if (!newTitle.trim()) {
          setError(t('A source title is required.'));
          return;
        }
        const created = await createSource.mutateAsync({ title: newTitle.trim(), url: newUrl.trim() || null });
        citeSourceId = created.id;
      }
      if (!citeSourceId) {
        setError(t('Choose a source.'));
        return;
      }
      await createCitation.mutateAsync({
        source_id: citeSourceId,
        page: page.trim() || null,
        quotation: quotation.trim() || null,
      });
      reset();
    } catch (e) {
      setError((e as Error).message ?? t('Could not add citation.'));
    }
  };

  if (isLoading) return <p className="text-[12.5px] text-ink-muted">{t('Loading sources…')}</p>;

  const busy = createCitation.isPending || createSource.isPending;

  return (
    <div className="space-y-4">
      {citations?.length ? (
        <ul className="space-y-2.5">
          {citations.map((c) => (
            <li key={c.id} className="rounded-xl border border-line bg-fill-faint p-3">
              <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                  <p className="text-[13px] font-semibold text-ink">{c.source_title ?? t('Untitled source')}</p>
                  {c.source_author && <p className="text-[11.5px] text-ink-muted">{c.source_author}</p>}
                </div>
                {canManage && (
                  <button
                    onClick={() => deleteCitation.mutate(c.id)}
                    className="shrink-0 text-ink-muted transition hover:text-danger"
                    aria-label={t('Remove citation')}
                  >
                    <Field d="M6 6l12 12M18 6L6 18" />
                  </button>
                )}
              </div>
              {c.page && <p className="mt-1 text-[12px] text-ink-soft">{t('Page')}: {c.page}</p>}
              {c.quotation && <p className="mt-1 text-[12px] italic leading-5 text-ink-soft">“{c.quotation}”</p>}
              {c.source_url && (
                <a
                  href={c.source_url}
                  target="_blank"
                  rel="noreferrer"
                  className="mt-1 inline-flex items-center gap-1 text-[11.5px] font-medium text-accent hover:text-accent-strong"
                >
                  {t('View source')}
                  <Field d="M7 17 17 7M9 7h8v8" className="h-3 w-3" />
                </a>
              )}
            </li>
          ))}
        </ul>
      ) : (
        <p className="text-[12.5px] text-ink-muted">{t('No sources cited for this person yet.')}</p>
      )}

      {canManage && !adding && (
        <button
          onClick={() => setAdding(true)}
          className="o-btn-secondary o-btn-sm w-full"
        >
          {t('+ Cite a source')}
        </button>
      )}

      {canManage && adding && (
        <div className="space-y-3 rounded-xl border border-line p-3">
          <div className="flex gap-1 rounded-lg bg-fill p-0.5 text-[11.5px] font-medium">
            {(['existing', 'new'] as const).map((m) => (
              <button
                key={m}
                onClick={() => setMode(m)}
                className={`flex-1 rounded-md py-1 transition ${
                  mode === m ? 'bg-elevated text-ink shadow-sm' : 'text-ink-muted'
                }`}
              >
                {m === 'existing' ? t('Existing source') : t('New source')}
              </button>
            ))}
          </div>

          {mode === 'existing' ? (
            <select
              value={sourceId}
              onChange={(e) => setSourceId(e.target.value)}
              className="o-input text-[13px]"
            >
              <option value="">{t('Choose a source…')}</option>
              {sources?.map((s) => (
                <option key={s.id} value={s.id}>
                  {s.title}
                </option>
              ))}
            </select>
          ) : (
            <div className="space-y-2">
              <input
                value={newTitle}
                onChange={(e) => setNewTitle(e.target.value)}
                placeholder={t('Source title (e.g. 1911 Census)')}
                className="o-input text-[13px]"
              />
              <input
                value={newUrl}
                onChange={(e) => setNewUrl(e.target.value)}
                placeholder={t('URL (optional)')}
                className="o-input text-[13px]"
              />
            </div>
          )}

          <input
            value={page}
            onChange={(e) => setPage(e.target.value)}
            placeholder={t('Page / reference (optional)')}
            className="o-input text-[13px]"
          />
          <textarea
            value={quotation}
            onChange={(e) => setQuotation(e.target.value)}
            placeholder={t('Quotation (optional)')}
            rows={2}
            className="o-input resize-none text-[13px]"
          />

          {error && <p className="text-[11.5px] text-danger">{error}</p>}

          <div className="flex justify-end gap-2">
            <button onClick={reset} className="o-btn-secondary o-btn-sm">
              {t('Cancel')}
            </button>
            <button onClick={submit} disabled={busy} className="o-btn-primary o-btn-sm">
              {busy ? t('Saving…') : t('Add citation')}
            </button>
          </div>
        </div>
      )}

      {research && (
        <div className="border-t border-line/60 pt-3">
          <p className="mb-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-muted/80">
            {t('Search external records')}
          </p>
          <div className="flex flex-wrap gap-x-4 gap-y-1 text-[11.5px]">
            <a
              href={research.findagrave.memorial ?? research.findagrave.search}
              target="_blank"
              rel="noreferrer"
              className="inline-flex items-center gap-1 font-medium text-accent hover:text-accent-strong"
            >
              Find a Grave
              <Field d="M7 17 17 7M9 7h8v8" className="h-3 w-3" />
            </a>
            <a
              href={research.billiongraves.grave ?? research.billiongraves.search}
              target="_blank"
              rel="noreferrer"
              className="inline-flex items-center gap-1 font-medium text-accent hover:text-accent-strong"
            >
              BillionGraves
              <Field d="M7 17 17 7M9 7h8v8" className="h-3 w-3" />
            </a>
          </div>
        </div>
      )}
    </div>
  );
}
