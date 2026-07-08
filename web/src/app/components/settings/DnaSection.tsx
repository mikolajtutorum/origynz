import { useEffect, useRef, useState } from 'react';
import { dnaApi, type DnaKit } from '@core/api/endpoints/dna';
import { useT } from '@core/i18n';
import { FormError } from '../ui';

function KitCard({ kit, onRename, onRemove }: { kit: DnaKit; onRename: (name: string) => void; onRemove: () => void }) {
  const t = useT();
  return (
    <li className="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
      <div className="min-w-0">
        <p className="truncate text-sm font-medium text-ink">{kit.kit_name || t('Untitled kit')}</p>
        <p className="text-xs text-ink-muted">
          {kit.provider_label} · {t('{count} SNPs', { count: kit.snp_count.toLocaleString() })}
          {kit.person_name ? ` · ${t('linked to {name}', { name: kit.person_name })}` : ''}
        </p>
        {(kit.haplogroup_y || kit.haplogroup_mt) && (
          <p className="mt-0.5 text-xs text-ink-muted">
            {kit.haplogroup_y && <span>{t('Y-DNA {group}', { group: kit.haplogroup_y })}</span>}
            {kit.haplogroup_y && kit.haplogroup_mt && ' · '}
            {kit.haplogroup_mt && <span>{t('mtDNA {group}', { group: kit.haplogroup_mt })}</span>}
          </p>
        )}
      </div>
      <div className="flex shrink-0 gap-3">
        <button
          onClick={() => {
            const name = window.prompt(t('Rename kit'), kit.kit_name ?? '');
            if (name !== null) onRename(name);
          }}
          className="text-xs font-medium text-ink-muted hover:text-ink hover:underline"
        >
          {t('Rename')}
        </button>
        <button
          onClick={() => window.confirm(t('Delete this DNA kit?')) && onRemove()}
          className="text-xs font-medium text-danger hover:text-danger-strong hover:underline"
        >
          {t('Delete')}
        </button>
      </div>
    </li>
  );
}

export function DnaSection() {
  const t = useT();
  const [kits, setKits] = useState<DnaKit[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const fileInput = useRef<HTMLInputElement>(null);

  const refresh = () => dnaApi.list().then(setKits).catch(() => {});
  useEffect(() => {
    refresh();
  }, []);

  const onUpload = async (file: File) => {
    setError(null);
    setBusy(true);
    try {
      await dnaApi.upload(file);
      await refresh();
    } catch (e) {
      const err = e as { validationErrors?: Record<string, string[]>; message?: string };
      setError(err.validationErrors?.file?.[0] ?? err.message ?? 'Upload failed.');
    } finally {
      setBusy(false);
      if (fileInput.current) fileInput.current.value = '';
    }
  };

  const rename = async (id: string, kit_name: string) => {
    await dnaApi.update(id, { kit_name });
    await refresh();
  };

  const remove = async (id: string) => {
    await dnaApi.remove(id);
    await refresh();
  };

  return (
    <div className="flex max-w-2xl flex-col gap-5">
      <div className="flex flex-col gap-3 rounded-xl border border-line p-4">
        <h3 className="font-medium text-ink">{t('Upload a raw DNA file')}</h3>
        <p className="text-sm text-ink-muted">
          {t('Export the raw data from 23andMe, AncestryDNA, FamilyTreeDNA, MyHeritage or Living DNA and upload it here. The provider, haplogroups and SNP count are detected automatically.')}
        </p>
        <FormError message={error} />
        <input
          ref={fileInput}
          type="file"
          accept=".txt,.csv,.tsv,.zip"
          onChange={(e) => {
            const file = e.target.files?.[0];
            if (file) void onUpload(file);
          }}
          className="text-sm text-ink-soft file:mr-3 file:rounded-full file:border-0 file:bg-fill file:px-4 file:py-2 file:text-sm file:font-medium file:text-ink hover:file:bg-fill-strong"
        />
        {busy && <p className="text-xs text-ink-muted">{t('Uploading and analysing…')}</p>}
      </div>

      <div>
        <h3 className="mb-2 font-medium text-ink">{t('Your DNA kits')}</h3>
        {kits.length === 0 ? (
          <p className="text-sm text-ink-muted">{t('No kits uploaded yet.')}</p>
        ) : (
          <ul className="divide-y divide-line/70 rounded-xl border border-line">
            {kits.map((kit) => (
              <KitCard key={kit.id} kit={kit} onRename={(name) => rename(kit.id, name)} onRemove={() => remove(kit.id)} />
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}
