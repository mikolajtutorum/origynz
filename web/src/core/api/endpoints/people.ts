import { apiClient } from '../client';
import type { Person, Relationship, Sex } from '../../models';

export interface PersonFields {
  given_name: string;
  middle_name?: string | null;
  surname: string;
  birth_surname?: string | null;
  sex: Sex;
  birth_date?: string | null;
  birth_date_text?: string | null;
  death_date?: string | null;
  death_date_text?: string | null;
  birth_place?: string | null;
  death_place?: string | null;
  is_living?: boolean;
  headline?: string | null;
  notes?: string | null;
}

export interface UpdatePersonFields extends PersonFields {
  prefix?: string | null;
  suffix?: string | null;
  nickname?: string | null;
  cause_of_death?: string | null;
  burial_place?: string | null;
  physical_description?: string | null;
}

export type RelationRole =
  | 'father' | 'mother' | 'parent'
  | 'son' | 'daughter' | 'child'
  | 'brother' | 'sister' | 'half-brother' | 'half-sister'
  | 'partner' | 'spouse';

export interface AddRelativePayload extends PersonFields {
  anchor_person_id: string;
  relation_role: RelationRole;
  relationship_subtype?: string | null;
}

export interface TimelineEvent {
  id: string;
  label: string;
  date: string | null;
  value: string | null;
  place: string | null;
  note: string | null;
  description: string | null;
}

export interface RelationshipFact {
  id: string;
  with: string | null;
  subtype: string | null;
  start: string | null;
  end: string | null;
  place: string | null;
  description: string | null;
}

export interface PersonProfile {
  data: Person;
  events: TimelineEvent[];
  relationship_facts: RelationshipFact[];
  imported_record: Record<string, string>;
}

export const peopleApi = {
  get: (id: string) => apiClient.get<{ data: Person }>(`/api/v1/people/${id}`).then((r) => r.data),

  profile: (id: string) => apiClient.get<PersonProfile>(`/api/v1/people/${id}/profile`),

  relationships: (id: string) =>
    apiClient.get<{ data: Relationship[] }>(`/api/v1/people/${id}/relationships`).then((r) => r.data),

  create: (treeId: string, payload: PersonFields) =>
    apiClient.post<{ data: Person }>(`/api/v1/trees/${treeId}/people`, payload).then((r) => r.data),

  update: (id: string, payload: UpdatePersonFields) =>
    apiClient.patch<{ data: Person }>(`/api/v1/people/${id}`, payload).then((r) => r.data),

  addRelative: (treeId: string, payload: AddRelativePayload) =>
    apiClient
      .post<{ data: Person; relationship_label: string }>(`/api/v1/trees/${treeId}/people/relative`, payload)
      .then((r) => r),

  remove: (id: string) => apiClient.delete<{ message: string }>(`/api/v1/people/${id}`),

  search: (q: string, treeId?: string) =>
    apiClient
      .get<{ data: Person[] }>('/api/v1/people/search', { q, tree_id: treeId })
      .then((r) => r.data),
};
