import type { ReactNode } from 'react';
import { Link, NavLink, useNavigate } from 'react-router-dom';
import { useAuthStore } from '@core/auth/store';
import { useLogout } from '@core/auth/hooks';

function initials(name?: string | null): string {
  if (!name) return '?';
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase())
    .join('');
}

function navClass({ isActive }: { isActive: boolean }) {
  return `workspace-nav-link ${isActive ? 'is-active' : ''}`;
}

function NavDropdown({ label, items, danger }: { label: string; danger?: boolean; items: { label: string; to: string }[] }) {
  return (
    <div className="group relative">
      <button className={`workspace-nav-link flex items-center gap-2 ${danger ? '!font-semibold !text-[#dc2626]' : ''}`}>
        <span>{label}</span>
        <span className="text-[9px] opacity-70">▾</span>
      </button>
      <div className="absolute left-0 top-full z-50 mt-1 hidden min-w-[220px] overflow-hidden rounded-[6px] border border-[#dde1e6] bg-white py-1 shadow-xl group-hover:block">
        {items.map((it) => (
          <Link
            key={it.to}
            to={it.to}
            className="block px-4 py-2.5 text-sm text-[#4f5963] transition-colors hover:bg-[#f3f7fb] hover:text-[#2563eb]"
          >
            {it.label}
          </Link>
        ))}
      </div>
    </div>
  );
}

export function AppLayout({ children, bleed }: { children: ReactNode; bleed?: boolean }) {
  const user = useAuthStore((s) => s.user);
  const logout = useLogout();
  const navigate = useNavigate();

  const onLogout = async () => {
    await logout.mutateAsync();
    navigate('/login', { replace: true });
  };

  return (
    <div className="flex min-h-screen flex-col">
      {/* ── Topbar ── */}
      <header className="flex h-11 shrink-0 items-center justify-between bg-[#666462] px-6 text-white">
        <div className="flex items-center gap-3 text-[16px] font-medium">
          <span>Origynz</span>
        </div>
        <div className="flex items-center gap-4 text-[14px]">
          <span className="flex items-center gap-1.5 text-white/80">
            <span>🇬🇧</span>
            <span className="text-[11px] font-semibold tracking-wide">EN</span>
          </span>
          <span className="workspace-topbar-icon-btn">✉</span>
          <div className="workspace-topbar-user">
            <span className="workspace-user-avatar !h-7 !w-7 !text-[10px]">{initials(user?.name)}</span>
            <span className="text-[13px] font-medium leading-none">{user?.name}</span>
          </div>
          <button onClick={onLogout} className="workspace-topbar-btn">
            Log out
          </button>
          <span className="workspace-topbar-btn cursor-default">Help</span>
        </div>
      </header>

      {/* ── Nav header ── */}
      <div className="flex h-[72px] shrink-0 items-center justify-center border-b border-[#ececec] bg-white">
        <div className="flex w-full max-w-[1200px] items-center justify-between px-8">
          <Link to="/dashboard" className="text-[28px] font-semibold tracking-tight text-[#5d5d5d] transition-colors duration-150 hover:text-[#2563eb]">
            Origynz
          </Link>

          <nav className="flex items-center gap-2 text-[#5f6a74]">
            <NavDropdown label="Home" items={[{ label: 'Home overview', to: '/dashboard' }]} />
            <NavDropdown
              label="Family tree"
              items={[
                { label: 'My family trees', to: '/trees' },
                { label: 'Import GEDCOM', to: '/import' },
              ]}
            />
            <NavLink to="/relationship-calculator" className={navClass}>
              Global Tree
            </NavLink>
            <NavLink to="/media" className={navClass}>
              Photos
            </NavLink>
            <span className="workspace-nav-link is-disabled">DNA</span>
            <span className="workspace-nav-link is-disabled">Research</span>
            <NavLink to="/settings" className={navClass}>
              Settings
            </NavLink>
            {user?.is_super_admin && <NavDropdown label="Admin" danger items={[{ label: 'Dashboard', to: '/admin' }]} />}
          </nav>

          <div className="w-10 text-right text-[#ababab]">✣</div>
        </div>
      </div>

      <main className={bleed ? 'flex min-h-0 flex-1 flex-col' : 'flex-1'}>
        {bleed ? children : <div className="mx-auto w-full max-w-[1200px] px-8 py-8">{children}</div>}
      </main>

      <footer className="border-t border-[#e6e6e6] bg-white px-8 py-5 text-center text-xs text-[#9a9a9a]">
        © {new Date().getFullYear()} Origynz · Genealogy workspace
      </footer>
    </div>
  );
}
