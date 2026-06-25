import { useEffect } from 'react';
import { clearToken } from '../api/client';
import { authApi } from '../api/endpoints/auth';
import { useAuthStore } from './store';

// Resolves the session on startup: if a persisted token exists (status 'unknown'),
// confirm it via /me. Platform-agnostic — used by both web and React Native shells.
export function useResolveSession(): void {
  const token = useAuthStore((s) => s.token);
  const status = useAuthStore((s) => s.status);

  useEffect(() => {
    if (status !== 'unknown') return;

    if (!token) {
      useAuthStore.getState().markGuest();
      return;
    }

    let cancelled = false;
    authApi
      .me()
      .then((user) => {
        if (!cancelled) useAuthStore.getState().setUser(user);
      })
      .catch(async () => {
        await clearToken();
        if (!cancelled) useAuthStore.getState().clear();
      });

    return () => {
      cancelled = true;
    };
  }, [token, status]);
}
