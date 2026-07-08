import { apiClient } from '../client';

export interface DnaKit {
  id: string;
  provider: string;
  provider_label: string;
  kit_name: string | null;
  snp_count: number;
  haplogroup_y: string | null;
  haplogroup_mt: string | null;
  ancestry_composition: Record<string, unknown> | null;
  sample_date: string | null;
  notes: string | null;
  person_id: string | null;
  person_name: string | null;
  created_at: string | null;
}

interface Listed<T> {
  data: T[];
}

export const dnaApi = {
  list: () => apiClient.get<Listed<DnaKit>>('/api/v1/dna-kits').then((r) => r.data),

  upload: (file: File, personId?: string) => {
    const form = new FormData();
    form.append('file', file);
    if (personId) form.append('person_id', personId);
    return apiClient.upload<DnaKit>('/api/v1/dna-kits', form);
  },

  update: (id: string, payload: { kit_name?: string | null; notes?: string | null; person_id?: string | null }) =>
    apiClient.patch<DnaKit>(`/api/v1/dna-kits/${id}`, payload),

  remove: (id: string) => apiClient.delete<{ deleted: boolean }>(`/api/v1/dna-kits/${id}`),
};
