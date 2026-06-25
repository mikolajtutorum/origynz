import { apiClient } from '../client';

export interface Discussion {
  id: string;
  body: string;
  parent_id: string | null;
  author: string | null;
  created_at: string | null;
  can_delete: boolean;
}

export const interactionsApi = {
  toggleWatch: (personId: string) =>
    apiClient.post<{ watching: boolean }>(`/api/v1/people/${personId}/watch`),

  discussions: (personId: string) =>
    apiClient.get<{ data: Discussion[] }>(`/api/v1/people/${personId}/discussions`).then((r) => r.data),

  postDiscussion: (personId: string, body: string) =>
    apiClient.post<{ id: string }>(`/api/v1/people/${personId}/discussions`, { body }),

  deleteDiscussion: (id: string) =>
    apiClient.delete<{ message: string }>(`/api/v1/discussions/${id}`),

  requestPhoto: (personId: string, notes?: string) =>
    apiClient.post<{ id: string }>(`/api/v1/people/${personId}/photo-requests`, { notes }),
};
