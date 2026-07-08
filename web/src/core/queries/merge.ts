import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { mergeApi, type MergePayload } from '../api/endpoints/merge';

export const mergeKeys = {
  tree: (treeId: string) => ['merge', 'tree', treeId] as const,
  suggestions: ['merge', 'suggestions'] as const,
  preview: (id: string) => ['merge', 'preview', id] as const,
};

export function useTreeMergeCandidates(treeId: string | undefined) {
  return useQuery({
    queryKey: mergeKeys.tree(treeId ?? ''),
    queryFn: () => mergeApi.treeCandidates(treeId as string),
    enabled: Boolean(treeId),
  });
}

export function useMergeSuggestions() {
  return useQuery({ queryKey: mergeKeys.suggestions, queryFn: mergeApi.suggestions });
}

export function useMergePreview(id: string | null) {
  return useQuery({
    queryKey: mergeKeys.preview(id ?? ''),
    queryFn: () => mergeApi.preview(id as string),
    enabled: Boolean(id),
  });
}

export function useScanTree(treeId: string | undefined) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () => mergeApi.scanTree(treeId as string),
    onSuccess: () => qc.invalidateQueries({ queryKey: mergeKeys.tree(treeId ?? '') }),
  });
}

/** Invalidate every merge-related list after a resolution (merge or dismiss). */
function useInvalidateMergeLists() {
  const qc = useQueryClient();
  return () => {
    qc.invalidateQueries({ queryKey: ['merge'] });
    // A merge rewrites people/relationships, so tree graphs are now stale too.
    qc.invalidateQueries({ queryKey: ['trees'] });
  };
}

export function useMergeCandidate() {
  const invalidate = useInvalidateMergeLists();
  return useMutation({
    mutationFn: ({ id, payload }: { id: string; payload: MergePayload }) => mergeApi.merge(id, payload),
    onSuccess: invalidate,
  });
}

export function useDismissCandidate() {
  const invalidate = useInvalidateMergeLists();
  return useMutation({
    mutationFn: (id: string) => mergeApi.dismiss(id),
    onSuccess: invalidate,
  });
}
