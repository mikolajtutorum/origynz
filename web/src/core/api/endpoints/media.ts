import { apiClient } from '../client';
import type { MediaItem } from '../../models';

export interface MediaFilters {
  q?: string;
  kind?: 'all' | 'images';
  linked?: 'all' | 'linked' | 'unlinked';
  tree_id?: string;
}

interface Paginated<T> {
  data: T[];
}

type Query = Record<string, string | undefined>;

export const mediaApi = {
  list: (filters: MediaFilters = {}) =>
    apiClient.get<Paginated<MediaItem>>('/api/v1/media', filters as Query).then((r) => r.data),

  treeList: (treeId: string, filters: MediaFilters = {}) =>
    apiClient.get<Paginated<MediaItem>>(`/api/v1/trees/${treeId}/media`, filters as Query).then((r) => r.data),

  upload: (treeId: string, form: FormData) =>
    apiClient.upload<{ data: MediaItem }>(`/api/v1/trees/${treeId}/media`, form).then((r) => r.data),

  remove: (id: string) => apiClient.delete<{ message: string }>(`/api/v1/media/${id}`),
};
