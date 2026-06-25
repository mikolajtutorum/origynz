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
      <div className="flex flex-col gap-3">
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
        <div className="flex flex-col gap-1">
          <label className="text-sm font-medium text-neutral-700">File</label>
          <input type="file" onChange={(e) => setFile(e.target.files?.[0] ?? null)} className="text-sm" />
        </div>
        <Button onClick={submit} loading={upload.isPending}>
          Upload
        </Button>
      </div>
    </Modal>
  );
}

const SIDE = [
  { key: 'all', label: 'All media', filters: { kind: 'all', linked: 'all' } },
  { key: 'images', label: 'Photos', filters: { kind: 'images', linked: 'all' } },
  { key: 'linked', label: 'Linked to people', filters: { kind: 'all', linked: 'linked' } },
  { key: 'unlinked', label: 'Unlinked', filters: { kind: 'all', linked: 'unlinked' } },
] as const;

export function MediaLibrary() {
  const [active, setActive] = useState<string>('all');
  const [search, setSearch] = useState('');
  const current = SIDE.find((s) => s.key === active)!;
  const filters: MediaFilters = { ...current.filters, q: search } as MediaFilters;
  const { data: media, isLoading } = useGlobalMedia(filters);
  const remove = useRemoveMedia();
  const [uploading, setUploading] = useState(false);

  return (
    <AppLayout>
      <h1 className="mb-5 text-2xl font-semibold text-[#1f252b]">Photos &amp; media</h1>

      <div className="media-browser-shell">
        {/* Side nav */}
        <nav className="border-r border-[#ececec] py-1">
          {SIDE.map((s) => (
            <button
              key={s.key}
              onClick={() => setActive(s.key)}
              className={`media-browser-side-link w-full text-left ${active === s.key ? 'is-active' : ''}`}
            >
              {s.label}
            </button>
          ))}
        </nav>

        {/* Content */}
        <div className="media-browser-content">
          <div className="mb-6 flex items-center justify-between gap-4">
            <input
              placeholder="Search media…"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-72 rounded-md border border-[#d4dae1] px-3 py-2 text-sm"
            />
            <Button onClick={() => setUploading(true)}>Upload</Button>
          </div>

          {isLoading ? (
            <p className="text-sm text-neutral-500">Loading…</p>
          ) : media && media.length > 0 ? (
            <div className="media-library-grid">
              {media.map((m) => (
                <div key={m.id} className="overflow-hidden rounded-xl border border-[#ececec] bg-white shadow-sm">
                  <div className="flex h-36 items-center justify-center bg-[linear-gradient(145deg,#e9e9e9,#cacaca)]">
                    {m.is_image && m.preview_url ? (
                      <img src={m.preview_url} alt={m.title} className="h-full w-full object-cover" />
                    ) : (
                      <span className="text-3xl text-[#5c5c5c]">📄</span>
                    )}
                  </div>
                  <div className="p-3">
                    <p className="truncate text-sm font-medium text-[#1f252b]">{m.title}</p>
                    <p className="truncate text-xs text-[#9aa3ab]">
                      {m.tree_name ? `${m.tree_name} · ` : ''}
                      {m.file_name}
                    </p>
                    <div className="mt-2 flex justify-between text-xs">
                      <a href={m.download_url} className="text-[#2563eb] hover:underline">
                        Download
                      </a>
                      <button onClick={() => remove.mutate(m.id)} className="text-[#c0392b] hover:underline">
                        Delete
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <p className="rounded-xl border border-dashed border-neutral-300 p-10 text-center text-neutral-500">
              No media here yet. Upload a photo or document to get started.
            </p>
          )}
        </div>
      </div>

      {uploading && <UploadModal onClose={() => setUploading(false)} />}
    </AppLayout>
  );
}
