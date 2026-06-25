import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTrees, useCreateTree } from '@core/queries/trees';
import { treeSchema, type TreeFormValues } from '@core/validation/tree';
import { AppLayout } from '../components/AppLayout';
import { FullScreenSpinner } from '../components/Spinner';
import { Button, FormError, Modal, Select, TextField, Textarea } from '../components/ui';
import { applyApiErrors } from '../lib/applyApiErrors';

function CreateTreeModal({ onClose }: { onClose: () => void }) {
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
    <Modal title="New family tree" onClose={onClose}>
      <form onSubmit={onSubmit} className="flex flex-col gap-3">
        <FormError message={formError} />
        <TextField label="Name" error={formState.errors.name?.message} {...register('name')} />
        <Textarea label="Description" error={formState.errors.description?.message} {...register('description')} />
        <TextField label="Home region" error={formState.errors.home_region?.message} {...register('home_region')} />
        <Select label="Privacy" {...register('privacy')}>
          <option value="private">Private</option>
          <option value="invited">Invited only</option>
          <option value="public">Public</option>
        </Select>
        <Button type="submit" loading={create.isPending}>
          Create tree
        </Button>
      </form>
    </Modal>
  );
}

export function Trees() {
  const { data: trees, isLoading } = useTrees();
  const [creating, setCreating] = useState(false);

  return (
    <AppLayout>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-semibold text-neutral-900">Family trees</h1>
        <div className="flex gap-2">
          <Link
            to="/import"
            className="inline-flex items-center rounded-md border border-neutral-300 px-4 py-2 text-sm font-medium hover:bg-neutral-50"
          >
            Import GEDCOM
          </Link>
          <Button onClick={() => setCreating(true)}>New tree</Button>
        </div>
      </div>

      {isLoading ? (
        <FullScreenSpinner />
      ) : trees && trees.length > 0 ? (
        <ul className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {trees.map((tree) => (
            <li key={tree.id}>
              <Link
                to={`/trees/${tree.id}`}
                className="block rounded-xl border border-neutral-200 bg-white p-5 shadow-sm transition hover:border-neutral-400"
              >
                <h2 className="font-medium text-neutral-900">{tree.name}</h2>
                {tree.home_region && <p className="text-sm text-neutral-500">{tree.home_region}</p>}
                <p className="mt-2 text-xs text-neutral-400">
                  {tree.people_count ?? 0} people · {tree.privacy}
                </p>
              </Link>
            </li>
          ))}
        </ul>
      ) : (
        <p className="rounded-xl border border-dashed border-neutral-300 p-10 text-center text-neutral-500">
          No trees yet. Create your first family tree to get started.
        </p>
      )}

      {creating && <CreateTreeModal onClose={() => setCreating(false)} />}
    </AppLayout>
  );
}
