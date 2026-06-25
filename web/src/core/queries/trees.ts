import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { treesApi, type CreateTreePayload, type UpdateTreePayload } from '../api/endpoints/trees';

export const treeKeys = {
  all: ['trees'] as const,
  detail: (id: string) => ['trees', id] as const,
  graph: (id: string) => ['trees', id, 'graph'] as const,
  members: (id: string) => ['trees', id, 'members'] as const,
};

export function useTrees() {
  return useQuery({ queryKey: treeKeys.all, queryFn: treesApi.list });
}

export function useTreeGraph(id: string) {
  return useQuery({ queryKey: treeKeys.graph(id), queryFn: () => treesApi.graph(id) });
}

export function useTreeMembers(id: string) {
  return useQuery({ queryKey: treeKeys.members(id), queryFn: () => treesApi.members(id) });
}

export function useCreateTree() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: CreateTreePayload) => treesApi.create(payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: treeKeys.all }),
  });
}

export function useUpdateTree(id: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: UpdateTreePayload) => treesApi.update(id, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: treeKeys.all });
      qc.invalidateQueries({ queryKey: treeKeys.graph(id) });
    },
  });
}
