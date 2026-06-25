import { z } from 'zod';

export const treeSchema = z.object({
  name: z.string().min(1, 'Name is required.').max(120),
  description: z.string().max(2000).optional().or(z.literal('')),
  home_region: z.string().max(120).optional().or(z.literal('')),
  privacy: z.enum(['private', 'invited', 'public']),
});

export type TreeFormValues = z.infer<typeof treeSchema>;
