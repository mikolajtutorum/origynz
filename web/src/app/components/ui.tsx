import type {
  ButtonHTMLAttributes,
  InputHTMLAttributes,
  ReactNode,
  SelectHTMLAttributes,
  TextareaHTMLAttributes,
} from 'react';
import { forwardRef } from 'react';
import { Link } from 'react-router-dom';
import { LogoMark } from './AppLayout';

export const TextField = forwardRef<
  HTMLInputElement,
  InputHTMLAttributes<HTMLInputElement> & { label: string; error?: string }
>(function TextField({ label, error, id, ...props }, ref) {
  const inputId = id ?? props.name;
  return (
    <div className="flex flex-col gap-1.5">
      <label htmlFor={inputId} className="o-label">
        {label}
      </label>
      <input ref={ref} id={inputId} className="o-input" {...props} />
      {error && <span className="text-xs text-danger">{error}</span>}
    </div>
  );
});

export const Select = forwardRef<
  HTMLSelectElement,
  SelectHTMLAttributes<HTMLSelectElement> & { label: string; error?: string; children: ReactNode }
>(function Select({ label, error, id, children, ...props }, ref) {
  const selectId = id ?? props.name;
  return (
    <div className="flex flex-col gap-1.5">
      <label htmlFor={selectId} className="o-label">
        {label}
      </label>
      <select ref={ref} id={selectId} className="o-input" {...props}>
        {children}
      </select>
      {error && <span className="text-xs text-danger">{error}</span>}
    </div>
  );
});

export const Textarea = forwardRef<
  HTMLTextAreaElement,
  TextareaHTMLAttributes<HTMLTextAreaElement> & { label: string; error?: string }
>(function Textarea({ label, error, id, ...props }, ref) {
  const areaId = id ?? props.name;
  return (
    <div className="flex flex-col gap-1.5">
      <label htmlFor={areaId} className="o-label">
        {label}
      </label>
      <textarea ref={ref} id={areaId} rows={3} className="o-input resize-none" {...props} />
      {error && <span className="text-xs text-danger">{error}</span>}
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
      <label className="flex items-start gap-2.5 text-sm text-ink-soft">
        <input type="checkbox" className="mt-0.5 h-4 w-4 rounded accent-emerald-400" {...props} />
        <span>{label}</span>
      </label>
      {error && <span className="text-xs text-danger">{error}</span>}
    </div>
  );
}

export function Button({
  children,
  loading,
  variant = 'primary',
  ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & { loading?: boolean; variant?: 'primary' | 'secondary' | 'danger' }) {
  const cls =
    variant === 'secondary' ? 'o-btn-secondary' : variant === 'danger' ? 'o-btn-danger' : 'o-btn-primary';
  return (
    <button className={cls} disabled={loading || props.disabled} {...props}>
      {loading ? 'Please wait…' : children}
    </button>
  );
}

export function FormError({ message }: { message?: string | null }) {
  if (!message) return null;
  return <div className="o-alert-error">{message}</div>;
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
    <div
      className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 p-4 backdrop-blur-[2px]"
      onClick={onClose}
    >
      <div
        className="mt-12 w-full max-w-lg rounded-2xl border border-edge bg-elevated p-6 o-pop sm:p-7"
        role="dialog"
        aria-label={title}
        onClick={(e) => e.stopPropagation()}
      >
        <div className="mb-5 flex items-center justify-between gap-4">
          <h2 className="o-display text-xl">{title}</h2>
          <button
            onClick={onClose}
            className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-ink-muted transition hover:bg-fill hover:text-ink"
            aria-label="Close"
          >
            <svg className="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round">
              <path d="M6 6l12 12M18 6L6 18" />
            </svg>
          </button>
        </div>
        {children}
      </div>
    </div>
  );
}

export function AuthCard({ title, subtitle, children }: { title: string; subtitle?: string; children: ReactNode }) {
  return (
    <main className="flex min-h-screen flex-col items-center justify-center bg-paper px-4 py-10">
      <Link to="/" className="mb-7 flex items-center gap-3" aria-label="Origynz home">
        <LogoMark />
        <span className="font-display text-[26px] font-semibold tracking-tight text-ink">Origynz</span>
      </Link>
      <div className="o-card w-full max-w-md p-6 sm:p-9">
        <div className="mb-6 text-center">
          <h1 className="o-display text-2xl">{title}</h1>
          {subtitle && <p className="mt-1.5 text-sm leading-6 text-ink-muted">{subtitle}</p>}
        </div>
        {children}
      </div>
      <p className="mt-6 text-center text-xs text-ink-muted">Where family stories take root.</p>
    </main>
  );
}
