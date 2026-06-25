import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { mediaApi, type MediaFilters } from '../api/endpoints/media';
import { treeKeys } from './trees';

export const mediaKeys = {
  global: (filters: MediaFilters) => ['media', 'global', filters] as const,
  tree: (treeId: string) => ['media', 'tree', treeId] as const,
};

export function useGlobalMedia(filters: MediaFilters = {}) {
  return useQuery({ queryKey: mediaKeys.global(filters), queryFn: () => mediaApi.list(filters) });
}

export function useTreeMedia(treeId: string) {
  return useQuery({ queryKey: mediaKeys.tree(treeId), queryFn: () => mediaApi.treeList(treeId) });
}

export function useUploadMedia(treeId: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (form: FormData) => mediaApi.upload(treeId, form),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['media'] });
      qc.invalidateQueries({ queryKey: treeKeys.graph(treeId) });
    },
  });
}

export function useRemoveMedia() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => mediaApi.remove(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['media'] }),
  });
}
