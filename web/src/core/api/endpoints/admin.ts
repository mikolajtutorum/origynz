import { apiClient } from '../client';

export interface AdminStats {
  users: number;
  trees: number;
  people: number;
  recent_users: { id: string; name: string; email: string; created_at: string }[];
  recent_trees: { id: string; name: string; created_at: string }[];
}

export interface AdminUser {
  id: string;
  name: string;
  email: string;
  family_trees_count: number;
  people_count: number;
  roles: string[];
  created_at: string | null;
}

export interface AdminTree {
  id: string;
  name: string;
  privacy: string;
  owner: string | null;
  people_count: number;
  global_tree_enabled: boolean;
  created_at: string | null;
}

export interface ActivityLog {
  id: string;
  event: string | null;
  description: string | null;
  causer: string | null;
  created_at: string | null;
}

interface Listed<T> {
  data: T[];
}

export const SITE_ROLES = ['member', 'curator', 'admin', 'super admin'] as const;

export const adminApi = {
  dashboard: () => apiClient.get<AdminStats>('/api/v1/admin/dashboard'),
  users: (search?: string) =>
    apiClient.get<Listed<AdminUser>>('/api/v1/admin/users', { search }).then((r) => r.data),
  updateRole: (id: string, role: string) => apiClient.patch(`/api/v1/admin/users/${id}/role`, { role }),
  deleteUser: (id: string) => apiClient.delete(`/api/v1/admin/users/${id}`),
  trees: () => apiClient.get<Listed<AdminTree>>('/api/v1/admin/trees').then((r) => r.data),
  deleteTree: (id: string) => apiClient.delete(`/api/v1/admin/trees/${id}`),
  toggleGlobal: (id: string) =>
    apiClient.patch<{ global_tree_enabled: boolean }>(`/api/v1/admin/trees/${id}/global`),
  activity: () => apiClient.get<Listed<ActivityLog>>('/api/v1/admin/activity').then((r) => r.data),
};
