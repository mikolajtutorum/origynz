import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { collaborationApi, type AssignableLevel } from '../api/endpoints/collaboration';
import { treeKeys } from './trees';

export const collaborationKeys = {
  invitations: (treeId: string) => ['collaboration', 'invitations', treeId] as const,
  requests: (treeId: string) => ['collaboration', 'requests', treeId] as const,
};

export function useTreeInvitations(treeId: string, enabled = true) {
  return useQuery({
    queryKey: collaborationKeys.invitations(treeId),
    queryFn: () => collaborationApi.invitations(treeId),
    enabled,
  });
}

export function useMembershipRequests(treeId: string, enabled = true) {
  return useQuery({
    queryKey: collaborationKeys.requests(treeId),
    queryFn: () => collaborationApi.membershipRequests(treeId),
    enabled,
  });
}

export function useTreeCollaborationMutations(treeId: string) {
  const qc = useQueryClient();
  const invalidate = () => {
    qc.invalidateQueries({ queryKey: collaborationKeys.invitations(treeId) });
    qc.invalidateQueries({ queryKey: collaborationKeys.requests(treeId) });
    qc.invalidateQueries({ queryKey: treeKeys.members(treeId) });
  };

  return {
    invite: useMutation({
      mutationFn: ({ email, level }: { email: string; level: AssignableLevel }) =>
        collaborationApi.invite(treeId, email, level),
      onSuccess: invalidate,
    }),
    revokeInvitation: useMutation({
      mutationFn: (invitationId: string) => collaborationApi.revokeInvitation(invitationId),
      onSuccess: invalidate,
    }),
    reviewRequest: useMutation({
      mutationFn: ({ id, decision }: { id: string; decision: 'approved' | 'declined' }) =>
        collaborationApi.reviewRequest(id, decision),
      onSuccess: invalidate,
    }),
    updateMember: useMutation({
      mutationFn: ({ userId, level }: { userId: string; level: AssignableLevel }) =>
        collaborationApi.updateMember(treeId, userId, level),
      onSuccess: invalidate,
    }),
    removeMember: useMutation({
      mutationFn: (userId: string) => collaborationApi.removeMember(treeId, userId),
      onSuccess: invalidate,
    }),
  };
}
