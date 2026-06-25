import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { authApi } from '@core/api/endpoints/auth';
import { useAuthStore } from '@core/auth/store';
import { AppLayout } from '../components/AppLayout';

function StatCard({ label, value, dark }: { label: string; value: number | string; dark?: boolean }) {
  return (
    <div
      className={
        dark
          ? 'rounded-xl bg-[#1f252b] p-4 text-white'
          : 'rounded-xl border border-[#e3e8ee] bg-white p-4 text-[#1f252b] shadow-sm'
      }
    >
      <p className={`text-xs uppercase tracking-[0.25em] ${dark ? 'text-white/60' : 'text-[#6f7b83]'}`}>{label}</p>
      <p className="mt-2 text-3xl font-semibold">{value}</p>
    </div>
  );
}

export function Dashboard() {
  const user = useAuthStore((s) => s.user);
  const { data: stats } = useQuery({ queryKey: ['me', 'stats'], queryFn: authApi.stats });
  const { data: onboarding } = useQuery({ queryKey: ['me', 'onboarding'], queryFn: authApi.onboarding });
  const completed = onboarding?.steps.filter((s) => s.complete).length ?? 0;

  return (
    <AppLayout>
      <div className="genealogy-shell space-y-6">
        <section className="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
          <div className="grid gap-8 lg:grid-cols-[1.6fr_.9fr] lg:items-center">
            <div className="space-y-4">
              <p className="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">
                Family Storytelling Platform
              </p>
              <h1 className="max-w-3xl text-4xl font-semibold tracking-tight text-[#1f252b] sm:text-5xl">
                Welcome back{user?.first_name ? `, ${user.first_name}` : ''}.
              </h1>
              <p className="max-w-2xl text-base leading-7 text-[#4f5963]">
                Build living family trees, biographies, and relationship maps in one place — private
                trees, person profiles, parent and spouse links, and a workspace that grows toward
                records, timelines, and collaboration.
              </p>
              <div className="flex gap-3 pt-1">
                <Link to="/trees" className="rounded-md bg-[#1f252b] px-5 py-2.5 text-sm font-medium text-white hover:bg-black">
                  Open my trees
                </Link>
                <Link to="/import" className="rounded-md border border-[#d4dae1] px-5 py-2.5 text-sm font-medium text-[#1f252b] hover:bg-[#f7f9fb]">
                  Import GEDCOM
                </Link>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-3 rounded-2xl border border-[#e3e8ee] bg-[#f7f9fb] p-4">
              <StatCard label="Trees" value={stats?.trees ?? '—'} dark />
              <StatCard label="Profiles" value={stats?.profiles ?? '—'} />
              <StatCard label="Living" value={stats?.living ?? '—'} />
              <StatCard label="Links" value={stats?.relationships ?? '—'} />
            </div>
          </div>
        </section>

        {onboarding?.in_progress && (
          <section className="overflow-hidden rounded-2xl border border-[#bfdbfe] bg-[#eff6ff] px-8 py-7 shadow-sm">
            <div className="flex items-start justify-between gap-4">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.35em] text-[#2563eb]">Getting started</p>
                <h2 className="mt-1 text-xl font-semibold text-[#1f252b]">Complete your setup</h2>
                <p className="mt-1 text-sm text-[#4f5963]">Follow these steps to get your family workspace ready.</p>
              </div>
              <span className="shrink-0 rounded-full bg-[#2563eb] px-3 py-1 text-xs font-semibold text-white">
                {completed} / {onboarding.steps.length}
              </span>
            </div>
            <ol className="mt-6 space-y-3">
              {onboarding.steps.map((step) => (
                <li
                  key={step.title}
                  className={`flex items-center gap-4 rounded-xl border px-5 py-4 ${step.complete ? 'border-[#bbf7d0] bg-[#f0fdf4]' : 'border-[#c7d4df] bg-white'}`}
                >
                  <span
                    className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full ${step.complete ? 'bg-[#22c55e] text-white' : 'border-2 border-[#cbd5e1] bg-white'}`}
                  >
                    {step.complete ? '✓' : ''}
                  </span>
                  <span className={`flex-1 text-sm font-medium ${step.complete ? 'text-[#15803d] line-through' : 'text-[#1f252b]'}`}>
                    {step.title}
                  </span>
                  {!step.complete && (
                    <Link
                      to={step.link?.includes('tree') ? '/trees' : (step.link ?? '/dashboard')}
                      className="text-sm font-semibold text-[#2563eb]"
                    >
                      {step.cta ?? 'Go'} →
                    </Link>
                  )}
                </li>
              ))}
            </ol>
          </section>
        )}
      </div>
    </AppLayout>
  );
}
