import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClientProvider } from '@tanstack/react-query';
import { RouterProvider } from 'react-router-dom';
import { queryClient } from '@core/queries/queryClient';
import { bootstrapApi } from './platform/bootstrap';
import { SessionGate } from './app/SessionGate';
import { router } from './app/routes';
import './index.css';

bootstrapApi();

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <SessionGate>
        <RouterProvider router={router} />
      </SessionGate>
    </QueryClientProvider>
  </StrictMode>,
);
