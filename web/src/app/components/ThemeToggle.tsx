import { useState } from 'react';
import { useT } from '@core/i18n';
import { applyTheme, currentTheme, type Theme } from '../lib/theme';

export function ThemeToggle({ className = '' }: { className?: string }) {
  const t = useT();
  const [theme, setTheme] = useState<Theme>(() => currentTheme());

  const toggle = () => {
    const next: Theme = theme === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    setTheme(next);
  };

  return (
    <button
      type="button"
      onClick={toggle}
      className={`flex h-9 w-9 items-center justify-center rounded-full text-ink-muted transition hover:bg-fill hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500 ${className}`}
      aria-label={theme === 'dark' ? t('Switch to light mode') : t('Switch to dark mode')}
      title={theme === 'dark' ? t('Light mode') : t('Dark mode')}
    >
      {theme === 'dark' ? (
        // Sun
        <svg className="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round">
          <circle cx="12" cy="12" r="4" />
          <path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
        </svg>
      ) : (
        // Moon
        <svg className="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round">
          <path d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.6 6.6 0 0 0 9.8 9.8Z" />
        </svg>
      )}
    </button>
  );
}
