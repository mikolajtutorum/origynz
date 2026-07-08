import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { getBlob } from '@core/api/client';
import { settingsApi } from '@core/api/endpoints/settings';
import { useLogout } from '@core/auth/hooks';
import { useT } from '@core/i18n';
import { Button, FormError, TextField } from '../ui';
import { downloadBlob } from '../../lib/download';

export function AccountSection() {
  const t = useT();
  const navigate = useNavigate();
  const logout = useLogout();
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const exportData = async () => {
    const blob = await getBlob(settingsApi.dataExportPath());
    downloadBlob(blob, `origynz-data-export-${new Date().toISOString().slice(0, 10)}.json`);
  };

  const deleteAccount = async () => {
    if (!window.confirm(t('Permanently delete your account and all your data? This cannot be undone.'))) return;
    setError(null);
    setBusy(true);
    try {
      await settingsApi.deleteAccount(password);
      // The token is now invalid; clear local session and go to login.
      await logout.mutateAsync().catch(() => {});
      navigate('/login', { replace: true });
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="flex max-w-lg flex-col gap-8">
      <div className="flex flex-col gap-3">
        <h3 className="font-medium text-ink">{t('Export your data')}</h3>
        <p className="text-sm text-ink-muted">{t('Download a JSON copy of your profile, trees, and activity.')}</p>
        <div>
          <Button onClick={exportData}>{t('Download data export')}</Button>
        </div>
      </div>

      <div className="flex flex-col gap-3 rounded-xl border border-red-200 p-4">
        <h3 className="font-medium text-danger-strong">{t('Delete account')}</h3>
        <p className="text-sm text-ink-muted">{t('This permanently removes your account and data.')}</p>
        <FormError message={error} />
        <TextField label={t('Confirm your password')} type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
        <div>
          <button
            onClick={deleteAccount}
            disabled={busy || !password}
            className="o-btn-danger"
          >
            {t('Delete my account')}
          </button>
        </div>
      </div>
    </div>
  );
}
