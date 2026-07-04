import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { authApi } from '@core/api/endpoints/auth';
import { useAuthStore } from '@core/auth/store';
import { useTrees } from '@core/queries/trees';
import { AppLayout } from '../components/AppLayout';

function greeting(): string {
  const h = new Date().getHours();
  if (h < 5) return 'Welcome back';
  if (h < 12) return 'Good morning';
  if (h < 18) return 'Good afternoon';
  return 'Good evening';
}

function Stat({ label, value }: { label: string; value: number | string }) {
  return (
    <div className="o-stat">
      <div aria-hidden="true" className="pointer-events-none absolute -right-5 -top-5 h-16 w-16 rounded-full bg-accent-soft blur-2xl" />
      <p className="o-stat-label">{label}</p>
      <p className="o-stat-value">{value}</p>
    </div>
  );
}

function ArrowIcon({ className = 'h-3.5 w-3.5' }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M5 12h14M13 6l6 6-6 6" />
    </svg>
  );
}

export function Dashboard() {
  const user = useAuthStore((s) => s.user);
  const { data: stats } = useQuery({ queryKey: ['me', 'stats'], queryFn: authApi.stats });
  const { data: onboarding } = useQuery({ queryKey: ['me', 'onboarding'], queryFn: authApi.onboarding });
  const { data: trees } = useTrees();
  const completed = onboarding?.steps.filter((s) => s.complete).length ?? 0;
  const total = onboarding?.steps.length ?? 0;

  const today = new Date().toLocaleDateString(undefined, {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });

  return (
    <AppLayout>
      <div className="space-y-8">
        {/* Greeting + primary actions */}
        <header className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <div className="max-w-xl space-y-2">
            <p className="o-eyebrow">{today}</p>
            <h1 className="o-display text-3xl sm:text-4xl">
              {greeting()}
              {user?.first_name ? `, ${user.first_name}` : ''}.
            </h1>
            <p className="text-[15px] leading-7 text-ink-muted">
              Pick up your research where you left off — your trees, people, and family records live here.
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <Link to="/trees" className="o-btn-primary">
              Open my trees
            </Link>
            <Link to="/import" className="o-btn-secondary">
              Import GEDCOM
            </Link>
          </div>
        </header>

        {/* Stats */}
        <section aria-label="Family statistics" className="grid grid-cols-2 gap-3 lg:grid-cols-4">
          <Stat label="Trees" value={stats?.trees ?? '—'} />
          <Stat label="Profiles" value={stats?.profiles ?? '—'} />
          <Stat label="Living" value={stats?.living ?? '—'} />
          <Stat label="Relationships" value={stats?.relationships ?? '—'} />
        </section>

        {/* Onboarding */}
        {onboarding?.in_progress && (
          <section className="o-card overflow-hidden">
            <div className="border-b border-line bg-accent-soft px-6 py-5 sm:px-8">
              <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                  <p className="o-eyebrow">Getting started</p>
                  <h2 className="o-display mt-1 text-xl">Complete your setup</h2>
                </div>
                <div className="flex items-center gap-3">
                  <div
                    className="h-2 w-32 overflow-hidden rounded-full bg-fill-strong"
                    role="progressbar"
                    aria-valuenow={completed}
                    aria-valuemin={0}
                    aria-valuemax={total}
                  >
                    <div
                      className="h-full rounded-full bg-emerald-400 transition-all"
                      style={{ width: `${total ? Math.round((completed / total) * 100) : 0}%` }}
                    />
                  </div>
                  <span className="text-sm font-semibold text-accent">
                    {completed}/{total}
                  </span>
                </div>
              </div>
            </div>
            <ol className="divide-y divide-line/70">
              {onboarding.steps.map((step) => (
                <li key={step.title} className={`flex items-center gap-4 px-6 py-4 sm:px-8 ${step.complete ? 'opacity-60' : ''}`}>
                  <span
                    className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full ${
                      step.complete ? 'bg-emerald-400 text-emerald-950' : 'border-2 border-line-strong bg-surface'
                    }`}
                  >
                    {step.complete && (
                      <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="3" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                      </svg>
                    )}
                  </span>
                  <span className={`flex-1 text-sm font-medium ${step.complete ? 'text-ink-muted line-through' : 'text-ink'}`}>
                    {step.title}
                  </span>
                  {!step.complete && (
                    <Link to={step.link?.includes('tree') ? '/trees' : (step.link ?? '/dashboard')} className="o-btn-secondary o-btn-sm shrink-0">
                      {step.cta ?? 'Go'}
                    </Link>
                  )}
                </li>
              ))}
            </ol>
          </section>
        )}

        <section className="grid gap-6 xl:grid-cols-[1.25fr_.75fr]">
          {/* Trees */}
          <div className="space-y-4">
            <div className="flex items-center justify-between gap-4">
              <h2 className="o-display text-2xl">Your trees</h2>
              <Link to="/trees" className="o-btn-ghost o-btn-sm">
                All trees
                <ArrowIcon />
              </Link>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              {trees && trees.length > 0 ? (
                trees.slice(0, 4).map((tree) => (
                  <Link key={tree.id} to={`/trees/${tree.id}`} className="o-card o-card-hover group flex flex-col p-6">
                    <div className="flex items-start justify-between gap-3">
                      <h3 className="text-base font-semibold text-ink transition group-hover:text-accent">{tree.name}</h3>
                      <span className="o-chip-muted shrink-0 uppercase tracking-[0.14em]">{tree.privacy}</span>
                    </div>
                    <p className="mt-1 flex-1 text-sm text-ink-muted">{tree.home_region || 'Region not set yet'}</p>
                    <div className="mt-5 flex items-center justify-between text-sm">
                      <span className="text-ink-muted">{tree.people_count ?? 0} people</span>
                      <span className="inline-flex items-center gap-1 font-semibold text-accent">
                        Open
                        <ArrowIcon className="h-3.5 w-3.5 transition group-hover:translate-x-0.5" />
                      </span>
                    </div>
                  </Link>
                ))
              ) : (
                <div className="o-empty sm:col-span-2">
                  <p className="font-medium text-ink-soft">No family trees yet.</p>
                  <p className="mt-1">Create your first tree and start mapping ancestors, descendants, and spouse connections.</p>
                  <Link to="/trees" className="o-btn-primary o-btn-sm mt-5">
                    Create a tree
                  </Link>
                </div>
              )}
            </div>

            {trees && trees.length > 4 && (
              <p className="text-sm text-ink-muted">
                Showing 4 of {trees.length} trees.{' '}
                <Link to="/trees" className="font-medium text-accent hover:text-accent-strong">
                  See all
                </Link>
              </p>
            )}
          </div>

          {/* Quick actions */}
          <div className="space-y-6">
            <div className="o-card p-6">
              <h2 className="text-base font-semibold text-ink">Quick actions</h2>
              <div className="mt-4 grid gap-2">
                <Link to="/import" className="o-menu-item rounded-xl border border-line">
                  <span className="flex-1">Import a GEDCOM file</span>
                  <ArrowIcon className="h-4 w-4 text-ink-muted" />
                </Link>
                <Link to="/media" className="o-menu-item rounded-xl border border-line">
                  <span className="flex-1">Browse family photos</span>
                  <ArrowIcon className="h-4 w-4 text-ink-muted" />
                </Link>
                <Link to="/relationship-calculator" className="o-menu-item rounded-xl border border-line">
                  <span className="flex-1">How are two people related?</span>
                  <ArrowIcon className="h-4 w-4 text-ink-muted" />
                </Link>
                <Link to="/settings" className="o-menu-item rounded-xl border border-line">
                  <span className="flex-1">Account &amp; settings</span>
                  <ArrowIcon className="h-4 w-4 text-ink-muted" />
                </Link>
              </div>
            </div>

            <div className="o-dotgrid relative overflow-hidden rounded-2xl border border-accent-edge bg-inset p-6">
              <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0"
                style={{ background: 'radial-gradient(320px 180px at 20% 0%, rgba(52,211,153,.15), transparent 65%)' }}
              />
              <div className="relative">
                <p className="text-[11px] font-semibold uppercase tracking-[0.22em] text-accent">Private by default</p>
                <p className="o-display mt-2 text-xl">Your family history belongs to your family.</p>
                <p className="mt-2 text-sm leading-6 text-ink-soft">
                  Trees are private unless you share them, living relatives are protected, and your data is always exportable.
                </p>
              </div>
            </div>
          </div>
        </section>
      </div>
    </AppLayout>
  );
}
