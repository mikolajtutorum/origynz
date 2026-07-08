import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { loginSchema, type LoginValues } from '@core/validation/auth';
import { useLogin, useTwoFactorChallenge } from '@core/auth/hooks';
import { isTwoFactorRequired } from '@core/api/endpoints/auth';
import { useT } from '@core/i18n';
import { AuthCard, Button, FormError, TextField } from '../components/ui';
import { applyApiErrors } from '../lib/applyApiErrors';

interface FromState {
  from?: { pathname?: string };
}

export function Login() {
  const t = useT();
  const navigate = useNavigate();
  const location = useLocation();
  const redirectTo = (location.state as FromState)?.from?.pathname ?? '/dashboard';

  const login = useLogin();
  const challenge = useTwoFactorChallenge();

  const [formError, setFormError] = useState<string | null>(null);
  const [needsTwoFactor, setNeedsTwoFactor] = useState(false);
  const [credentials, setCredentials] = useState<LoginValues | null>(null);
  const [code, setCode] = useState('');

  const { register, handleSubmit, setError, formState } = useForm<LoginValues>({
    resolver: zodResolver(loginSchema),
  });

  const onSubmit = handleSubmit(async (values) => {
    setFormError(null);
    try {
      const result = await login.mutateAsync(values);
      if (isTwoFactorRequired(result)) {
        setCredentials(values);
        setNeedsTwoFactor(true);
        return;
      }
      navigate(redirectTo, { replace: true });
    } catch (e) {
      setFormError(applyApiErrors(e, setError));
    }
  });

  const submitTwoFactor = async () => {
    if (!credentials) return;
    setFormError(null);
    try {
      await challenge.mutateAsync({ ...credentials, code });
      navigate(redirectTo, { replace: true });
    } catch (e) {
      setFormError(e instanceof Error ? e.message : t('Invalid code.'));
    }
  };

  if (needsTwoFactor) {
    return (
      <AuthCard title={t('Two-factor authentication')} subtitle={t('Enter the code from your authenticator app')}>
        <div className="flex flex-col gap-4">
          <FormError message={formError} />
          <TextField
            label={t('Authentication code')}
            inputMode="numeric"
            autoComplete="one-time-code"
            value={code}
            onChange={(e) => setCode(e.target.value)}
          />
          <Button onClick={submitTwoFactor} loading={challenge.isPending}>
            {t('Verify')}
          </Button>
        </div>
      </AuthCard>
    );
  }

  return (
    <AuthCard title={t('Welcome back')} subtitle={t('Log in to continue your family research')}>
      <form onSubmit={onSubmit} className="flex flex-col gap-4">
        <FormError message={formError} />
        <TextField
          label={t('Email')}
          type="email"
          autoComplete="email"
          error={formState.errors.email?.message}
          {...register('email')}
        />
        <TextField
          label={t('Password')}
          type="password"
          autoComplete="current-password"
          error={formState.errors.password?.message}
          {...register('password')}
        />
        <Button type="submit" loading={login.isPending}>
          {t('Sign in')}
        </Button>
      </form>
      <p className="mt-5 text-center text-sm text-ink-muted">
        {t('No account?')}{' '}
        <Link to="/register" className="font-medium text-accent underline decoration-emerald-400/40 underline-offset-2 hover:text-accent-strong">
          {t('Create one')}
        </Link>
      </p>
    </AuthCard>
  );
}
