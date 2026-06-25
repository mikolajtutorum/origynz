import { apiClient } from '../client';
import type { Relationship, RelationshipType } from '../../models';

export interface CreateRelationshipPayload {
  person_id: string;
  related_person_id: string;
  type: RelationshipType;
  subtype?: string | null;
}

export interface UpdateRelationshipPayload {
  subtype?: string | null;
  start_date_text?: string | null;
  end_date_text?: string | null;
  place?: string | null;
  description?: string | null;
}

export const relationshipsApi = {
  create: (treeId: string, payload: CreateRelationshipPayload) =>
    apiClient
      .post<{ data: Relationship }>(`/api/v1/trees/${treeId}/relationships`, payload)
      .then((r) => r.data),

  update: (treeId: string, id: string, payload: UpdateRelationshipPayload) =>
    apiClient
      .patch<{ data: Relationship }>(`/api/v1/trees/${treeId}/relationships/${id}`, payload)
      .then((r) => r.data),

  remove: (treeId: string, id: string) =>
    apiClient.delete<{ message: string }>(`/api/v1/trees/${treeId}/relationships/${id}`),
};
