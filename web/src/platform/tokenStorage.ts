import type { TokenStorage } from '@core/auth/storage';

// Web implementation of the platform-agnostic TokenStorage contract.
// React Native would supply an Expo SecureStore implementation instead.
const KEY = 'origynz.token';

export const webTokenStorage: TokenStorage = {
  get: () => localStorage.getItem(KEY),
  set: (token: string) => localStorage.setItem(KEY, token),
  clear: () => localStorage.removeItem(KEY),
};
