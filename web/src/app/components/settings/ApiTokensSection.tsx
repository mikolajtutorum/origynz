import { useEffect, useState } from 'react';
import { settingsApi, type ApiToken } from '@core/api/endpoints/settings';
import { useT } from '@core/i18n';
import { Button, FormError, TextField } from '../ui';

export function ApiTokensSection() {
  const t = useT();
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
      <div className="flex flex-col gap-3 rounded-xl border border-line p-4">
        <h3 className="font-medium text-ink">{t('Create API token')}</h3>
        <FormError message={error} />
        <TextField label={t('Token name')} value={name} onChange={(e) => setName(e.target.value)} />
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
            {t('Create token')}
          </Button>
        </div>
        {plainToken && (
          <div className="rounded-2xl border border-copper-400/25 bg-copper-400/10 p-4 text-sm">
            <p className="mb-1 font-medium text-copper-800 dark:text-copper-200">{t("Copy this token now — it won't be shown again:")}</p>
            <code className="block break-all font-mono text-xs text-amber-900">{plainToken}</code>
          </div>
        )}
      </div>

      <div>
        <h3 className="mb-2 font-medium text-ink">{t('Active tokens')}</h3>
        {tokens.length === 0 ? (
          <p className="text-sm text-ink-muted">{t('No tokens yet.')}</p>
        ) : (
          <ul className="divide-y divide-line/70 rounded-xl border border-line">
            {tokens.map((token) => (
              <li key={token.id} className="flex items-center justify-between px-4 py-3">
                <div>
                  <p className="text-sm font-medium text-ink">{token.name}</p>
                  <p className="text-xs text-ink-muted">
                    {(token.abilities ?? []).join(', ') || '—'} · {t('last used {when}', { when: token.last_used_at ?? t('never') })}
                  </p>
                </div>
                <button onClick={() => revoke(token.id)} className="text-sm font-medium text-danger hover:text-danger-strong hover:underline">
                  {t('Revoke')}
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}
