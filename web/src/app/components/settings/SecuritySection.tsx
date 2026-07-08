import { useEffect, useState } from 'react';
import { settingsApi, type TwoFactorState } from '@core/api/endpoints/settings';
import { useAuthStore } from '@core/auth/store';
import { useT } from '@core/i18n';
import { Button, FormError, TextField } from '../ui';

function PasswordChange() {
  const t = useT();
  const [form, setForm] = useState({ current_password: '', password: '', password_confirmation: '' });
  const [error, setError] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);
  const [busy, setBusy] = useState(false);

  const submit = async () => {
    setError(null);
    setSaved(false);
    setBusy(true);
    try {
      await settingsApi.updatePassword(form);
      setForm({ current_password: '', password: '', password_confirmation: '' });
      setSaved(true);
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="flex max-w-lg flex-col gap-3">
      <h3 className="font-medium text-ink">{t('Change password')}</h3>
      <FormError message={error} />
      {saved && <p className="o-alert-success">{t('Password updated.')}</p>}
      <TextField label={t('Current password')} type="password" value={form.current_password} onChange={(e) => setForm({ ...form, current_password: e.target.value })} />
      <TextField label={t('New password')} type="password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} />
      <TextField label={t('Confirm new password')} type="password" value={form.password_confirmation} onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })} />
      <div>
        <Button onClick={submit} loading={busy}>
          {t('Update password')}
        </Button>
      </div>
    </div>
  );
}

function TwoFactor() {
  const t = useT();
  const [state, setState] = useState<TwoFactorState | null>(null);
  const [password, setPassword] = useState('');
  const [code, setCode] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    settingsApi.twoFactor.show().then(setState).catch(() => {});
  }, []);

  const run = async (fn: () => Promise<TwoFactorState | unknown>) => {
    setError(null);
    setBusy(true);
    try {
      await fn();
      const fresh = await settingsApi.twoFactor.show();
      setState(fresh);
      // Keep the auth-store flag in sync for the dashboard badge.
      const current = useAuthStore.getState().user;
      if (current) {
        useAuthStore.getState().setUser({ ...current, two_factor_enabled: fresh.enabled && fresh.confirmed });
      }
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setBusy(false);
    }
  };

  if (!state) return <p className="text-sm text-ink-muted">{t('Loading two-factor status…')}</p>;

  return (
    <div className="flex max-w-lg flex-col gap-3">
      <h3 className="font-medium text-ink">{t('Two-factor authentication')}</h3>
      <FormError message={error} />

      {!state.enabled && (
        <>
          <p className="text-sm text-ink-muted">{t('Add an authenticator app for extra security.')}</p>
          <TextField label={t('Current password')} type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
          <div>
            <Button loading={busy} onClick={() => run(() => settingsApi.twoFactor.enable(password))}>
              {t('Enable 2FA')}
            </Button>
          </div>
        </>
      )}

      {state.enabled && !state.confirmed && (
        <>
          <p className="text-sm text-ink-muted">{t('Scan this QR code, then enter the 6-digit code to confirm.')}</p>
          {state.qr_svg && <div className="w-40" dangerouslySetInnerHTML={{ __html: state.qr_svg }} />}
          {state.secret && <p className="font-mono text-xs text-ink-muted">{t('Secret')}: {state.secret}</p>}
          <TextField label={t('Authentication code')} value={code} onChange={(e) => setCode(e.target.value)} />
          <div>
            <Button loading={busy} onClick={() => run(() => settingsApi.twoFactor.confirm(code))}>
              {t('Confirm')}
            </Button>
          </div>
        </>
      )}

      {state.enabled && state.confirmed && (
        <>
          <p className="o-alert-success">{t('Two-factor authentication is on.')}</p>
          {state.recovery_codes.length > 0 && (
            <div className="rounded-2xl border border-line bg-paper p-4">
              <p className="mb-2 text-xs font-medium text-ink-soft">{t('Recovery codes — store these safely:')}</p>
              <ul className="grid grid-cols-2 gap-1 font-mono text-xs text-ink-soft">
                {state.recovery_codes.map((c) => (
                  <li key={c}>{c}</li>
                ))}
              </ul>
            </div>
          )}
          <TextField label={t('Current password')} type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
          <div className="flex gap-2">
            <Button loading={busy} onClick={() => run(() => settingsApi.twoFactor.regenerate(password))}>
              {t('Regenerate codes')}
            </Button>
            <button
              onClick={() => run(() => settingsApi.twoFactor.disable(password))}
              className="o-btn-danger-soft o-btn-sm"
            >
              {t('Disable 2FA')}
            </button>
          </div>
        </>
      )}
    </div>
  );
}

export function SecuritySection() {
  return (
    <div className="flex flex-col gap-8">
      <PasswordChange />
      <TwoFactor />
    </div>
  );
}
