import { apiClient } from '../client';
import type { User } from '../../models/user';

interface ResourceEnvelope<T> {
  data: T;
}

export interface AuthSuccess {
  data: User;
  token: string;
}

export interface TwoFactorRequired {
  two_factor_required: true;
}

export type LoginResult = AuthSuccess | TwoFactorRequired;

export function isTwoFactorRequired(result: LoginResult): result is TwoFactorRequired {
  return 'two_factor_required' in result;
}

export interface RegisterPayload {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  terms: boolean;
  age_confirmation: boolean;
  device_name?: string;
}

export interface LoginPayload {
  email: string;
  password: string;
  device_name?: string;
}

export interface TwoFactorChallengePayload {
  email: string;
  password: string;
  code?: string;
  recovery_code?: string;
  device_name?: string;
}

const DEVICE = 'web';

export const authApi = {
  register: (payload: RegisterPayload) =>
    apiClient.post<AuthSuccess>('/api/v1/auth/register', { device_name: DEVICE, ...payload }),

  login: (payload: LoginPayload) =>
    apiClient.post<LoginResult>('/api/v1/auth/login', { device_name: DEVICE, ...payload }),

  twoFactorChallenge: (payload: TwoFactorChallengePayload) =>
    apiClient.post<AuthSuccess>('/api/v1/auth/two-factor-challenge', { device_name: DEVICE, ...payload }),

  logout: () => apiClient.post<{ message: string }>('/api/v1/auth/logout'),

  me: () => apiClient.get<ResourceEnvelope<User>>('/api/v1/me').then((r) => r.data),

  stats: () =>
    apiClient.get<{ trees: number; profiles: number; living: number; relationships: number }>(
      '/api/v1/me/stats',
    ),

  onboarding: () =>
    apiClient.get<{
      in_progress: boolean;
      steps: { title: string; complete: boolean; cta: string | null; link: string | null }[];
    }>('/api/v1/me/onboarding'),
};
