import { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { gedcomApi, type ImportProgress, type ImportStage } from '@core/api/endpoints/gedcom';
import { useTrees } from '@core/queries/trees';
import { useT } from '@core/i18n';
import { AppLayout } from '../components/AppLayout';
import { Button, FormError, Select, TextField } from '../components/ui';

// Ordered pipeline the importer walks through. `queued`, `processing`, `failed`
// are transient states handled separately and are not shown as their own step.
const STAGES: { key: ImportStage; label: string; hint: string }[] = [
  { key: 'reading', label: 'Reading file', hint: 'Decoding and normalising the upload' },
  { key: 'parsing', label: 'Parsing records', hint: 'Reading GEDCOM tags and tree metadata' },
  { key: 'sources', label: 'Sources & media', hint: 'Importing citations and media references' },
  { key: 'people', label: 'Creating people', hint: 'Adding every individual to the tree' },
  { key: 'links', label: 'Linking citations & media', hint: 'Attaching sources and photos to people' },
  { key: 'relationships', label: 'Building relationships', hint: 'Connecting spouses, parents and children' },
  { key: 'finalizing', label: 'Finalizing', hint: 'Matching the tree owner and wrapping up' },
  { key: 'done', label: 'Complete', hint: 'Your tree is ready' },
];

function stageIndex(stage: ImportStage | undefined): number {
  if (!stage || stage === 'queued') return -1;
  if (stage === 'processing') return 0;
  return STAGES.findIndex((s) => s.key === stage);
}

function StageRow({ state, label, hint, detail }: { state: 'done' | 'active' | 'pending'; label: string; hint: string; detail?: string }) {
  return (
    <li className="flex items-start gap-3">
      <span
        className={[
          'mt-0.5 flex h-5 w-5 flex-none items-center justify-center rounded-full border text-[11px] font-semibold',
          state === 'done' && 'border-emerald-400 bg-emerald-400 text-emerald-950',
          state === 'active' && 'border-emerald-300 text-accent',
          state === 'pending' && 'border-line-strong text-line-strong',
        ]
          .filter(Boolean)
          .join(' ')}
      >
        {state === 'done' ? (
          <svg viewBox="0 0 20 20" fill="currentColor" className="h-3 w-3">
            <path
              fillRule="evenodd"
              d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.3 3.3 6.8-6.8a1 1 0 011.4 0z"
              clipRule="evenodd"
            />
          </svg>
        ) : state === 'active' ? (
          <span className="h-2 w-2 animate-pulse rounded-full bg-emerald-300" />
        ) : null}
      </span>
      <div className="min-w-0">
        <p className={state === 'pending' ? 'text-sm text-ink-muted/70' : 'text-sm font-medium text-ink'}>{label}</p>
        <p className="text-xs text-ink-muted">{detail ?? hint}</p>
      </div>
    </li>
  );
}

export function GedcomImport() {
  const t = useT();
  const navigate = useNavigate();
  const { data: trees } = useTrees();

  const [file, setFile] = useState<File | null>(null);
  const [treeName, setTreeName] = useState('');
  const [target, setTarget] = useState('new'); // 'new' | tree id
  const [importId, setImportId] = useState<string | null>(null);
  const [progress, setProgress] = useState<ImportProgress | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const poll = useRef<ReturnType<typeof setInterval> | null>(null);

  useEffect(() => {
    if (!importId) return;
    poll.current = setInterval(async () => {
      try {
        const p = await gedcomApi.progress(importId);
        setProgress(p);
        if (p.status === 'done') {
          if (poll.current) clearInterval(poll.current);
          if (p.tree_id)
            setTimeout(
              () =>
                navigate(`/trees/${p.tree_id}`, {
                  // Ask the user to pick themselves when the importer couldn't.
                  state: p.owner_selection_required ? { chooseHome: true } : undefined,
                }),
              900,
            );
        } else if (p.status === 'failed') {
          if (poll.current) clearInterval(poll.current);
          setError(p.message);
        }
      } catch {
        /* keep polling */
      }
    }, 1200);
    return () => {
      if (poll.current) clearInterval(poll.current);
    };
  }, [importId, navigate]);

  const submit = async () => {
    if (!file) return;
    setError(null);
    setSubmitting(true);
    try {
      const form = new FormData();
      form.append('gedcom_file', file);
      if (target === 'new') {
        if (treeName.trim()) form.append('tree_name', treeName.trim());
      } else {
        form.append('tree_id', target);
      }
      const started = await gedcomApi.importNew(form);
      setImportId(started.import_id);
      setProgress({
        status: 'queued',
        stage: 'queued',
        progress: 0,
        message: t('Queued…'),
        current: null,
        total: null,
        tree_id: started.tree_id,
        first_person_id: null,
      });
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setSubmitting(false);
    }
  };

  const busy = importId !== null && progress?.status !== 'failed';
  const pct = Math.max(0, Math.min(100, Math.round(progress?.progress ?? 0)));
  const activeIndex = stageIndex(progress?.stage);
  const queued = progress?.status === 'queued';
  const done = progress?.status === 'done';

  return (
    <AppLayout>
      <header className="max-w-2xl space-y-2">
        <p className="o-eyebrow">{t('GEDCOM import')}</p>
        <h1 className="o-display text-3xl sm:text-4xl">{t('Bring your research with you.')}</h1>
        <p className="text-[15px] leading-7 text-ink-muted">
          {t('Upload a .ged file from MyHeritage, Ancestry, FamilySearch, or any other genealogy tool to create or extend a family tree — people, relationships, and photos included.')}
        </p>
      </header>

      <div className="o-card mt-8 max-w-xl p-6 sm:p-7">
        {!busy ? (
          <div className="flex flex-col gap-4">
            <FormError message={error} />
            <div className="flex flex-col gap-1.5">
              <label className="o-label">{t('GEDCOM file')}</label>
              <input
                type="file"
                accept=".ged,.gedcom,text/plain"
                onChange={(e) => setFile(e.target.files?.[0] ?? null)}
                className="o-input"
              />
            </div>
            <Select label={t('Destination')} value={target} onChange={(e) => setTarget(e.target.value)}>
              <option value="new">{t('Create a new tree')}</option>
              {trees?.map((tree) => (
                <option key={tree.id} value={tree.id}>
                  {t('Import into: {name}', { name: tree.name })}
                </option>
              ))}
            </Select>
            {target === 'new' && (
              <TextField
                label={t('New tree name (optional)')}
                value={treeName}
                onChange={(e) => setTreeName(e.target.value)}
                placeholder={t('Defaults to the file name')}
              />
            )}
            <Button onClick={submit} disabled={!file} loading={submitting}>
              {t('Start import')}
            </Button>
          </div>
        ) : (
          <div className="flex flex-col gap-5">
            <div className="flex flex-col gap-2">
              <div className="flex items-baseline justify-between gap-3">
                <p className="text-sm font-medium text-ink">{progress?.message ? t(progress.message) : null}</p>
                <span className="text-sm tabular-nums text-ink-muted">{pct}%</span>
              </div>
              <div className="h-2 w-full overflow-hidden rounded-full bg-paper-deep">
                <div
                  className={`h-full transition-all duration-500 ${done ? 'bg-emerald-400' : 'bg-emerald-500'}`}
                  style={{ width: `${pct}%` }}
                />
              </div>
              {progress?.total != null && progress.total > 0 && progress.stage === 'people' && (
                <p className="text-xs text-ink-muted">
                  {t('{current} of {total} people processed', { current: progress.current ?? 0, total: progress.total })}
                </p>
              )}
            </div>

            <ol className="flex flex-col gap-3 border-t border-line pt-4">
              {queued && (
                <li className="flex items-center gap-3 text-sm text-ink-muted">
                  <span className="h-4 w-4 animate-spin rounded-full border-2 border-edge border-t-emerald-400" />
                  {t('Waiting for a worker to pick up the import…')}
                </li>
              )}
              {STAGES.map((s, i) => {
                const state = done || i < activeIndex ? 'done' : i === activeIndex ? 'active' : 'pending';
                const detail =
                  s.key === 'people' && i === activeIndex && progress?.total
                    ? t('{current} of {total} people', { current: progress.current ?? 0, total: progress.total })
                    : undefined;
                return <StageRow key={s.key} state={state} label={t(s.label)} hint={t(s.hint)} detail={detail} />;
              })}
            </ol>

            {done && (
              <p className="text-sm font-medium text-accent">
                {progress?.people_created != null
                  ? t('Added {people} people and {rels} relationships. ', {
                      people: progress.people_created,
                      rels: progress.relationships_created ?? 0,
                    })
                  : ''}
                {t('Opening your tree…')}
              </p>
            )}
            {!done && (
              <p className="text-xs leading-5 text-ink-muted">
                {t('Photos and other media linked to external URLs continue downloading in the background after the import finishes.')}
              </p>
            )}
          </div>
        )}
      </div>
    </AppLayout>
  );
}
