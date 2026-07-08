import { useEffect, useRef, useState } from 'react';
import { useI18n, LANGUAGES } from '@core/i18n';

function IconGlobe({ className = 'h-4 w-4' }: { className?: string }) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" className={className}>
      <circle cx="12" cy="12" r="9" />
      <path d="M3 12h18" />
      <path d="M12 3c2.5 2.7 3.9 5.9 3.9 9s-1.4 6.3-3.9 9c-2.5-2.7-3.9-5.9-3.9-9S9.5 5.7 12 3Z" />
    </svg>
  );
}

/** Compact language dropdown for the app shell / auth screens. */
export function LanguageSwitcher({ className = '' }: { className?: string }) {
  const { lang, setLang, t } = useI18n();
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);
  const current = LANGUAGES.find((l) => l.code === lang);

  useEffect(() => {
    if (!open) return;
    const onClick = (e: MouseEvent) => {
      if (!ref.current?.contains(e.target as Node)) setOpen(false);
    };
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && setOpen(false);
    document.addEventListener('mousedown', onClick);
    document.addEventListener('keydown', onKey);
    return () => {
      document.removeEventListener('mousedown', onClick);
      document.removeEventListener('keydown', onKey);
    };
  }, [open]);

  return (
    <div ref={ref} className={`relative ${className}`}>
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="flex min-h-10 items-center gap-1.5 rounded-full px-2.5 py-1.5 text-sm text-ink-muted transition hover:bg-fill hover:text-ink"
        aria-haspopup="true"
        aria-expanded={open}
        aria-label={t('Interface language')}
      >
        <IconGlobe />
        <span className="text-[13px] font-medium">{current?.label ?? 'English'}</span>
      </button>
      {open && (
        <div className="o-menu end-0 w-44" role="menu">
          {LANGUAGES.map((l) => (
            <button
              key={l.code}
              type="button"
              role="menuitemradio"
              aria-checked={l.code === lang}
              onClick={() => {
                setLang(l.code);
                setOpen(false);
              }}
              className={`o-menu-item justify-between ${l.code === lang ? 'font-semibold text-ink' : ''}`}
            >
              <span dir={l.dir}>{l.label}</span>
              {l.code === lang && (
                <svg className="h-4 w-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M20 6 9 17l-5-5" />
                </svg>
              )}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
