import { apiClient } from '../client';

export interface Source {
  id: string;
  title: string;
  author: string | null;
  publication_facts: string | null;
  repository: string | null;
  call_number: string | null;
  url: string | null;
  text: string | null;
  quality: number | null;
  source_type: string | null;
  source_medium: string | null;
  citations_count: number;
}

export interface Citation {
  id: string;
  source_id: string;
  source_title: string | null;
  source_author: string | null;
  source_url: string | null;
  page: string | null;
  quotation: string | null;
  note: string | null;
  quality: number | null;
  event_name: string | null;
}

export interface CreateSourcePayload {
  title: string;
  author?: string | null;
  repository?: string | null;
  url?: string | null;
  publication_facts?: string | null;
  call_number?: string | null;
  text?: string | null;
}

export interface CreateCitationPayload {
  source_id: string;
  page?: string | null;
  quotation?: string | null;
  note?: string | null;
  quality?: number | null;
  event_name?: string | null;
}

interface Listed<T> {
  data: T[];
}

export const sourcesApi = {
  listForTree: (treeId: string) =>
    apiClient.get<Listed<Source>>(`/api/v1/trees/${treeId}/sources`).then((r) => r.data),

  createSource: (treeId: string, payload: CreateSourcePayload) =>
    apiClient.post<Source>(`/api/v1/trees/${treeId}/sources`, payload),

  updateSource: (id: string, payload: Partial<CreateSourcePayload>) =>
    apiClient.patch<Source>(`/api/v1/sources/${id}`, payload),

  deleteSource: (id: string) => apiClient.delete<{ deleted: boolean }>(`/api/v1/sources/${id}`),

  listCitations: (personId: string) =>
    apiClient.get<Listed<Citation>>(`/api/v1/people/${personId}/citations`).then((r) => r.data),

  createCitation: (personId: string, payload: CreateCitationPayload) =>
    apiClient.post<Citation>(`/api/v1/people/${personId}/citations`, payload),

  deleteCitation: (id: string) => apiClient.delete<{ deleted: boolean }>(`/api/v1/citations/${id}`),
};
