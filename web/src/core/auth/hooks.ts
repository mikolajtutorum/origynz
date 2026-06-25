import { useMutation } from '@tanstack/react-query';
import { clearToken, persistToken } from '../api/client';
import {
  authApi,
  isTwoFactorRequired,
  type AuthSuccess,
  type LoginPayload,
  type LoginResult,
  type RegisterPayload,
  type TwoFactorChallengePayload,
} from '../api/endpoints/auth';
import { queryClient } from '../queries/queryClient';
import { useAuthStore } from './store';

async function applySession(result: AuthSuccess): Promise<void> {
  await persistToken(result.token);
  useAuthStore.getState().setSession(result.token, result.data);
}

export function useLogin() {
  return useMutation({
    mutationFn: (payload: LoginPayload) => authApi.login(payload),
    onSuccess: async (result: LoginResult) => {
      if (!isTwoFactorRequired(result)) await applySession(result);
    },
  });
}

export function useRegister() {
  return useMutation({
    mutationFn: (payload: RegisterPayload) => authApi.register(payload),
    onSuccess: applySession,
  });
}

export function useTwoFactorChallenge() {
  return useMutation({
    mutationFn: (payload: TwoFactorChallengePayload) => authApi.twoFactorChallenge(payload),
    onSuccess: applySession,
  });
}

export function useLogout() {
  return useMutation({
    mutationFn: () => authApi.logout(),
    // Drop the local session regardless of the network result.
    onSettled: async () => {
      await clearToken();
      useAuthStore.getState().clear();
      queryClient.clear();
    },
  });
}
