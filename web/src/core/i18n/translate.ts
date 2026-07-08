import { DEFAULT_LANG, type LangCode } from './config';
import { translations } from './translations';

export type TranslateVars = Record<string, string | number>;

function interpolate(text: string, vars?: TranslateVars): string {
  if (!vars) return text;
  return text.replace(/\{(\w+)\}/g, (match, name) => (name in vars ? String(vars[name]) : match));
}

/**
 * Natural-key translation: the English string IS the key. Non-English locales
 * look the string up in their dictionary and fall back to English if it is
 * missing, so the app is always fully usable while translation coverage grows.
 */
export function translate(lang: LangCode, key: string, vars?: TranslateVars): string {
  if (lang === DEFAULT_LANG) return interpolate(key, vars);
  const dict = translations[lang];
  return interpolate(dict?.[key] ?? key, vars);
}

// Mirrors the current UI language outside React, so non-component code (e.g.
// the API client, translating server-sent error text) can translate without
// needing a hook. Kept in sync by I18nProvider whenever the language changes.
let activeLang: LangCode = DEFAULT_LANG;

export function setActiveLang(lang: LangCode): void {
  activeLang = lang;
}

export function getActiveLang(): LangCode {
  return activeLang;
}
