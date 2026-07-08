import { apiClient } from '../client';
import type { TreeAccessLevel } from '../../models';

export interface TreeInvitation {
  id: string;
  email: string;
  access_level: TreeAccessLevel;
  status: 'pending' | 'accepted' | 'revoked';
  invited_by: string | null;
  accepted_at: string | null;
  created_at: string | null;
}

export interface MembershipRequest {
  id: string;
  requester_name: string;
  requester_email: string;
  note: string | null;
  status: 'pending' | 'approved' | 'declined';
  created_at: string | null;
}

/** The access levels a manager may assign (owner is fixed to the tree creator). */
export type AssignableLevel = 'manager' | 'observer';

interface Listed<T> {
  data: T[];
}

export const collaborationApi = {
  invitations: (treeId: string) =>
    apiClient.get<Listed<TreeInvitation>>(`/api/v1/trees/${treeId}/invitations`).then((r) => r.data),

  invite: (treeId: string, email: string, access_level: AssignableLevel) =>
    apiClient.post<{ id: string; status: string }>(`/api/v1/trees/${treeId}/invitations`, { email, access_level }),

  revokeInvitation: (invitationId: string) =>
    apiClient.delete<{ status: string }>(`/api/v1/invitations/${invitationId}`),

  membershipRequests: (treeId: string) =>
    apiClient
      .get<Listed<MembershipRequest>>(`/api/v1/trees/${treeId}/membership-requests`)
      .then((r) => r.data),

  reviewRequest: (requestId: string, decision: 'approved' | 'declined') =>
    apiClient.patch<{ status: string }>(`/api/v1/membership-requests/${requestId}`, { decision }),

  updateMember: (treeId: string, userId: string, access_level: AssignableLevel) =>
    apiClient.patch<{ access_level: string }>(`/api/v1/trees/${treeId}/members/${userId}`, { access_level }),

  removeMember: (treeId: string, userId: string) =>
    apiClient.delete<{ removed: boolean }>(`/api/v1/trees/${treeId}/members/${userId}`),
};
