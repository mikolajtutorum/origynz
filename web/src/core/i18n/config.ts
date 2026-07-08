export type LangCode = 'en' | 'pl' | 'ru' | 'ar';

export interface LanguageMeta {
  code: LangCode;
  label: string; // endonym (name in its own language)
  dir: 'ltr' | 'rtl';
}

export const LANGUAGES: LanguageMeta[] = [
  { code: 'en', label: 'English', dir: 'ltr' },
  { code: 'pl', label: 'Polski', dir: 'ltr' },
  { code: 'ru', label: 'Русский', dir: 'ltr' },
  { code: 'ar', label: 'العربية', dir: 'rtl' },
];

export const DEFAULT_LANG: LangCode = 'en';
export const STORAGE_KEY = 'origynz-lang';
// Set once the user picks a language from the selector. While present, automatic
// browser/IP detection never overrides their choice.
export const MANUAL_KEY = 'origynz-lang-manual';

export function isLangCode(value: string | null | undefined): value is LangCode {
  return value === 'en' || value === 'pl' || value === 'ru' || value === 'ar';
}

export function dirFor(lang: LangCode): 'ltr' | 'rtl' {
  return LANGUAGES.find((l) => l.code === lang)?.dir ?? 'ltr';
}

export function hasManualChoice(): boolean {
  try {
    return window.localStorage.getItem(MANUAL_KEY) === '1';
  } catch {
    return false;
  }
}

export function storedLang(): LangCode | null {
  try {
    const value = window.localStorage.getItem(STORAGE_KEY);
    return isLangCode(value) ? value : null;
  } catch {
    return null;
  }
}

/** First supported language from the browser's ordered language list. */
export function browserLang(): LangCode | null {
  if (typeof navigator === 'undefined') return null;
  const list = navigator.languages?.length ? navigator.languages : [navigator.language];
  for (const entry of list) {
    const code = entry?.slice(0, 2).toLowerCase();
    if (isLangCode(code)) return code;
  }
  return null;
}

/**
 * Synchronous best guess for the very first paint (no network): the user's saved
 * choice if they made one, otherwise the browser language, otherwise English.
 * IP-based refinement happens asynchronously after mount.
 */
export function detectInitialLang(): LangCode {
  if (hasManualChoice()) {
    const stored = storedLang();
    if (stored) return stored;
  }
  return browserLang() ?? DEFAULT_LANG;
}

/** Reflect the language on <html> so CSS, screen readers, and RTL all react. */
export function applyDocumentLang(lang: LangCode): void {
  if (typeof document === 'undefined') return;
  document.documentElement.lang = lang;
  document.documentElement.dir = dirFor(lang);
}
