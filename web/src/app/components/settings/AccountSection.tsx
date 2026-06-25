import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { getBlob } from '@core/api/client';
import { settingsApi } from '@core/api/endpoints/settings';
import { useLogout } from '@core/auth/hooks';
import { Button, FormError, TextField } from '../ui';
import { downloadBlob } from '../../lib/download';

export function AccountSection() {
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
    if (!window.confirm('Permanently delete your account and all your data? This cannot be undone.')) return;
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
        <h3 className="font-medium text-neutral-900">Export your data</h3>
        <p className="text-sm text-neutral-500">Download a JSON copy of your profile, trees, and activity.</p>
        <div>
          <Button onClick={exportData}>Download data export</Button>
        </div>
      </div>

      <div className="flex flex-col gap-3 rounded-xl border border-red-200 p-4">
        <h3 className="font-medium text-red-700">Delete account</h3>
        <p className="text-sm text-neutral-500">This permanently removes your account and data.</p>
        <FormError message={error} />
        <TextField label="Confirm your password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
        <div>
          <button
            onClick={deleteAccount}
            disabled={busy || !password}
            className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
          >
            Delete my account
          </button>
        </div>
      </div>
    </div>
  );
}
