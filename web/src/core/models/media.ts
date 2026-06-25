export interface MediaItem {
  id: string;
  title: string;
  description: string | null;
  file_name: string;
  mime_type: string | null;
  file_size: number | null;
  is_image: boolean;
  is_primary: boolean;
  person_id: string | null;
  family_tree_id: string;
  tree_name?: string | null;
  preview_url: string | null;
  download_url: string;
  created_at?: string | null;
}
