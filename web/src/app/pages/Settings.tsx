import { useState } from 'react';
import { AppLayout } from '../components/AppLayout';
import { ProfileSection } from '../components/settings/ProfileSection';
import { SecuritySection } from '../components/settings/SecuritySection';
import { ApiTokensSection } from '../components/settings/ApiTokensSection';
import { AccountSection } from '../components/settings/AccountSection';

const TABS = [
  { key: 'profile', label: 'Profile', blurb: 'Your name, email, and public details.' },
  { key: 'security', label: 'Security', blurb: 'Password and two-factor authentication.' },
  { key: 'tokens', label: 'API tokens', blurb: 'Personal access tokens for the Origynz API.' },
  { key: 'account', label: 'Data & account', blurb: 'Export your data or close your account.' },
] as const;

type TabKey = (typeof TABS)[number]['key'];

export function Settings() {
  const [tab, setTab] = useState<TabKey>('profile');
  const active = TABS.find((t) => t.key === tab)!;

  return (
    <AppLayout>
      <div className="space-y-7">
        <header className="max-w-2xl space-y-2">
          <p className="o-eyebrow">Your account</p>
          <h1 className="o-display text-3xl sm:text-4xl">Settings</h1>
        </header>

        <div className="o-subnav" role="tablist" aria-label="Settings sections">
          {TABS.map((t) => (
            <button
              key={t.key}
              role="tab"
              aria-selected={tab === t.key}
              onClick={() => setTab(t.key)}
              className={`o-subnav-link ${tab === t.key ? 'is-active' : ''}`}
            >
              {t.label}
            </button>
          ))}
        </div>

        <section className="o-card max-w-3xl p-6 sm:p-8" aria-label={active.label}>
          <div className="mb-6">
            <h2 className="text-lg font-semibold text-ink">{active.label}</h2>
            <p className="mt-0.5 text-sm text-ink-muted">{active.blurb}</p>
          </div>
          {tab === 'profile' && <ProfileSection />}
          {tab === 'security' && <SecuritySection />}
          {tab === 'tokens' && <ApiTokensSection />}
          {tab === 'account' && <AccountSection />}
        </section>
      </div>
    </AppLayout>
  );
}
