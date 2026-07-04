// Light/dark theming: a `.dark` class on <html> drives the CSS variables in
// index.css. The user's explicit choice persists in localStorage; without one
// we follow the OS preference (and keep following it live).

export type Theme = 'light' | 'dark';

const KEY = 'origynz-theme';
const THEME_COLOR: Record<Theme, string> = { light: '#f6f4ee', dark: '#0b0f0c' };

export function systemTheme(): Theme {
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

export function currentTheme(): Theme {
  return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
}

export function applyTheme(theme: Theme, persist = true): void {
  document.documentElement.classList.toggle('dark', theme === 'dark');
  document.querySelector('meta[name="theme-color"]')?.setAttribute('content', THEME_COLOR[theme]);
  if (persist) {
    try {
      localStorage.setItem(KEY, theme);
    } catch {
      /* private mode */
    }
  }
}

// Follow OS changes only while the user hasn't made an explicit choice.
export function watchSystemTheme(): void {
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    try {
      if (localStorage.getItem(KEY)) return;
    } catch {
      /* ignore */
    }
    applyTheme(e.matches ? 'dark' : 'light', false);
  });
}
