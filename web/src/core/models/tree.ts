export type TreePrivacy = 'private' | 'invited' | 'public';
export type TreeAccessLevel = 'owner' | 'manager' | 'observer';

export interface Tree {
  id: string;
  name: string;
  description: string | null;
  home_region: string | null;
  privacy: TreePrivacy;
  global_tree_enabled?: boolean;
  owner_person_id: string | null;
  people_count?: number;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface TreeMember {
  id: string;
  name: string;
  email: string;
  access_level: TreeAccessLevel;
}
