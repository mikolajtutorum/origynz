import { apiClient } from '../client';

export interface ImportStarted {
  import_id: string;
  tree_id: string;
}

export type ImportStatus = 'queued' | 'processing' | 'done' | 'failed' | 'not_found';

export type ImportStage =
  | 'queued'
  | 'reading'
  | 'parsing'
  | 'sources'
  | 'people'
  | 'links'
  | 'relationships'
  | 'finalizing'
  | 'done'
  | 'failed'
  | 'processing';

export interface ImportProgress {
  status: ImportStatus;
  stage: ImportStage;
  progress: number;
  message: string;
  current: number | null;
  total: number | null;
  tree_id: string | null;
  first_person_id: string | null;
  people_created?: number | null;
  relationships_created?: number | null;
  // Set when the importer couldn't confidently identify the home person; the
  // workspace then prompts the user to pick themselves.
  owner_selection_required?: boolean;
}

export const gedcomApi = {
  // Import into a new (or named/existing) tree.
  importNew: (form: FormData) => apiClient.upload<ImportStarted>('/api/v1/gedcom/import', form),

  // Import into a specific existing tree.
  importInto: (treeId: string, form: FormData) =>
    apiClient.upload<ImportStarted>(`/api/v1/trees/${treeId}/gedcom/import`, form),

  progress: (importId: string) =>
    apiClient.get<ImportProgress>(`/api/v1/gedcom/import/${importId}`),

  // Direct download URL (bearer is added by the browser fetch in the page).
  exportPath: (treeId: string) => `/api/v1/trees/${treeId}/gedcom/export`,
};
