import { apiClient } from '../client';

export interface RelationshipStep {
  id: string;
  name: string;
  via: string;
}

export interface RelationshipPath {
  connected: boolean;
  path: RelationshipStep[];
}

export const globalTreeApi = {
  relationshipPath: (personAId: string, personBId: string) =>
    apiClient.post<RelationshipPath>('/api/v1/global-tree/relationship', {
      person_a_id: personAId,
      person_b_id: personBId,
    }),
};
