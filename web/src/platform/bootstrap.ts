import { configureApiClient } from '@core/api/client';
import { useAuthStore } from '@core/auth/store';
import { webTokenStorage } from './tokenStorage';

// Wire the platform-agnostic API client to web-specific concerns: the API base
// URL (Vite env) and the localStorage-backed token store. Called once before render.
export function bootstrapApi(): void {
  configureApiClient({
    baseUrl: import.meta.env.VITE_API_URL,
    tokenStorage: webTokenStorage,
    onUnauthorized: () => {
      void webTokenStorage.clear();
      useAuthStore.getState().clear();
    },
  });

  // Seed the React auth store from any persisted token; /me confirms it later.
  const token = localStorage.getItem('origynz.token');
  if (token) {
    useAuthStore.setState({ token, status: 'unknown' });
  } else {
    useAuthStore.getState().markGuest();
  }
}
