export type Sex = 'female' | 'male' | 'unknown';

export interface Person {
  id: string;
  given_name: string;
  middle_name: string | null;
  surname: string | null;
  birth_surname: string | null;
  nickname: string | null;
  prefix: string | null;
  suffix: string | null;
  sex: Sex;
  display_name: string;
  life_span: string | null;
  is_living: boolean;
  birth_date: string | null;
  birth_date_text: string | null;
  birth_place: string | null;
  death_date: string | null;
  death_date_text: string | null;
  death_place: string | null;
  cause_of_death: string | null;
  burial_place: string | null;
  headline?: string | null;
  notes?: string | null;
  trust_score: number | null;
  family_tree_id: string;
  avatar_url: string | null;
  created_at?: string | null;
  updated_at?: string | null;
}
