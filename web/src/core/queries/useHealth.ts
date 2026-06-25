import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../api/client';

export interface HealthResponse {
  status: string;
  app: string;
  time: string;
}

// Public, unauthenticated endpoint — used to prove API reachability + CORS.
export function useHealth() {
  return useQuery({
    queryKey: ['health'],
    queryFn: () => apiClient.get<HealthResponse>('/api/v1/health'),
  });
}
