import { Link } from 'react-router-dom';
import { LogoMark } from '../components/AppLayout';

export function NotFound() {
  return (
    <main className="flex min-h-screen flex-col items-center justify-center gap-5 bg-paper px-4 text-center">
      <LogoMark className="h-12 w-12" />
      <div className="space-y-2">
        <p className="o-eyebrow">404</p>
        <h1 className="o-display text-3xl">This branch doesn&apos;t exist.</h1>
        <p className="text-sm leading-6 text-ink-muted">The page you&apos;re looking for was moved, renamed, or never grew here.</p>
      </div>
      <Link to="/" className="o-btn-primary">
        Back to start
      </Link>
    </main>
  );
}
