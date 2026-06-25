import { Link } from 'react-router-dom';

export function NotFound() {
  return (
    <main className="min-h-screen flex flex-col items-center justify-center gap-3 bg-neutral-50 text-neutral-900">
      <h1 className="text-2xl font-semibold">Page not found</h1>
      <Link to="/" className="text-blue-600 underline">
        Back to start
      </Link>
    </main>
  );
}
