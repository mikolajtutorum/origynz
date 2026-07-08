import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { settingsApi, type ProfilePayload } from '@core/api/endpoints/settings';
import { useAuthStore } from '@core/auth/store';
import { useT } from '@core/i18n';
import { Button, FormError, TextField } from '../ui';
import { applyApiErrors } from '../../lib/applyApiErrors';

export function ProfileSection() {
  const t = useT();
  const user = useAuthStore((s) => s.user);
  const [error, setError] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);

  const { register, handleSubmit, setError: setFieldError, formState } = useForm<ProfilePayload>({
    defaultValues: {
      name: user?.name ?? '',
      email: user?.email ?? '',
      first_name: user?.first_name ?? '',
      last_name: user?.last_name ?? '',
    },
  });

  const onSubmit = handleSubmit(async (values) => {
    setError(null);
    setSaved(false);
    try {
      const updated = await settingsApi.updateProfile(values);
      useAuthStore.getState().setUser(updated);
      setSaved(true);
    } catch (e) {
      setError(applyApiErrors(e, setFieldError));
    }
  });

  return (
    <form onSubmit={onSubmit} className="flex max-w-lg flex-col gap-4">
      <FormError message={error} />
      {saved && <p className="o-alert-success">{t('Profile saved.')}</p>}
      <TextField label={t('Display name')} error={formState.errors.name?.message} {...register('name')} />
      <div className="grid grid-cols-2 gap-3">
        <TextField label={t('First name')} {...register('first_name')} />
        <TextField label={t('Last name')} {...register('last_name')} />
      </div>
      <TextField label={t('Email')} type="email" error={formState.errors.email?.message} {...register('email')} />
      <TextField label={t('Country of residence')} {...register('country_of_residence')} />
      <div>
        <Button type="submit" loading={formState.isSubmitting}>
          {t('Save profile')}
        </Button>
      </div>
    </form>
  );
}
