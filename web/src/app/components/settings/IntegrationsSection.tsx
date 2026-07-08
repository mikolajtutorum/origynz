import { useEffect, useState } from 'react';
import { integrationsApi, type Integration } from '@core/api/endpoints/integrations';
import { useT } from '@core/i18n';
import { Button, FormError } from '../ui';

function WikiTreeConnect({ onConnected }: { onConnected: () => void }) {
  const t = useT();
  const [open, setOpen] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  if (!open) {
    return (
      <Button variant="secondary" onClick={() => setOpen(true)}>
        {t('Connect')}
      </Button>
    );
  }

  const submit = async () => {
    setError(null);
    setBusy(true);
    try {
      await integrationsApi.connectWikiTree(email, password);
      onConnected();
    } catch (e) {
      const err = e as { validationErrors?: Record<string, string[]>; message?: string };
      setError(err.validationErrors?.email?.[0] ?? err.message ?? t('Connection failed.'));
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center">
      <input
        type="email"
        value={email}
        onChange={(e) => setEmail(e.target.value)}
        placeholder={t('WikiTree email')}
        className="o-input text-sm"
      />
      <input
        type="password"
        value={password}
        onChange={(e) => setPassword(e.target.value)}
        placeholder={t('Password')}
        className="o-input text-sm"
      />
      <Button onClick={submit} loading={busy} disabled={!email || !password}>
        {t('Save')}
      </Button>
      {error && <FormError message={error} />}
    </div>
  );
}

function IntegrationRow({ integration, onChanged }: { integration: Integration; onChanged: () => void }) {
  const t = useT();
  const [busy, setBusy] = useState(false);

  const connect = async () => {
    setBusy(true);
    try {
      const url = await integrationsApi.authorizeUrl(integration.provider);
      window.location.href = url;
    } catch {
      setBusy(false);
    }
  };

  const disconnect = async () => {
    if (!window.confirm(t('Disconnect {label}?', { label: integration.label }))) return;
    await integrationsApi.disconnect(integration.provider);
    onChanged();
  };

  return (
    <li className="flex flex-col gap-3 rounded-xl border border-line p-4 sm:flex-row sm:items-center sm:justify-between">
      <div className="min-w-0">
        <div className="flex items-center gap-2">
          <p className="font-medium text-ink">{integration.label}</p>
          {integration.connected ? (
            <span className="o-chip-brand">{t('Connected')}</span>
          ) : !integration.configured ? (
            <span className="o-chip-muted">{t('Not available')}</span>
          ) : null}
        </div>
        <p className="mt-0.5 text-xs text-ink-muted">
          {integration.connected
            ? integration.username
              ? t('Signed in as {name}', { name: integration.username })
              : t('Signed in')
            : integration.configured
              ? integration.type === 'oauth'
                ? t('Connect your account to search and import records.')
                : t('Sign in with your account to search and import records.')
              : t('This integration has not been configured on this server.')}
        </p>
      </div>

      <div className="shrink-0">
        {integration.connected ? (
          <button
            onClick={disconnect}
            className="text-sm font-medium text-danger hover:text-danger-strong hover:underline"
          >
            {t('Disconnect')}
          </button>
        ) : !integration.configured ? (
          <span className="text-xs text-ink-muted">—</span>
        ) : integration.type === 'credentials' ? (
          <WikiTreeConnect onConnected={onChanged} />
        ) : (
          <Button variant="secondary" onClick={connect} loading={busy}>
            {t('Connect')}
          </Button>
        )}
      </div>
    </li>
  );
}

export function IntegrationsSection() {
  const t = useT();
  const [integrations, setIntegrations] = useState<Integration[]>([]);
  const [loading, setLoading] = useState(true);

  const refresh = () =>
    integrationsApi
      .list()
      .then(setIntegrations)
      .catch(() => {})
      .finally(() => setLoading(false));

  useEffect(() => {
    refresh();
  }, []);

  if (loading) return <p className="text-sm text-ink-muted">{t('Loading integrations…')}</p>;

  return (
    <ul className="flex max-w-2xl flex-col gap-3">
      {integrations.map((i) => (
        <IntegrationRow key={i.provider} integration={i} onChanged={refresh} />
      ))}
    </ul>
  );
}
