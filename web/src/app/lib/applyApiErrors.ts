import { ApiError } from '@core/api/client';
import type { FieldValues, Path, UseFormSetError } from 'react-hook-form';

// Map a Laravel 422 validation error onto react-hook-form fields. Returns a
// general (non-field) message when the error isn't field-level, else null.
export function applyApiErrors<T extends FieldValues>(
  error: unknown,
  setError: UseFormSetError<T>,
): string | null {
  if (error instanceof ApiError) {
    const fields = error.validationErrors;
    if (fields && Object.keys(fields).length > 0) {
      for (const [field, messages] of Object.entries(fields)) {
        setError(field as Path<T>, { type: 'server', message: messages[0] });
      }
      return null;
    }
    return error.message;
  }
  return error instanceof Error ? error.message : 'Something went wrong.';
}
