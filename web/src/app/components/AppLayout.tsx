import { useEffect, useRef, useState, type ReactNode } from 'react';
import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom';
import { useAuthStore } from '@core/auth/store';
import { useLogout } from '@core/auth/hooks';
import { useT } from '@core/i18n';
import { CommandPalette } from './CommandPalette';
import { ThemeToggle } from './ThemeToggle';
import { LanguageSwitcher } from './LanguageSwitcher';
import {
  IconGlobe,
  IconHome,
  IconImport,
  IconLogout,
  IconMerge,
  IconPhoto,
  IconSearch,
  IconSettings,
  IconShield,
  IconTree,
} from './icons';

function initials(name?: string | null): string {
  if (!name) return '?';
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase())
    .join('');
}

export function LogoMark({ className = 'h-9 w-9' }: { className?: string }) {
  return (
    <span
      className={`flex items-center justify-center rounded-xl bg-emerald-400 text-emerald-950 ${className}`}
      style={{ boxShadow: '0 0 20px -6px rgba(52,211,153,.55)' }}
    >
      <svg className="h-[58%] w-[58%]" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
        <path d="M12 21v-8" />
        <path d="M12 13c0-3 2-5 5-5 0 3-2 5-5 5Z" />
        <path d="M12 13c0-3-2-5-5-5 0 3 2 5 5 5Z" />
        <path d="M12 8V3" />
        <path d="M5 21h14" />
      </svg>
    </span>
  );
}

function sideClass({ isActive }: { isActive: boolean }) {
  return `o-side-link ${isActive ? 'is-active' : ''}`;
}

const NAV = [
  { to: '/dashboard', label: 'Home', icon: IconHome, exact: false },
  { to: '/trees', label: 'Family trees', icon: IconTree },
  { to: '/media', label: 'Photos', icon: IconPhoto },
  { to: '/relationship-calculator', label: 'Global Tree', icon: IconGlobe },
];

