import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { personSchema, type PersonFormValues } from '@core/validation/person';
import { Button, Checkbox, FormError, Select, TextField, Textarea } from '../ui';

export function PersonForm({
  defaultValues,
  onSubmit,
  submitting,
  submitLabel,
  error,
}: {
  defaultValues?: Partial<PersonFormValues>;
  onSubmit: (values: PersonFormValues) => void | Promise<void>;
  submitting?: boolean;
  submitLabel: string;
  error?: string | null;
}) {
  const { register, handleSubmit, formState } = useForm<PersonFormValues>({
    resolver: zodResolver(personSchema),
    defaultValues: { sex: 'unknown', is_living: true, ...defaultValues },
  });

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-3">
      <FormError message={error} />
      <div className="grid grid-cols-2 gap-3">
        <TextField label="Given name" error={formState.errors.given_name?.message} {...register('given_name')} />
        <TextField label="Surname" error={formState.errors.surname?.message} {...register('surname')} />
        <TextField label="Middle name" error={formState.errors.middle_name?.message} {...register('middle_name')} />
        <Select label="Sex" error={formState.errors.sex?.message} {...register('sex')}>
          <option value="unknown">Unknown</option>
          <option value="female">Female</option>
          <option value="male">Male</option>
        </Select>
        <TextField label="Birth date" placeholder="e.g. 12 Mar 1901" error={formState.errors.birth_date_text?.message} {...register('birth_date_text')} />
        <TextField label="Birth place" error={formState.errors.birth_place?.message} {...register('birth_place')} />
        <TextField label="Death date" placeholder="e.g. 1978" error={formState.errors.death_date_text?.message} {...register('death_date_text')} />
        <TextField label="Death place" error={formState.errors.death_place?.message} {...register('death_place')} />
      </div>
      <TextField label="Headline" error={formState.errors.headline?.message} {...register('headline')} />
      <Textarea label="Notes" error={formState.errors.notes?.message} {...register('notes')} />
      <Checkbox label="Living" {...register('is_living')} />
      <Button type="submit" loading={submitting}>
        {submitLabel}
      </Button>
    </form>
  );
}
