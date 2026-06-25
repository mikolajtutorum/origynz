export function FullScreenSpinner() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-neutral-50">
      <div className="h-8 w-8 animate-spin rounded-full border-2 border-neutral-300 border-t-neutral-700" />
    </div>
  );
}
