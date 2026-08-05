import { type ReactNode } from "react";

export function Button({
  variant = "primary",
  className = "",
  children,
  ...props
}: React.ButtonHTMLAttributes<HTMLButtonElement> & { variant?: "primary" | "secondary" | "danger" }) {
  const styles =
    variant === "primary"
      ? "bg-indigo-600 hover:bg-indigo-700 text-white"
      : variant === "danger"
        ? "bg-red-600 hover:bg-red-700 text-white"
        : "bg-white hover:bg-gray-50 text-gray-700 border border-gray-300";
  return (
    <button
      className={`inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium transition-colors disabled:opacity-50 ${styles} ${className}`}
      {...props}
    >
      {children}
    </button>
  );
}

export function Card({ className = "", children }: { className?: string; children: ReactNode }) {
  return (
    <div className={`rounded-xl border border-gray-200 bg-white p-5 shadow-sm ${className}`}>
      {children}
    </div>
  );
}

export function Input({
  label,
  error,
  className = "",
  ...props
}: React.InputHTMLAttributes<HTMLInputElement> & { label?: string; error?: string }) {
  return (
    <label className="block">
      {label ? <span className="mb-1 block text-sm font-medium text-gray-700">{label}</span> : null}
      <input
        className={`w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 ${className}`}
        {...props}
      />
      {error ? <span className="mt-1 block text-xs text-red-600">{error}</span> : null}
    </label>
  );
}

export function Badge({
  sentiment,
  className = "",
}: {
  sentiment: string | null;
  className?: string;
}) {
  const palette: Record<string, string> = {
    positive: "bg-emerald-100 text-emerald-800",
    negative: "bg-red-100 text-red-800",
    neutral: "bg-gray-100 text-gray-700",
  };
  const fallback = "bg-gray-100 text-gray-400";
  return (
    <span
      className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${sentiment ? (palette[sentiment] ?? fallback) : fallback} ${className}`}
    >
      {sentiment ?? "unanalyzed"}
    </span>
  );
}

export function Spinner() {
  return (
    <div className="flex items-center justify-center py-10">
      <div className="h-8 w-8 animate-spin rounded-full border-2 border-gray-300 border-t-indigo-600" />
    </div>
  );
}

export function EmptyState({ message }: { message: string }) {
  return <div className="py-10 text-center text-sm text-gray-400">{message}</div>;
}

export function PageHeader({
  title,
  subtitle,
  action,
}: {
  title: string;
  subtitle?: string;
  action?: ReactNode;
}) {
  return (
    <div className="mb-6 flex items-center justify-between">
      <div>
        <h1 className="text-xl font-bold text-gray-900">{title}</h1>
        {subtitle ? <p className="mt-0.5 text-sm text-gray-500">{subtitle}</p> : null}
      </div>
      {action}
    </div>
  );
}
