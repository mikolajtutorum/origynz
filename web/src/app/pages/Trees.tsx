import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTrees, useCreateTree } from '@core/queries/trees';
import { treeSchema, type TreeFormValues } from '@core/validation/tree';
import { useT } from '@core/i18n';
import { AppLayout } from '../components/AppLayout';
import { FullScreenSpinner } from '../components/Spinner';
import { Button, FormError, Modal, Select, TextField, Textarea } from '../components/ui';
import { applyApiErrors } from '../lib/applyApiErrors';

function CreateTreeModal({ onClose }: { onClose: () => void }) {
  const t = useT();
  const create = useCreateTree();
  const navigate = useNavigate();
  const [formError, setFormError] = useState<string | null>(null);
  const { register, handleSubmit, setError, formState } = useForm<TreeFormValues>({
    resolver: zodResolver(treeSchema),
    defaultValues: { privacy: 'private' },
  });

  const onSubmit = handleSubmit(async (values) => {
    setFormError(null);
    try {
      const tree = await create.mutateAsync(values);
      navigate(`/trees/${tree.id}`);
    } catch (e) {
      setFormError(applyApiErrors(e, setError));
    }
  });

  return (
    <Modal title={t('New family tree')} onClose={onClose}>
      <form onSubmit={onSubmit} className="flex flex-col gap-4">
        <FormError message={formError} />
        <TextField label={t('Name')} placeholder="The Johnson-Moore Tree" error={formState.errors.name?.message} {...register('name')} />
        <Textarea label={t('Description')} error={formState.errors.description?.message} {...register('description')} />
        <TextField label={t('Home region')} placeholder="Manchester, England" error={formState.errors.home_region?.message} {...register('home_region')} />
        <Select label={t('Privacy')} {...register('privacy')}>
          <option value="private">{t('Private')}</option>
          <option value="invited">{t('Invited only')}</option>
          <option value="public">{t('Public')}</option>
        </Select>
        <Button type="submit" loading={create.isPending}>
          {t('Create tree')}
        </Button>
      </form>
    </Modal>
  );
}

export function Trees() {
  const t = useT();
  const { data: trees, isLoading } = useTrees();
  const [creating, setCreating] = useState(false);

  return (
    <AppLayout>
      <div className="space-y-8">
        <header className="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
          <div className="max-w-2xl space-y-2">
            <p className="o-eyebrow">{t('Tree management')}</p>
            <h1 className="o-display text-3xl sm:text-4xl">{t('Family trees')}</h1>
            <p className="text-[15px] leading-7 text-ink-muted">
              {t('Every branch, lineage, and research project you can access — open a workspace or start a new one.')}
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <Link to="/import" className="o-btn-secondary">
              {t('Import GEDCOM')}
            </Link>
            <button onClick={() => setCreating(true)} className="o-btn-primary">
              {t('New tree')}
            </button>
          </div>
        </header>

        {isLoading ? (
          <FullScreenSpinner />
        ) : trees && trees.length > 0 ? (
          <ul className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {trees.map((tree) => (
              <li key={tree.id}>
                <Link to={`/trees/${tree.id}`} className="o-card o-card-hover group flex h-full flex-col p-6">
                  <div className="flex items-start justify-between gap-3">
                    <h2 className="text-base font-semibold text-ink transition group-hover:text-accent">{tree.name}</h2>
                    <span className="o-chip-muted shrink-0 uppercase tracking-[0.14em]">{tree.privacy}</span>
                  </div>
                  <p className="mt-1 flex-1 text-sm text-ink-muted">{tree.home_region || t('Region not set yet')}</p>
                  <div className="mt-5 flex items-center justify-between text-sm">
                    <span className="text-ink-muted">{t('{count} people', { count: tree.people_count ?? 0 })}</span>
                    <span className="inline-flex items-center gap-1 font-semibold text-accent">
                      {t('Open')}
                      <svg className="h-3.5 w-3.5 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M5 12h14M13 6l6 6-6 6" />
                      </svg>
                    </span>
                  </div>
                </Link>
              </li>
            ))}
          </ul>
        ) : (
          <div className="o-empty">
            <p className="font-medium text-ink-soft">{t('No trees yet.')}</p>
            <p className="mt-1">{t('Create your first family tree to get started, or import a GEDCOM file.')}</p>
            <button onClick={() => setCreating(true)} className="o-btn-primary o-btn-sm mt-5">
              {t('Create a tree')}
            </button>
          </div>
        )}
      </div>

      {creating && <CreateTreeModal onClose={() => setCreating(false)} />}
    </AppLayout>
  );
}
