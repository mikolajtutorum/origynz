import { Link, Navigate } from 'react-router-dom';
import { useAuthStore } from '@core/auth/store';
import { useHealth } from '@core/queries/useHealth';

export function Landing() {
  const status = useAuthStore((s) => s.status);
  const { isSuccess: apiUp } = useHealth();

  // Signed-in users go straight to their workspace.
  if (status === 'authenticated') {
    return <Navigate to="/dashboard" replace />;
  }

  return (
    <main className="min-h-screen bg-neutral-50">
      <header className="mx-auto flex max-w-5xl items-center justify-between px-6 py-5">
        <span className="text-lg font-semibold text-neutral-900">Origynz</span>
        <nav className="flex items-center gap-3 text-sm">
          <Link to="/login" className="rounded-md px-3 py-1.5 text-neutral-700 hover:bg-neutral-100">
            Sign in
          </Link>
          <Link to="/register" className="rounded-md bg-neutral-900 px-3 py-1.5 font-medium text-white hover:bg-neutral-800">
            Create account
          </Link>
        </nav>
      </header>

      <section className="mx-auto max-w-3xl px-6 py-24 text-center">
        <h1 className="text-4xl font-bold tracking-tight text-neutral-900 sm:text-5xl">
          Map and preserve your family history.
        </h1>
        <p className="mx-auto mt-5 max-w-xl text-lg text-neutral-600">
          Build private or shared family trees with rich profiles, relationships, media, and
          GEDCOM import &amp; export — in a fast, modern workspace.
        </p>
        <div className="mt-8 flex items-center justify-center gap-3">
          <Link to="/register" className="rounded-md bg-neutral-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-neutral-800">
            Get started
          </Link>
          <Link to="/login" className="rounded-md border border-neutral-300 px-5 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-100">
            Sign in
          </Link>
        </div>
        <p className="mt-10 text-xs text-neutral-400">
          <span className={apiUp ? 'text-green-600' : 'text-neutral-400'}>●</span>{' '}
          {apiUp ? 'Connected to the Origynz API' : 'Connecting to the Origynz API…'}
        </p>
      </section>
    </main>
  );
}
