import { apiClient } from '../client';
import type { Tree, TreeGraph, TreeMember } from '../../models';

interface Paginated<T> {
  data: T[];
  meta?: unknown;
  links?: unknown;
}

export interface CreateTreePayload {
  name: string;
  description?: string | null;
  home_region?: string | null;
  privacy: Tree['privacy'];
}

export type UpdateTreePayload = Partial<CreateTreePayload> & {
  // The home person the workspace centres on.
  owner_person_id?: string;
};

export const treesApi = {
  list: () => apiClient.get<Paginated<Tree>>('/api/v1/trees').then((r) => r.data),

  get: (id: string) => apiClient.get<{ data: Tree }>(`/api/v1/trees/${id}`).then((r) => r.data),

  create: (payload: CreateTreePayload) =>
    apiClient.post<{ data: Tree }>('/api/v1/trees', payload).then((r) => r.data),

  update: (id: string, payload: UpdateTreePayload) =>
    apiClient.patch<{ data: Tree }>(`/api/v1/trees/${id}`, payload).then((r) => r.data),

  graph: (id: string) => apiClient.get<TreeGraph>(`/api/v1/trees/${id}/graph`),

  members: (id: string) =>
    apiClient.get<{ data: TreeMember[] }>(`/api/v1/trees/${id}/members`).then((r) => r.data),
};
