import { Link, Navigate } from 'react-router-dom';
import { useAuthStore } from '@core/auth/store';
import { useHealth } from '@core/queries/useHealth';
import { LogoMark } from '../components/AppLayout';
import { ThemeToggle } from '../components/ThemeToggle';

function FeatureCard({ icon, title, copy, copper }: { icon: React.ReactNode; title: string; copy: string; copper?: boolean }) {
  return (
    <div className="o-card o-card-hover p-7">
      <span
        className={`flex h-11 w-11 items-center justify-center rounded-2xl ${
          copper ? 'bg-copper-400/10 text-copper-700 dark:text-copper-300' : 'bg-accent-soft text-accent'
        }`}
      >
        {icon}
      </span>
      <h3 className="mt-5 text-lg font-semibold text-ink">{title}</h3>
      <p className="mt-2 text-sm leading-6 text-ink-muted">{copy}</p>
    </div>
  );
}

function PreviewCard({ initials, name, dates, focus, copper }: { initials: string; name: string; dates: string; focus?: boolean; copper?: boolean }) {
  return (
    <div
      className={`flex items-center gap-2 rounded-2xl bg-elevated px-3.5 py-2.5 sm:px-5 ${
        focus
          ? 'border border-accent-edge py-3 shadow-[0_0_32px_-8px_rgba(52,211,153,.4)]'
          : 'border border-edge o-pop'
      }`}
    >
      <span className={`o-avatar h-8 w-8 text-[10px] ${copper ? 'bg-copper-400 text-copper-900' : ''}`}>{initials}</span>
      <div className="text-left">
        <p className="text-[13px] font-semibold leading-tight text-ink">{name}</p>
        <p className="text-[11px] text-ink-muted">{dates}</p>
      </div>
    </div>
  );
}

const stroke = { fill: 'none', stroke: 'currentColor', strokeWidth: 1.7, strokeLinecap: 'round', strokeLinejoin: 'round' } as const;

