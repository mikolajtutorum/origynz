import { z } from 'zod';

export const loginSchema = z.object({
  email: z.string().email('Enter a valid email address.'),
  password: z.string().min(1, 'Password is required.'),
});

export type LoginValues = z.infer<typeof loginSchema>;

export const registerSchema = z
  .object({
    name: z.string().min(1, 'Your name is required.').max(255),
    email: z.string().email('Enter a valid email address.'),
    password: z.string().min(8, 'Password must be at least 8 characters.'),
    password_confirmation: z.string(),
    terms: z.literal(true, {
      errorMap: () => ({ message: 'You must agree to the Terms and Privacy Policy.' }),
    }),
    age_confirmation: z.literal(true, {
      errorMap: () => ({ message: 'You must confirm you are 13 or older.' }),
    }),
  })
  .refine((v) => v.password === v.password_confirmation, {
    message: 'Passwords do not match.',
    path: ['password_confirmation'],
  });

export type RegisterValues = z.infer<typeof registerSchema>;
