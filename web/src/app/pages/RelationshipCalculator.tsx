import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { globalTreeApi, type RelationshipPath } from '@core/api/endpoints/globalTree';
import { ApiError } from '@core/api/client';
import type { Person } from '@core/models';
import { AppLayout } from '../components/AppLayout';
import { PersonSearchInput } from '../components/PersonSearchInput';
import { Button, FormError } from '../components/ui';

export function RelationshipCalculator() {
  const [a, setA] = useState<Person | null>(null);
  const [b, setB] = useState<Person | null>(null);
  const [error, setError] = useState<string | null>(null);

  const calc = useMutation<RelationshipPath>({
    mutationFn: () => globalTreeApi.relationshipPath(a!.id, b!.id),
  });

  const run = async () => {
    if (!a || !b) return;
    setError(null);
    try {
      await calc.mutateAsync();
    } catch (e) {
      setError(
        e instanceof ApiError && e.status === 403
          ? 'Both people must belong to a Global Tree–enabled tree.'
          : (e as Error).message,
      );
    }
  };

  const result = calc.data;

  return (
    <AppLayout>
      <h1 className="text-2xl font-semibold text-neutral-900">Relationship calculator</h1>
      <p className="mt-1 text-neutral-500">Find how two people in the Global Tree are connected.</p>

      <div className="mt-6 max-w-xl rounded-xl border border-neutral-200 bg-white p-5">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <PersonSearchInput label="Person A" selected={a} onSelect={setA} />
          <PersonSearchInput label="Person B" selected={b} onSelect={setB} />
        </div>
        <div className="mt-4">
          <Button onClick={run} disabled={!a || !b} loading={calc.isPending}>
            Calculate
          </Button>
        </div>
        <div className="mt-3">
          <FormError message={error} />
        </div>
      </div>

      {result && !error && (
        <div className="mt-6 max-w-xl rounded-xl border border-neutral-200 bg-white p-5">
          {result.connected ? (
            <ol className="flex flex-col gap-2">
              {result.path.map((step, i) => (
                <li key={step.id} className="flex items-center gap-3 text-sm">
                  <span className="flex h-6 w-6 items-center justify-center rounded-full bg-neutral-900 text-xs text-white">
                    {i + 1}
                  </span>
                  <span className="font-medium text-neutral-900">{step.name}</span>
                  {step.via && <span className="text-neutral-500">({step.via})</span>}
                </li>
              ))}
            </ol>
          ) : (
            <p className="text-neutral-600">No connection found between these two people in the Global Tree.</p>
          )}
        </div>
      )}
    </AppLayout>
  );
}
