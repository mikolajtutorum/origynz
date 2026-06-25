import { apiClient } from '../client';
import type { User } from '../../models';

export interface TwoFactorState {
  enabled: boolean;
  confirmed: boolean;
  qr_svg: string | null;
  secret: string | null;
  recovery_codes: string[];
}

export interface ApiToken {
  id: string;
  name: string;
  abilities: string[] | null;
  last_used_at: string | null;
  created_at: string | null;
}

export interface ProfilePayload {
  name: string;
  email: string;
  first_name?: string | null;
  last_name?: string | null;
  country_of_residence?: string | null;
}

export const settingsApi = {
  updateProfile: (payload: ProfilePayload) =>
    apiClient.patch<{ data: User }>('/api/v1/settings/profile', payload).then((r) => r.data),

  updatePassword: (payload: { current_password: string; password: string; password_confirmation: string }) =>
    apiClient.put<{ message: string }>('/api/v1/settings/password', payload),

  deleteAccount: (password: string) =>
    apiClient.delete<{ message: string }>('/api/v1/settings/account', { password }),

  twoFactor: {
    show: () => apiClient.get<TwoFactorState>('/api/v1/settings/two-factor'),
    enable: (current_password: string) =>
      apiClient.post<TwoFactorState>('/api/v1/settings/two-factor', { current_password }),
    confirm: (code: string) =>
      apiClient.post<TwoFactorState>('/api/v1/settings/two-factor/confirm', { code }),
    regenerate: (current_password: string) =>
      apiClient.post<{ recovery_codes: string[] }>('/api/v1/settings/two-factor/recovery-codes', { current_password }),
    disable: (current_password: string) =>
      apiClient.delete<unknown>('/api/v1/settings/two-factor', { current_password }),
  },

  tokens: {
    list: () => apiClient.get<{ data: ApiToken[] }>('/api/v1/settings/api-tokens').then((r) => r.data),
    create: (name: string, abilities: string[]) =>
      apiClient.post<{ plain_text_token: string; token: ApiToken }>('/api/v1/settings/api-tokens', { name, abilities }),
    revoke: (id: string) => apiClient.delete<{ message: string }>(`/api/v1/settings/api-tokens/${id}`),
  },

  dataExportPath: () => '/api/v1/settings/data-export',
};