export function Landing() {
  const status = useAuthStore((s) => s.status);
  const { isSuccess: apiUp } = useHealth();

  // Signed-in users go straight to their workspace.
  if (status === 'authenticated') {
    return <Navigate to="/dashboard" replace />;
  }

  return (
    <main className="min-h-screen bg-paper">
      {/* Header */}
      <header className="sticky top-0 z-40 border-b border-line bg-paper/80 backdrop-blur-md">
        <div className="o-shell flex h-16 items-center justify-between gap-3">
          <Link to="/" className="flex items-center gap-2.5" aria-label="Origynz">
            <LogoMark />
            <span className="font-display text-[21px] font-semibold tracking-tight text-ink max-[380px]:hidden">Origynz</span>
          </Link>
          <nav className="flex items-center gap-2">
            <ThemeToggle />
            <Link to="/login" className="o-btn o-btn-sm o-btn-ghost max-sm:hidden">
              Log in
            </Link>
            <Link to="/register" className="o-btn-primary o-btn-sm">
              Get started
            </Link>
          </nav>
        </div>
      </header>

      {/* Hero */}
      <section className="relative overflow-hidden">
        {/* Ambient glow */}
        <div
          aria-hidden="true"
          className="pointer-events-none absolute inset-0"
          style={{
            background:
              'radial-gradient(700px 380px at 50% -8%, rgba(52,211,153,.16), transparent 65%), radial-gradient(520px 300px at 82% 22%, rgba(223,138,75,.07), transparent 60%)',
          }}
        />
        <div className="o-shell relative pb-16 pt-16 sm:pb-24 sm:pt-24">
          <div className="mx-auto max-w-3xl text-center">
            <p className="mx-auto inline-flex items-center gap-2 rounded-full border border-accent-edge bg-accent-soft px-3.5 py-1.5 text-[12px] font-medium text-accent">
              <span className="h-1.5 w-1.5 rounded-full bg-emerald-400" />
              Origynz — Family Storytelling Platform
            </p>
            <h1 className="o-display mt-6 text-[2.6rem] leading-[1.05] sm:text-7xl">
              Where family stories
              <br />
              <span className="bg-gradient-to-r from-emerald-700 via-emerald-600 to-copper-600 dark:from-emerald-300 dark:via-emerald-200 dark:to-copper-300 bg-clip-text text-transparent">take root.</span>
            </h1>
            <p className="mx-auto mt-6 max-w-2xl text-lg leading-8 text-ink-soft">
              Build living family trees, preserve photos and records, and map every relationship — private by
              default, collaborative when you choose.
            </p>
            <div className="mt-9 flex flex-wrap items-center justify-center gap-3">
              <Link to="/register" className="o-btn-primary min-h-12 px-7 text-base">
                Start your tree — free
              </Link>
              <Link to="/login" className="o-btn-secondary min-h-12 px-7 text-base">
                Log in
              </Link>
            </div>
            <p className="mt-4 text-[13px] text-ink-muted">GEDCOM import supported — bring your existing research with you.</p>
          </div>

          {/* Stylised tree preview */}
          <div className="relative mx-auto mt-16 max-w-4xl">
            <div
              aria-hidden="true"
              className="pointer-events-none absolute -inset-8 rounded-[3rem]"
              style={{ background: 'radial-gradient(60% 60% at 50% 40%, rgba(52,211,153,.1), transparent 70%)' }}
            />
            <div className="o-card relative overflow-hidden p-2 sm:p-3">
              <div className="o-dotgrid relative rounded-xl border border-edge-faint bg-inset px-4 py-10 sm:py-14">
                <div className="mx-auto flex max-w-xl flex-col items-center gap-6">
                  <div className="flex w-full items-center justify-center gap-4 sm:gap-10">
                    <PreviewCard initials="EW" name="Eleanor Whitfield" dates="1921 – 2004" copper />
                    <PreviewCard initials="JW" name="James Whitfield" dates="1918 – 1997" />
                  </div>
                  <svg className="h-10 w-40 text-accent/40" viewBox="0 0 160 40" fill="none" stroke="currentColor" strokeWidth="1.5">
                    <path d="M40 0v14h80V0M80 14v26" />
                  </svg>
                  <PreviewCard initials="MW" name="Margaret Whitfield" dates="b. 1948 · 4 children" focus />
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Features */}
      <section className="border-t border-line py-16 sm:py-20">
        <div className="o-shell">
          <div className="mx-auto max-w-2xl text-center">
            <p className="o-eyebrow">Everything in one workspace</p>
            <h2 className="o-display mt-3 text-3xl sm:text-4xl">Serious genealogy, made welcoming</h2>
          </div>
          <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <FeatureCard
              title="Living family trees"
              copy="Interactive tree charts with marriages, branches, and focus views — built to handle thousands of relatives smoothly."
              icon={
                <svg className="h-5.5 w-5.5" viewBox="0 0 24 24" {...stroke}>
                  <path d="M12 21v-8" /><path d="M12 13c0-3 2-5 5-5 0 3-2 5-5 5Z" /><path d="M12 13c0-3-2-5-5-5 0 3 2 5 5 5Z" /><path d="M12 8V3" /><path d="M5 21h14" />
                </svg>
              }
            />
            <FeatureCard
              copper
              title="GEDCOM import & export"
              copy="Bring decades of research from any genealogy tool, photos included — and take your data with you anytime."
              icon={
                <svg className="h-5.5 w-5.5" viewBox="0 0 24 24" {...stroke}>
                  <path d="M12 3v12" /><path d="m7 10 5 5 5-5" /><path d="M5 21h14" />
                </svg>
              }
            />
            <FeatureCard
              title="Rich person profiles"
              copy="Names, dates, places, photos, and notes on every relative — with living people protected by default."
              icon={
                <svg className="h-5.5 w-5.5" viewBox="0 0 24 24" {...stroke}>
                  <circle cx="12" cy="8" r="4" /><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5" />
                </svg>
              }
            />
            <FeatureCard
              copper
              title="Photos & media"
              copy="A family photo library linked to the people in your tree, with portraits on every card."
              icon={
                <svg className="h-5.5 w-5.5" viewBox="0 0 24 24" {...stroke}>
                  <rect x="3" y="5" width="18" height="14" rx="2" /><circle cx="9" cy="11" r="2" /><path d="m21 16-4.5-4.5L9 19" />
                </svg>
              }
            />
            <FeatureCard
              title="Relationship paths"
              copy="Ask how any two people are connected and follow the chain of parents, spouses, and children between them."
              icon={
                <svg className="h-5.5 w-5.5" viewBox="0 0 24 24" {...stroke}>
                  <circle cx="5" cy="6" r="2.5" /><circle cx="19" cy="18" r="2.5" /><path d="M7 7.5C11 10 13 14 17 16.5" />
                </svg>
              }
            />
            <FeatureCard
              copper
              title="Fast, modern workspace"
              copy="A responsive app that works beautifully on your phone at a family gathering or on a big screen at your desk."
              icon={
                <svg className="h-5.5 w-5.5" viewBox="0 0 24 24" {...stroke}>
                  <path d="M13 3 5 13.5h6L11 21l8-10.5h-6L13 3Z" />
                </svg>
              }
            />
          </div>
        </div>
      </section>

      {/* Privacy + CTA */}
      <section className="o-shell py-16 sm:py-24">
        <div className="o-dotgrid relative overflow-hidden rounded-[2rem] border border-accent-edge bg-inset px-6 py-14 text-center sm:px-12">
          <div
            aria-hidden="true"
            className="pointer-events-none absolute inset-0"
            style={{ background: 'radial-gradient(520px 260px at 50% 0%, rgba(52,211,153,.14), transparent 65%)' }}
          />
          <div className="relative">
            <p className="text-[11px] font-semibold uppercase tracking-[0.22em] text-accent">Private by default</p>
            <h2 className="o-display mx-auto mt-3 max-w-2xl text-3xl sm:text-4xl">Your family history belongs to your family.</h2>
            <p className="mx-auto mt-4 max-w-xl text-[15px] leading-7 text-ink-soft">
              Trees are private unless you share them. Living relatives are protected, exports are always
              available, and there are no ads — ever.
            </p>
            <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
              <Link to="/register" className="o-btn-primary min-h-12 px-7 text-base">
                Create your free account
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-line">
        <div className="o-shell flex flex-col gap-3 py-6 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-2.5">
            <LogoMark className="h-7 w-7" />
            <p className="text-sm text-ink-muted">
              <span className="font-semibold text-ink">Origynz</span>
              <span aria-hidden="true" className="mx-1.5">·</span>© {new Date().getFullYear()}
            </p>
          </div>
          <p className="text-[13px] text-ink-muted">
            <span className={apiUp ? 'text-accent' : 'text-ink-muted'}>●</span>{' '}
            {apiUp ? 'Connected to the Origynz API' : 'Connecting to the Origynz API…'}
          </p>
        </div>
      </footer>
    </main>
  );
}
