import { apiClient } from '../client';

export interface MergePersonHeader {
  id: string;
  display_name: string;
  life_span: string | null;
  birth_place: string | null;
  sex: string;
  tree_id: string;
  tree_name: string | null;
  counts: { relationships: number; media: number; events: number; sources: number };
}

export interface MergeCandidate {
  id: string;
  similarity_score: number;
  person_a: MergePersonHeader;
  person_b: MergePersonHeader;
}

export interface MergeField {
  field: string;
  label: string;
  value_a: string | null;
  value_b: string | null;
  conflict: boolean;
  suggested: 'a' | 'b';
}

export interface MergePreview {
  id: string;
  similarity_score: number;
  person_a: MergePersonHeader;
  person_b: MergePersonHeader;
  fields: MergeField[];
}

export interface MergePayload {
  surviving: 'a' | 'b';
  decisions: Record<string, 'a' | 'b'>;
}

interface Listed<T> {
  data: T[];
}

export const mergeApi = {
  treeCandidates: (treeId: string) =>
    apiClient
      .get<Listed<MergeCandidate>>(`/api/v1/trees/${treeId}/merge-candidates`)
      .then((r) => r.data),

  scanTree: (treeId: string) =>
    apiClient.post<{ created: number }>(`/api/v1/trees/${treeId}/merge-candidates/scan`),

  suggestions: () =>
    apiClient.get<Listed<MergeCandidate>>('/api/v1/merge-candidates/suggestions').then((r) => r.data),

  preview: (id: string) => apiClient.get<MergePreview>(`/api/v1/merge-candidates/${id}/preview`),

  merge: (id: string, payload: MergePayload) =>
    apiClient.post<{ surviving_person_id: string; absorbed_person_id: string }>(
      `/api/v1/merge-candidates/${id}/merge`,
      payload,
    ),

  dismiss: (id: string) => apiClient.post<{ status: string }>(`/api/v1/merge-candidates/${id}/dismiss`),
};
