import { useI18n, LANGUAGES } from '@core/i18n';

export function LanguageSection() {
  const { lang, setLang, t } = useI18n();

  return (
    <div className="max-w-md">
      <p className="o-label mb-2">{t('Interface language')}</p>
      <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
        {LANGUAGES.map((l) => {
          const active = l.code === lang;
          return (
            <button
              key={l.code}
              onClick={() => setLang(l.code)}
              className={`flex items-center justify-between rounded-xl border px-4 py-3 text-left transition ${
                active ? 'border-emerald-400 bg-emerald-400/10' : 'border-line hover:border-line-strong'
              }`}
            >
              <span className="text-sm font-medium text-ink" dir={l.dir}>
                {l.label}
              </span>
              {active && (
                <svg className="h-4 w-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M20 6 9 17l-5-5" />
                </svg>
              )}
            </button>
          );
        })}
      </div>
    </div>
  );
}
