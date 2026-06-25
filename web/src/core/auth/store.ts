import { create } from 'zustand';
import type { User } from '../models/user';

export type AuthStatus = 'unknown' | 'authenticated' | 'guest';

interface AuthState {
  token: string | null;
  user: User | null;
  status: AuthStatus;
  /** Set after a successful login/register/2FA/social exchange. */
  setSession: (token: string, user: User) => void;
  /** Refresh the user object (e.g. after /me or a profile update). */
  setUser: (user: User | null) => void;
  /** Mark the bootstrap token check as resolved with no session. */
  markGuest: () => void;
  clear: () => void;
}

// React-facing mirror of the auth session. The fetch client's source of truth
// for the token is the injected TokenStorage; this store keeps React in sync.
export const useAuthStore = create<AuthState>((set) => ({
  token: null,
  user: null,
  status: 'unknown',
  setSession: (token, user) => set({ token, user, status: 'authenticated' }),
  setUser: (user) => set((s) => ({ user, status: user ? 'authenticated' : s.status })),
  markGuest: () => set({ token: null, user: null, status: 'guest' }),
  clear: () => set({ token: null, user: null, status: 'guest' }),
}));
