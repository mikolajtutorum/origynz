import { useEffect, useState } from 'react';
import { settingsApi, type TwoFactorState } from '@core/api/endpoints/settings';
import { useAuthStore } from '@core/auth/store';
import { Button, FormError, TextField } from '../ui';

function PasswordChange() {
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
      <h3 className="font-medium text-ink">Change password</h3>
      <FormError message={error} />
      {saved && <p className="o-alert-success">Password updated.</p>}
      <TextField label="Current password" type="password" value={form.current_password} onChange={(e) => setForm({ ...form, current_password: e.target.value })} />
      <TextField label="New password" type="password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} />
      <TextField label="Confirm new password" type="password" value={form.password_confirmation} onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })} />
      <div>
        <Button onClick={submit} loading={busy}>
          Update password
        </Button>
      </div>
    </div>
  );
}

function TwoFactor() {
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

  if (!state) return <p className="text-sm text-ink-muted">Loading two-factor status…</p>;

  return (
    <div className="flex max-w-lg flex-col gap-3">
      <h3 className="font-medium text-ink">Two-factor authentication</h3>
      <FormError message={error} />

      {!state.enabled && (
        <>
          <p className="text-sm text-ink-muted">Add an authenticator app for extra security.</p>
          <TextField label="Current password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
          <div>
            <Button loading={busy} onClick={() => run(() => settingsApi.twoFactor.enable(password))}>
              Enable 2FA
            </Button>
          </div>
        </>
      )}

      {state.enabled && !state.confirmed && (
        <>
          <p className="text-sm text-ink-muted">Scan this QR code, then enter the 6-digit code to confirm.</p>
          {state.qr_svg && <div className="w-40" dangerouslySetInnerHTML={{ __html: state.qr_svg }} />}
          {state.secret && <p className="font-mono text-xs text-ink-muted">Secret: {state.secret}</p>}
          <TextField label="Authentication code" value={code} onChange={(e) => setCode(e.target.value)} />
          <div>
            <Button loading={busy} onClick={() => run(() => settingsApi.twoFactor.confirm(code))}>
              Confirm
            </Button>
          </div>
        </>
      )}

      {state.enabled && state.confirmed && (
        <>
          <p className="o-alert-success">Two-factor authentication is on.</p>
          {state.recovery_codes.length > 0 && (
            <div className="rounded-2xl border border-line bg-paper p-4">
              <p className="mb-2 text-xs font-medium text-ink-soft">Recovery codes — store these safely:</p>
              <ul className="grid grid-cols-2 gap-1 font-mono text-xs text-ink-soft">
                {state.recovery_codes.map((c) => (
                  <li key={c}>{c}</li>
                ))}
              </ul>
            </div>
          )}
          <TextField label="Current password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
          <div className="flex gap-2">
            <Button loading={busy} onClick={() => run(() => settingsApi.twoFactor.regenerate(password))}>
              Regenerate codes
            </Button>
            <button
              onClick={() => run(() => settingsApi.twoFactor.disable(password))}
              className="o-btn-danger-soft o-btn-sm"
            >
              Disable 2FA
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