export function AppLayout({ children, bleed }: { children: ReactNode; bleed?: boolean }) {
  const t = useT();
  const user = useAuthStore((s) => s.user);
  const logout = useLogout();
  const navigate = useNavigate();
  const location = useLocation();
  const [paletteOpen, setPaletteOpen] = useState(false);
  const [userMenuOpen, setUserMenuOpen] = useState(false);
  const userMenuRef = useRef<HTMLDivElement>(null);

  // ⌘K / Ctrl+K opens the command palette from anywhere.
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        setPaletteOpen((v) => !v);
      }
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, []);

  useEffect(() => {
    if (!userMenuOpen) return;
    const onClick = (e: MouseEvent) => {
      if (!userMenuRef.current?.contains(e.target as Node)) setUserMenuOpen(false);
    };
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setUserMenuOpen(false);
    };
    document.addEventListener('mousedown', onClick);
    document.addEventListener('keydown', onKey);
    return () => {
      document.removeEventListener('mousedown', onClick);
      document.removeEventListener('keydown', onKey);
    };
  }, [userMenuOpen]);

  useEffect(() => setUserMenuOpen(false), [location.pathname]);

  const onLogout = async () => {
    await logout.mutateAsync();
    navigate('/login', { replace: true });
  };

  return (
    // Bleed pages (the tree workspace) lock to the viewport: the canvas fills
    // the remaining space and inner panels scroll themselves — no page scroll.
    <div className={`flex ${bleed ? 'h-dvh overflow-hidden' : 'min-h-screen'}`}>
      {/* ── Sidebar (desktop) ── */}
      <aside className="o-sidebar">
        <Link to="/dashboard" className="flex items-center gap-2.5 px-5 pb-4 pt-6" aria-label="Origynz home">
          <LogoMark className="h-8 w-8" />
          <span className="font-display text-[19px] font-semibold tracking-tight text-ink">Origynz</span>
        </Link>

        <div className="px-3 pb-1">
          <button type="button" onClick={() => setPaletteOpen(true)} className="o-search-btn w-full justify-between">
            <span className="flex items-center gap-2.5">
              <IconSearch className="h-4 w-4" />
              {t('Search…')}
            </span>
            <span className="o-kbd">⌘K</span>
          </button>
        </div>

        <nav className="flex-1 space-y-0.5 px-3 pt-3" aria-label="Primary">
          {NAV.map((item) => (
            <NavLink key={item.to} to={item.to} className={sideClass}>
              <item.icon className="o-side-icon" />
              {t(item.label)}
            </NavLink>
          ))}

          <p className="o-side-heading">{t('Tools')}</p>
          <NavLink to="/import" className={sideClass}>
            <IconImport className="o-side-icon" />
            {t('Import GEDCOM')}
          </NavLink>
          <NavLink to="/duplicates" className={sideClass}>
            <IconMerge className="o-side-icon" />
            {t('Merge duplicates')}
          </NavLink>
          <NavLink to="/settings" className={sideClass}>
            <IconSettings className="o-side-icon" />
            {t('Settings')}
          </NavLink>
          {user?.is_super_admin && (
            <NavLink to="/admin" className={sideClass}>
              <IconShield className="o-side-icon" />
              {t('Administration')}
            </NavLink>
          )}
        </nav>

        <div className="border-t border-line p-3">
          <div className="flex items-center gap-3 rounded-xl px-2 py-2">
            <span className="o-avatar h-9 w-9 text-xs">{initials(user?.name)}</span>
            <div className="min-w-0 flex-1">
              <p className="truncate text-[13px] font-semibold text-ink">{user?.name}</p>
              <p className="truncate text-[11px] text-ink-muted">{user?.email}</p>
            </div>
            <button
              type="button"
              onClick={() => void onLogout()}
              className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-ink-muted transition hover:bg-fill hover:text-ink"
              aria-label={t('Log out')}
              title={t('Log out')}
            >
              <IconLogout className="h-4 w-4" />
            </button>
          </div>
        </div>
      </aside>

      {/* ── Main column ── */}
      <div className="flex min-w-0 flex-1 flex-col">
        <header className="o-topbar">
          {/* Mobile logo */}
          <Link to="/dashboard" className="flex items-center gap-2 lg:hidden" aria-label="Origynz home">
            <LogoMark className="h-8 w-8" />
            <span className="font-display text-[17px] font-semibold tracking-tight text-ink">Origynz</span>
          </Link>

          <div className="flex-1" />

          {/* Search (mobile icon; desktop handled in sidebar) */}
          <button
            type="button"
            onClick={() => setPaletteOpen(true)}
            className="flex h-9 w-9 items-center justify-center rounded-full text-ink-muted transition hover:bg-fill hover:text-ink lg:hidden"
            aria-label={t('Search')}
          >
            <IconSearch />
          </button>

          <LanguageSwitcher />
          <ThemeToggle />

          {/* User menu */}
          <div ref={userMenuRef} className="relative">
            <button
              type="button"
              className="flex min-h-10 items-center gap-2 rounded-full p-1 transition hover:bg-fill focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-400"
              aria-haspopup="true"
              aria-expanded={userMenuOpen}
              aria-label={t('Account menu')}
              onClick={() => setUserMenuOpen((v) => !v)}
            >
              <span className="o-avatar h-8 w-8 text-[11px]">{initials(user?.name)}</span>
            </button>
            {userMenuOpen && (
              <div className="o-menu right-0 w-64" role="menu">
                <div className="px-4 pb-2.5 pt-2">
                  <p className="truncate text-sm font-semibold text-ink">{user?.name}</p>
                  <p className="truncate text-xs text-ink-muted">{user?.email}</p>
                </div>
                <div className="o-menu-divider" />
                <Link to="/settings" role="menuitem" className="o-menu-item">
                  <IconSettings className="h-4 w-4 text-ink-muted" />
                  {t('Settings')}
                </Link>
                {user?.is_super_admin && (
                  <Link to="/admin" role="menuitem" className="o-menu-item">
                    <IconShield className="h-4 w-4 text-ink-muted" />
                    {t('Administration')}
                  </Link>
                )}
                <div className="o-menu-divider" />
                <button
                  type="button"
                  role="menuitem"
                  className="o-menu-item text-danger hover:text-danger-strong"
                  onClick={() => void onLogout()}
                >
                  <IconLogout className="h-4 w-4" />
                  {t('Log out')}
                </button>
              </div>
            )}
          </div>
        </header>

        <main className={bleed ? 'flex min-h-0 flex-1 flex-col pb-14 lg:pb-0' : 'flex-1 pb-20 lg:pb-4'}>
          {bleed ? children : <div className="o-page o-rise">{children}</div>}
        </main>
      </div>

      {/* ── Bottom tab bar (mobile) ── */}
      <nav className="o-tabbar" aria-label="Primary">
        {NAV.slice(0, 3).map((item) => (
          <NavLink key={item.to} to={item.to} className={({ isActive }) => `o-tab ${isActive ? 'is-active' : ''}`}>
            <item.icon className="h-5 w-5" />
            {item.label === 'Family trees' ? t('Family trees') : t(item.label)}
          </NavLink>
        ))}
        <button type="button" onClick={() => setPaletteOpen(true)} className="o-tab">
          <IconSearch className="h-5 w-5" />
          {t('Search')}
        </button>
      </nav>

      <CommandPalette open={paletteOpen} onClose={() => setPaletteOpen(false)} />
    </div>
  );
}
