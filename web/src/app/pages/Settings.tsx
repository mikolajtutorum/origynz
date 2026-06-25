import { useState } from 'react';
import { AppLayout } from '../components/AppLayout';
import { ProfileSection } from '../components/settings/ProfileSection';
import { SecuritySection } from '../components/settings/SecuritySection';
import { ApiTokensSection } from '../components/settings/ApiTokensSection';
import { AccountSection } from '../components/settings/AccountSection';

const TABS = [
  { key: 'profile', label: 'Profile' },
  { key: 'security', label: 'Security' },
  { key: 'tokens', label: 'API tokens' },
  { key: 'account', label: 'Data & account' },
] as const;

type TabKey = (typeof TABS)[number]['key'];

export function Settings() {
  const [tab, setTab] = useState<TabKey>('profile');

  return (
    <AppLayout>
      <h1 className="mb-6 text-2xl font-semibold text-[#1f252b]">Settings</h1>
      <div className="flex gap-8">
        <nav className="flex w-44 shrink-0 flex-col gap-1">
          {TABS.map((t) => (
            <button
              key={t.key}
              onClick={() => setTab(t.key)}
              className={[
                'rounded-md px-3 py-2 text-left text-sm transition-colors',
                tab === t.key ? 'bg-[#eff6ff] font-medium text-[#2563eb]' : 'text-[#5f6a74] hover:bg-[#f3f7fb]',
              ].join(' ')}
            >
              {t.label}
            </button>
          ))}
        </nav>
        <section className="min-w-0 flex-1">
          {tab === 'profile' && <ProfileSection />}
          {tab === 'security' && <SecuritySection />}
          {tab === 'tokens' && <ApiTokensSection />}
          {tab === 'account' && <AccountSection />}
        </section>
      </div>
    </AppLayout>
  );
}
