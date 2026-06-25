import type { ReactNode } from 'react';
import { useResolveSession } from '@core/auth/session';
import { useAuthStore } from '@core/auth/store';
import { FullScreenSpinner } from './components/Spinner';

// Blocks first paint until the persisted token (if any) has been confirmed via /me,
// so guarded routes don't flash the login screen for an already-authenticated user.
export function SessionGate({ children }: { children: ReactNode }) {
  useResolveSession();
  const status = useAuthStore((s) => s.status);

  if (status === 'unknown') return <FullScreenSpinner />;
  return <>{children}</>;
}
