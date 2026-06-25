export type RelationshipType = 'parent' | 'spouse' | 'child';

export interface Relationship {
  id: string;
  type: RelationshipType;
  subtype: string | null;
  person_id: string;
  related_person_id: string;
  start_date_text: string | null;
  end_date_text: string | null;
  place: string | null;
  description: string | null;
}
