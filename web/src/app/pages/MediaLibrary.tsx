import { useState } from 'react';
import type { Person } from '@core/models';
import type { MediaFilters } from '@core/api/endpoints/media';
import { useGlobalMedia, useRemoveMedia, useUploadMedia } from '@core/queries/media';
import { useTrees } from '@core/queries/trees';
import { AppLayout } from '../components/AppLayout';
import { PersonSearchInput } from '../components/PersonSearchInput';
import { Button, FormError, Modal, Select, TextField } from '../components/ui';

function UploadModal({ onClose }: { onClose: () => void }) {
  const { data: trees } = useTrees();
  const [treeId, setTreeId] = useState('');
  const [title, setTitle] = useState('');
  const [file, setFile] = useState<File | null>(null);
  const [person, setPerson] = useState<Person | null>(null);
  const [error, setError] = useState<string | null>(null);
  const upload = useUploadMedia(treeId);

  const submit = async () => {
    if (!treeId || !file || !title.trim()) {
      setError('Tree, title and a file are required.');
      return;
    }
    setError(null);
    const form = new FormData();
    form.append('title', title.trim());
    form.append('media_file', file);
    // Linking to a person makes the photo that person's avatar in the tree.
    if (person) {
      form.append('person_id', person.id);
      form.append('is_primary', '1');
    }
    try {
      await upload.mutateAsync(form);
      onClose();
    } catch (e) {
      setError((e as Error).message);
    }
  };

  return (
    <Modal title="Upload media" onClose={onClose}>
      <div className="flex flex-col gap-4">
        <FormError message={error} />
        <Select
          label="Tree"
          value={treeId}
          onChange={(e) => {
            setTreeId(e.target.value);
            setPerson(null);
          }}
        >
          <option value="">Select a tree…</option>
          {trees?.map((t) => (
            <option key={t.id} value={t.id}>
              {t.name}
            </option>
          ))}
        </Select>
        <TextField label="Title" value={title} onChange={(e) => setTitle(e.target.value)} />
        {treeId && (
          <PersonSearchInput
            label="Link to a person (optional — sets their photo)"
            selected={person}
            onSelect={setPerson}
            treeId={treeId}
          />
        )}
        <div className="flex flex-col gap-1.5">
          <label className="o-label">File</label>
          <input type="file" onChange={(e) => setFile(e.target.files?.[0] ?? null)} className="o-input" />
        </div>
        <Button onClick={submit} loading={upload.isPending}>
          Upload
        </Button>
      </div>
    </Modal>
  );
}

const FILTERS = [
  { key: 'all', label: 'All media', filters: { kind: 'all', linked: 'all' } },
  { key: 'images', label: 'Photos', filters: { kind: 'images', linked: 'all' } },
  { key: 'linked', label: 'Linked to people', filters: { kind: 'all', linked: 'linked' } },
  { key: 'unlinked', label: 'Unlinked', filters: { kind: 'all', linked: 'unlinked' } },
] as const;

export function MediaLibrary() {
  const [active, setActive] = useState<string>('all');
  const [search, setSearch] = useState('');
  const current = FILTERS.find((s) => s.key === active)!;
  const filters: MediaFilters = { ...current.filters, q: search } as MediaFilters;
  const { data: media, isLoading } = useGlobalMedia(filters);
  const remove = useRemoveMedia();
  const [uploading, setUploading] = useState(false);

  return (
    <AppLayout>
      <div className="space-y-7">
        <header className="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
          <div className="max-w-2xl space-y-2">
            <p className="o-eyebrow">Media library</p>
            <h1 className="o-display text-3xl sm:text-4xl">Photos &amp; media</h1>
            <p className="text-[15px] leading-7 text-ink-muted">
              Every photo and document across your trees — link them to people to give profiles a face.
            </p>
          </div>
          <button onClick={() => setUploading(true)} className="o-btn-primary">
            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <path d="M12 19V5M5 12l7-7 7 7" />
            </svg>
            Upload
          </button>
        </header>

        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="o-subnav" role="tablist" aria-label="Media filters">
            {FILTERS.map((s) => (
              <button
                key={s.key}
                role="tab"
                aria-selected={active === s.key}
                onClick={() => setActive(s.key)}
                className={`o-subnav-link ${active === s.key ? 'is-active' : ''}`}
              >
                {s.label}
              </button>
            ))}
          </div>
          <div className="relative sm:w-72">
            <input
              placeholder="Search media…"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="o-input rounded-full pl-10"
              aria-label="Search media"
            />
            <svg className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
              <circle cx="11" cy="11" r="7" />
              <path d="m20 20-3.5-3.5" />
            </svg>
          </div>
        </div>

        {isLoading ? (
          <p className="text-sm text-ink-muted">Loading…</p>
        ) : media && media.length > 0 ? (
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            {media.map((m) => (
              <div key={m.id} className="o-card o-card-hover group overflow-hidden">
                <div className="flex aspect-[4/3] items-center justify-center bg-paper-deep">
                  {m.is_image && m.preview_url ? (
                    <img src={m.preview_url} alt={m.title} loading="lazy" className="h-full w-full object-cover" />
                  ) : (
                    <svg className="h-9 w-9 text-ink-muted/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round">
                      <path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" />
                      <path d="M14 2v6h6" />
                    </svg>
                  )}
                </div>
                <div className="p-3.5">
                  <p className="truncate text-sm font-medium text-ink">{m.title}</p>
                  <p className="truncate text-xs text-ink-muted">
                    {m.tree_name ? `${m.tree_name} · ` : ''}
                    {m.file_name}
                  </p>
                  <div className="mt-2.5 flex justify-between text-xs font-medium">
                    <a href={m.download_url} className="text-accent hover:text-accent-strong">
                      Download
                    </a>
                    <button
                      onClick={() => window.confirm(`Delete “${m.title}”?`) && remove.mutate(m.id)}
                      className="text-danger hover:text-danger-strong"
                    >
                      Delete
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="o-empty">
            <p className="font-medium text-ink-soft">No media here yet.</p>
            <p className="mt-1">Upload a photo or document to get started.</p>
            <button onClick={() => setUploading(true)} className="o-btn-primary o-btn-sm mt-5">
              Upload media
            </button>
          </div>
        )}
      </div>

      {uploading && <UploadModal onClose={() => setUploading(false)} />}
    </AppLayout>
  );
}
