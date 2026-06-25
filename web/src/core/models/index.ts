export type { User } from './user';
export type { Tree, TreeMember, TreePrivacy, TreeAccessLevel } from './tree';
export type { Person, Sex } from './person';
export type { Relationship, RelationshipType } from './relationship';
export type { MediaItem } from './media';

import type { Tree, TreeAccessLevel } from './tree';
import type { Person } from './person';
import type { Relationship } from './relationship';

export interface TreeGraph {
  tree: Tree;
  people: Person[];
  relationships: Relationship[];
  owner_person_id: string | null;
  access_level: TreeAccessLevel;
  can_manage: boolean;
}
