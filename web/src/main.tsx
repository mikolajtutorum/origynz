import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClientProvider } from '@tanstack/react-query';
import { RouterProvider } from 'react-router-dom';
import { queryClient } from '@core/queries/queryClient';
import { bootstrapApi } from './platform/bootstrap';
import { watchSystemTheme } from './app/lib/theme';
import { SessionGate } from './app/SessionGate';
import { router } from './app/routes';
import './index.css';

bootstrapApi();
watchSystemTheme();

if (import.meta.env.PROD && 'serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  });
}

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <SessionGate>
        <RouterProvider router={router} />
      </SessionGate>
    </QueryClientProvider>
  </StrictMode>,
);
