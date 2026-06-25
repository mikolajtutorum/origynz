import { useEffect, useState } from 'react';
import { settingsApi, type ApiToken } from '@core/api/endpoints/settings';
import { Button, FormError, TextField } from '../ui';

export function ApiTokensSection() {
  const [tokens, setTokens] = useState<ApiToken[]>([]);
  const [name, setName] = useState('');
  const [abilities, setAbilities] = useState<string[]>(['read']);
  const [plainToken, setPlainToken] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const refresh = () => settingsApi.tokens.list().then(setTokens).catch(() => {});
  useEffect(() => {
    refresh();
  }, []);

  const toggleAbility = (a: string) =>
    setAbilities((prev) => (prev.includes(a) ? prev.filter((x) => x !== a) : [...prev, a]));

  const create = async () => {
    if (!name.trim()) return;
    setError(null);
    setBusy(true);
    try {
      const result = await settingsApi.tokens.create(name.trim(), abilities);
      setPlainToken(result.plain_text_token);
      setName('');
      await refresh();
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setBusy(false);
    }
  };

  const revoke = async (id: string) => {
    await settingsApi.tokens.revoke(id);
    await refresh();
  };

  return (
    <div className="flex max-w-2xl flex-col gap-5">
      <div className="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4">
        <h3 className="font-medium text-neutral-900">Create API token</h3>
        <FormError message={error} />
        <TextField label="Token name" value={name} onChange={(e) => setName(e.target.value)} />
        <div className="flex gap-4 text-sm">
          {['read', 'write'].map((a) => (
            <label key={a} className="flex items-center gap-2">
              <input type="checkbox" checked={abilities.includes(a)} onChange={() => toggleAbility(a)} />
              {a}
            </label>
          ))}
        </div>
        <div>
          <Button onClick={create} loading={busy}>
            Create token
          </Button>
        </div>
        {plainToken && (
          <div className="rounded-md bg-amber-50 p-3 text-sm">
            <p className="mb-1 font-medium text-amber-800">Copy this token now — it won't be shown again:</p>
            <code className="block break-all font-mono text-xs text-amber-900">{plainToken}</code>
          </div>
        )}
      </div>

      <div>
        <h3 className="mb-2 font-medium text-neutral-900">Active tokens</h3>
        {tokens.length === 0 ? (
          <p className="text-sm text-neutral-500">No tokens yet.</p>
        ) : (
          <ul className="divide-y divide-neutral-200 rounded-xl border border-neutral-200">
            {tokens.map((t) => (
              <li key={t.id} className="flex items-center justify-between px-4 py-3">
                <div>
                  <p className="text-sm font-medium text-neutral-900">{t.name}</p>
                  <p className="text-xs text-neutral-400">
                    {(t.abilities ?? []).join(', ') || '—'} · last used {t.last_used_at ?? 'never'}
                  </p>
                </div>
                <button onClick={() => revoke(t.id)} className="text-sm text-red-600 hover:underline">
                  Revoke
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}
