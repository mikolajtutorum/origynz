import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { globalTreeApi, type RelationshipPath } from '@core/api/endpoints/globalTree';
import { ApiError } from '@core/api/client';
import type { Person } from '@core/models';
import { useT } from '@core/i18n';
import { AppLayout } from '../components/AppLayout';
import { PersonSearchInput } from '../components/PersonSearchInput';
import { Button, FormError } from '../components/ui';

export function RelationshipCalculator() {
  const t = useT();
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
          ? t('Both people must belong to a Global Tree–enabled tree.')
          : (e as Error).message,
      );
    }
  };

  const result = calc.data;

  return (
    <AppLayout>
      <div className="space-y-8">
        <header className="max-w-2xl space-y-2">
          <p className="o-eyebrow">{t('Global Tree')}</p>
          <h1 className="o-display text-3xl sm:text-4xl">{t('Relationship calculator')}</h1>
          <p className="text-[15px] leading-7 text-ink-muted">
            {t('Pick any two people and follow the chain of parents, spouses, and children that connects them.')}
          </p>
        </header>

        <div className="o-card max-w-2xl p-6 sm:p-7">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <PersonSearchInput label={t('Person A')} selected={a} onSelect={setA} />
            <PersonSearchInput label={t('Person B')} selected={b} onSelect={setB} />
          </div>
          <div className="mt-5">
            <Button onClick={run} disabled={!a || !b} loading={calc.isPending}>
              {t('Calculate relationship')}
            </Button>
          </div>
          {error && (
            <div className="mt-4">
              <FormError message={error} />
            </div>
          )}
        </div>

        {result && !error && (
          <div className="o-card max-w-2xl p-6 sm:p-7">
            {result.connected ? (
              <>
                <h2 className="text-base font-semibold text-ink">{t('Connection found')}</h2>
                <ol className="mt-4 flex flex-col">
                  {result.path.map((step, i) => (
                    <li key={step.id} className="relative flex gap-4 pb-5 last:pb-0">
                      {i < result.path.length - 1 && (
                        <span className="absolute left-[13px] top-8 h-[calc(100%-2rem)] w-px bg-accent-edge" aria-hidden="true" />
                      )}
                      <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-400 text-xs font-semibold text-emerald-950">
                        {i + 1}
                      </span>
                      <div className="min-w-0 pt-0.5">
                        <p className="text-sm font-semibold text-ink">{step.name}</p>
                        {step.via && <p className="text-xs text-ink-muted">{step.via}</p>}
                      </div>
                    </li>
                  ))}
                </ol>
              </>
            ) : (
              <div className="o-empty border-0 bg-transparent p-0">
                {t('No connection found between these two people in the Global Tree.')}
              </div>
            )}
          </div>
        )}
      </div>
    </AppLayout>
  );
}
