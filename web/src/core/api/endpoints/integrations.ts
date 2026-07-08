import { apiClient } from '../client';

export interface Integration {
  provider: string;
  label: string;
  logo_url: string;
  type: 'oauth' | 'credentials';
  configured: boolean;
  connected: boolean;
  username: string | null;
  connected_at: string | null;
}

interface Listed<T> {
  data: T[];
}

export const integrationsApi = {
  list: () => apiClient.get<Listed<Integration>>('/api/v1/integrations').then((r) => r.data),

  /** Returns the provider OAuth authorization URL to redirect the browser to. */
  authorizeUrl: (provider: string) =>
    apiClient.post<{ url: string }>(`/api/v1/integrations/${provider}/authorize`).then((r) => r.url),

  connectWikiTree: (email: string, password: string) =>
    apiClient.post<{ connected: boolean }>('/api/v1/integrations/wikitree', { email, password }),

  disconnect: (provider: string) =>
    apiClient.delete<{ disconnected: boolean }>(`/api/v1/integrations/${provider}`),

  researchLinks: (personId: string) =>
    apiClient.get<ResearchLinks>(`/api/v1/people/${personId}/research-links`),
};

export interface ResearchLinks {
  findagrave: { search: string; memorial: string | null };
  billiongraves: { search: string; grave: string | null };
}
