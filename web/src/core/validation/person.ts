import { z } from 'zod';

const optionalText = (max: number) => z.string().max(max).optional().or(z.literal(''));

export const personSchema = z.object({
  given_name: z.string().min(1, 'Given name is required.').max(80),
  middle_name: optionalText(80),
  surname: z.string().min(1, 'Surname is required.').max(80),
  sex: z.enum(['female', 'male', 'unknown']),
  birth_date_text: optionalText(120),
  birth_place: optionalText(120),
  death_date_text: optionalText(120),
  death_place: optionalText(120),
  is_living: z.boolean().optional(),
  headline: optionalText(120),
  notes: optionalText(4000),
});

export type PersonFormValues = z.infer<typeof personSchema>;
