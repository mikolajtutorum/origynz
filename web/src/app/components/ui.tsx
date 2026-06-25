import type {
  ButtonHTMLAttributes,
  InputHTMLAttributes,
  ReactNode,
  SelectHTMLAttributes,
  TextareaHTMLAttributes,
} from 'react';
import { forwardRef } from 'react';

export const TextField = forwardRef<
  HTMLInputElement,
  InputHTMLAttributes<HTMLInputElement> & { label: string; error?: string }
>(function TextField({ label, error, id, ...props }, ref) {
  const inputId = id ?? props.name;
  return (
    <div className="flex flex-col gap-1">
      <label htmlFor={inputId} className="text-sm font-medium text-neutral-700">
        {label}
      </label>
      <input
        ref={ref}
        id={inputId}
        className="rounded-md border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
        {...props}
      />
      {error && <span className="text-xs text-red-600">{error}</span>}
    </div>
  );
});

export const Select = forwardRef<
  HTMLSelectElement,
  SelectHTMLAttributes<HTMLSelectElement> & { label: string; error?: string; children: ReactNode }
>(function Select({ label, error, id, children, ...props }, ref) {
  const selectId = id ?? props.name;
  return (
    <div className="flex flex-col gap-1">
      <label htmlFor={selectId} className="text-sm font-medium text-neutral-700">
        {label}
      </label>
      <select
        ref={ref}
        id={selectId}
        className="rounded-md border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
        {...props}
      >
        {children}
      </select>
      {error && <span className="text-xs text-red-600">{error}</span>}
    </div>
  );
});

export const Textarea = forwardRef<
  HTMLTextAreaElement,
  TextareaHTMLAttributes<HTMLTextAreaElement> & { label: string; error?: string }
>(function Textarea({ label, error, id, ...props }, ref) {
  const areaId = id ?? props.name;
  return (
    <div className="flex flex-col gap-1">
      <label htmlFor={areaId} className="text-sm font-medium text-neutral-700">
        {label}
      </label>
      <textarea
        ref={ref}
        id={areaId}
        rows={3}
        className="rounded-md border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200"
        {...props}
      />
      {error && <span className="text-xs text-red-600">{error}</span>}
    </div>
  );
});

export function Checkbox({
  label,
  error,
  ...props
}: InputHTMLAttributes<HTMLInputElement> & { label: ReactNode; error?: string }) {
  return (
    <div className="flex flex-col gap-1">
      <label className="flex items-start gap-2 text-sm text-neutral-700">
        <input type="checkbox" className="mt-0.5" {...props} />
        <span>{label}</span>
      </label>
      {error && <span className="text-xs text-red-600">{error}</span>}
    </div>
  );
}

export function Button({
  children,
  loading,
  ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & { loading?: boolean }) {
  return (
    <button
      className="inline-flex items-center justify-center rounded-md bg-[#1f252b] px-4 py-2 text-sm font-medium text-white transition hover:bg-black disabled:opacity-50"
      disabled={loading || props.disabled}
      {...props}
    >
      {loading ? 'Please wait…' : children}
    </button>
  );
}

export function FormError({ message }: { message?: string | null }) {
  if (!message) return null;
  return <div className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">{message}</div>;
}

export function Modal({
  title,
  onClose,
  children,
}: {
  title: string;
  onClose: () => void;
  children: ReactNode;
}) {
  return (
    <div className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-4" onClick={onClose}>
      <div
        className="mt-12 w-full max-w-lg rounded-xl border border-neutral-200 bg-white p-6 shadow-xl"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="mb-4 flex items-center justify-between">
          <h2 className="text-lg font-semibold text-neutral-900">{title}</h2>
          <button onClick={onClose} className="text-neutral-400 hover:text-neutral-700" aria-label="Close">
            ✕
          </button>
        </div>
        {children}
      </div>
    </div>
  );
}

export function AuthCard({ title, children }: { title: string; children: ReactNode }) {
  return (
    <main className="flex min-h-screen flex-col items-center justify-center px-4">
      <div className="mb-6 text-[30px] font-semibold tracking-tight text-[#5d5d5d]">Origynz</div>
      <div className="w-full max-w-sm rounded-2xl border border-[#e3e8ee] bg-white p-7 shadow-sm">
        <h1 className="mb-5 text-center text-lg font-semibold text-[#1f252b]">{title}</h1>
        {children}
      </div>
    </main>
  );
}
