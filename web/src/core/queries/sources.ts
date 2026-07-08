import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  sourcesApi,
  type CreateCitationPayload,
  type CreateSourcePayload,
} from '../api/endpoints/sources';

export const sourceKeys = {
  tree: (treeId: string) => ['sources', 'tree', treeId] as const,
  citations: (personId: string) => ['sources', 'citations', personId] as const,
};

export function useTreeSources(treeId: string) {
  return useQuery({ queryKey: sourceKeys.tree(treeId), queryFn: () => sourcesApi.listForTree(treeId) });
}

export function usePersonCitations(personId: string) {
  return useQuery({
    queryKey: sourceKeys.citations(personId),
    queryFn: () => sourcesApi.listCitations(personId),
  });
}

export function useCreateSource(treeId: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: CreateSourcePayload) => sourcesApi.createSource(treeId, payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: sourceKeys.tree(treeId) }),
  });
}

export function useCreateCitation(personId: string, treeId: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: CreateCitationPayload) => sourcesApi.createCitation(personId, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: sourceKeys.citations(personId) });
      qc.invalidateQueries({ queryKey: sourceKeys.tree(treeId) });
    },
  });
}

export function useDeleteCitation(personId: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => sourcesApi.deleteCitation(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: sourceKeys.citations(personId) }),
  });
}
