import { useMutation, useQueryClient } from '@tanstack/react-query';
import {
  peopleApi,
  type AddRelativePayload,
  type PersonFields,
  type UpdatePersonFields,
} from '../api/endpoints/people';
import {
  relationshipsApi,
  type CreateRelationshipPayload,
} from '../api/endpoints/relationships';
import { treeKeys } from './trees';

// All mutations invalidate the tree graph so the workspace re-renders.
function useGraphInvalidation(treeId: string) {
  const qc = useQueryClient();
  return () => qc.invalidateQueries({ queryKey: treeKeys.graph(treeId) });
}

export function useCreatePerson(treeId: string) {
  const invalidate = useGraphInvalidation(treeId);
  return useMutation({
    mutationFn: (payload: PersonFields) => peopleApi.create(treeId, payload),
    onSuccess: invalidate,
  });
}

export function useUpdatePerson(treeId: string) {
  const invalidate = useGraphInvalidation(treeId);
  return useMutation({
    mutationFn: ({ id, payload }: { id: string; payload: UpdatePersonFields }) =>
      peopleApi.update(id, payload),
    onSuccess: invalidate,
  });
}

export function useAddRelative(treeId: string) {
  const invalidate = useGraphInvalidation(treeId);
  return useMutation({
    mutationFn: (payload: AddRelativePayload) => peopleApi.addRelative(treeId, payload),
    onSuccess: invalidate,
  });
}

export function useRemovePerson(treeId: string) {
  const invalidate = useGraphInvalidation(treeId);
  return useMutation({
    mutationFn: (id: string) => peopleApi.remove(id),
    onSuccess: invalidate,
  });
}

export function useCreateRelationship(treeId: string) {
  const invalidate = useGraphInvalidation(treeId);
  return useMutation({
    mutationFn: (payload: CreateRelationshipPayload) => relationshipsApi.create(treeId, payload),
    onSuccess: invalidate,
  });
}

export function useRemoveRelationship(treeId: string) {
  const invalidate = useGraphInvalidation(treeId);
  return useMutation({
    mutationFn: (id: string) => relationshipsApi.remove(treeId, id),
    onSuccess: invalidate,
  });
}
