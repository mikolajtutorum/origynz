export function FullScreenSpinner() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-paper">
      <div className="h-8 w-8 animate-spin rounded-full border-2 border-edge border-t-emerald-400" />
    </div>
  );
}

/** Inline spinner for section-level loading states. */
export function Spinner() {
  return (
    <div className="flex justify-center py-8">
      <div className="h-6 w-6 animate-spin rounded-full border-2 border-edge border-t-emerald-400" />
    </div>
  );
}
