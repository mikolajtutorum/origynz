import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Link, useNavigate } from 'react-router-dom';
import { registerSchema, type RegisterValues } from '@core/validation/auth';
import { useRegister } from '@core/auth/hooks';
import { AuthCard, Button, Checkbox, FormError, TextField } from '../components/ui';
import { applyApiErrors } from '../lib/applyApiErrors';

export function Register() {
  const navigate = useNavigate();
  const registerUser = useRegister();
  const [formError, setFormError] = useState<string | null>(null);

  const { register, handleSubmit, setError, formState } = useForm<RegisterValues>({
    resolver: zodResolver(registerSchema),
  });

  const onSubmit = handleSubmit(async (values) => {
    setFormError(null);
    try {
      await registerUser.mutateAsync(values);
      navigate('/dashboard', { replace: true });
    } catch (e) {
      setFormError(applyApiErrors(e, setError));
    }
  });

  return (
    <AuthCard title="Create your account" subtitle="Start your family tree in minutes — free">
      <form onSubmit={onSubmit} className="flex flex-col gap-4">
        <FormError message={formError} />
        <TextField
          label="Name"
          autoComplete="name"
          error={formState.errors.name?.message}
          {...register('name')}
        />
        <TextField
          label="Email"
          type="email"
          autoComplete="email"
          error={formState.errors.email?.message}
          {...register('email')}
        />
        <TextField
          label="Password"
          type="password"
          autoComplete="new-password"
          error={formState.errors.password?.message}
          {...register('password')}
        />
        <TextField
          label="Confirm password"
          type="password"
          autoComplete="new-password"
          error={formState.errors.password_confirmation?.message}
          {...register('password_confirmation')}
        />
        <Checkbox
          label={
            <>
              I agree to the{' '}
              <Link to="/terms-of-service" className="font-medium text-accent underline decoration-emerald-400/40 underline-offset-2">
                Terms
              </Link>{' '}
              and{' '}
              <Link to="/privacy-policy" className="font-medium text-accent underline decoration-emerald-400/40 underline-offset-2">
                Privacy Policy
              </Link>
              .
            </>
          }
          error={formState.errors.terms?.message}
          {...register('terms')}
        />
        <Checkbox
          label="I confirm I am 13 years of age or older."
          error={formState.errors.age_confirmation?.message}
          {...register('age_confirmation')}
        />
        <Button type="submit" loading={registerUser.isPending}>
          Create account
        </Button>
      </form>
      <p className="mt-5 text-center text-sm text-ink-muted">
        Already have an account?{' '}
        <Link to="/login" className="font-medium text-accent underline decoration-emerald-400/40 underline-offset-2 hover:text-accent-strong">
          Sign in
        </Link>
      </p>
    </AuthCard>
  );
}
