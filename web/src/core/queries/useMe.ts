import { useQuery } from '@tanstack/react-query';
import { authApi } from '../api/endpoints/auth';

export function useMe(enabled = true) {
  return useQuery({
    queryKey: ['me'],
    queryFn: () => authApi.me(),
    enabled,
  });
}
