import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { localeApi } from '../api/endpoints/locale';
import {
  applyDocumentLang,
  browserLang,
  detectInitialLang,
  dirFor,
  hasManualChoice,
  isLangCode,
  MANUAL_KEY,
  STORAGE_KEY,
  type LangCode,
} from './config';
import { setActiveLang, translate, type TranslateVars } from './translate';

export type { TranslateVars };
export { translate };

export interface I18nContextValue {
  lang: LangCode;
  dir: 'ltr' | 'rtl';
  setLang: (lang: LangCode) => void;
  t: (key: string, vars?: TranslateVars) => string;
}

const I18nContext = createContext<I18nContextValue | null>(null);

export function I18nProvider({ children }: { children: ReactNode }) {
  const [lang, setLangState] = useState<LangCode>(() => {
    const initial = detectInitialLang();
    applyDocumentLang(initial);
    setActiveLang(initial);
    return initial;
  });

  const applyLang = useCallback((next: LangCode) => {
    setLangState(next);
    applyDocumentLang(next);
    setActiveLang(next);
  }, []);

  // User picked a language from the selector: persist + lock out auto-detection.
  const setLang = useCallback(
    (next: LangCode) => {
      applyLang(next);
      try {
        window.localStorage.setItem(STORAGE_KEY, next);
        window.localStorage.setItem(MANUAL_KEY, '1');
      } catch {
        /* storage may be unavailable (private mode) — language still applies for the session */
      }
    },
    [applyLang],
  );

  // Automatic detection: browser language wins; the IP/geo hint fills in only
  // when the browser gives no supported-language signal. Never overrides a
  // manual choice, and does not persist (re-evaluated each visit until manual).
  useEffect(() => {
    if (hasManualChoice()) return;
    const fromBrowser = browserLang();
    if (fromBrowser) return; // browser already told us a supported language

    let cancelled = false;
    localeApi.suggest().then(({ locale }) => {
      if (cancelled || hasManualChoice()) return;
      if (isLangCode(locale)) applyLang(locale);
    });
    return () => {
      cancelled = true;
    };
  }, [applyLang]);

  const t = useCallback((key: string, vars?: TranslateVars) => translate(lang, key, vars), [lang]);

  const value = useMemo<I18nContextValue>(
    () => ({ lang, dir: dirFor(lang), setLang, t }),
    [lang, setLang, t],
  );

  return <I18nContext.Provider value={value}>{children}</I18nContext.Provider>;
}

export function useI18n(): I18nContextValue {
  const ctx = useContext(I18nContext);
  if (!ctx) throw new Error('useI18n must be used within <I18nProvider>.');
  return ctx;
}

/** Convenience hook when only the translate function is needed. */
export function useT(): I18nContextValue['t'] {
  return useI18n().t;
}
