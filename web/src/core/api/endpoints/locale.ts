import { apiClient } from '../client';

export interface LocaleSuggestion {
  country: string | null;
  locale: string | null;
}

export const localeApi = {
  /** IP/geo-based locale hint from the server. Never throws — resolves to null on failure. */
  suggest: async (): Promise<LocaleSuggestion> => {
    try {
      return await apiClient.get<LocaleSuggestion>('/api/v1/locale');
    } catch {
      return { country: null, locale: null };
    }
  },
};
