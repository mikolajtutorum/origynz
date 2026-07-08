import { useState } from 'react';
import { useT } from '@core/i18n';
import { AppLayout } from '../components/AppLayout';
import { ProfileSection } from '../components/settings/ProfileSection';
import { SecuritySection } from '../components/settings/SecuritySection';
import { ApiTokensSection } from '../components/settings/ApiTokensSection';
import { AccountSection } from '../components/settings/AccountSection';
import { DnaSection } from '../components/settings/DnaSection';
import { IntegrationsSection } from '../components/settings/IntegrationsSection';
import { LanguageSection } from '../components/settings/LanguageSection';

const TABS = [
  { key: 'profile', label: 'Profile', blurb: 'Your name, email, and public details.' },
  { key: 'security', label: 'Security', blurb: 'Password and two-factor authentication.' },
  { key: 'language', label: 'Language', blurb: 'Choose the language used across the interface.' },
  { key: 'dna', label: 'DNA', blurb: 'Upload and manage your raw DNA test data.' },
  { key: 'integrations', label: 'Integrations', blurb: 'Connect FamilySearch, WikiTree, and Geni.' },
  { key: 'tokens', label: 'API tokens', blurb: 'Personal access tokens for the Origynz API.' },
  { key: 'account', label: 'Data & account', blurb: 'Export your data or close your account.' },
] as const;

type TabKey = (typeof TABS)[number]['key'];

export function Settings() {
  const t = useT();
  const [tab, setTab] = useState<TabKey>('profile');
  const active = TABS.find((x) => x.key === tab)!;

  return (
    <AppLayout>
      <div className="space-y-7">
        <header className="max-w-2xl space-y-2">
          <p className="o-eyebrow">{t('Your account')}</p>
          <h1 className="o-display text-3xl sm:text-4xl">{t('Settings')}</h1>
        </header>

        <div className="o-subnav" role="tablist" aria-label={t('Settings')}>
          {TABS.map((x) => (
            <button
              key={x.key}
              role="tab"
              aria-selected={tab === x.key}
              onClick={() => setTab(x.key)}
              className={`o-subnav-link ${tab === x.key ? 'is-active' : ''}`}
            >
              {t(x.label)}
            </button>
          ))}
        </div>

        <section className="o-card max-w-3xl p-6 sm:p-8" aria-label={t(active.label)}>
          <div className="mb-6">
            <h2 className="text-lg font-semibold text-ink">{t(active.label)}</h2>
            <p className="mt-0.5 text-sm text-ink-muted">{t(active.blurb)}</p>
          </div>
          {tab === 'profile' && <ProfileSection />}
          {tab === 'security' && <SecuritySection />}
          {tab === 'language' && <LanguageSection />}
          {tab === 'dna' && <DnaSection />}
          {tab === 'integrations' && <IntegrationsSection />}
          {tab === 'tokens' && <ApiTokensSection />}
          {tab === 'account' && <AccountSection />}
        </section>
      </div>
    </AppLayout>
  );
}
