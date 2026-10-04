/** Loading / Empty / Error states — used consistently across every list screen (instructions §4). */

export function LoadingState({ label = 'جارٍ التحميل…' }: { label?: string }) {
  return (
    <div role="status" style={{ padding: 'var(--space-6)', textAlign: 'center', color: 'var(--color-text-muted)' }}>
      {label}
    </div>
  );
}

export function EmptyState({ title, hint }: { title: string; hint?: string }) {
  return (
    <div style={{ padding: 'var(--space-6)', textAlign: 'center', color: 'var(--color-text-muted)' }}>
      <p style={{ fontSize: 'var(--font-size-lg)', margin: 0 }}>{title}</p>
      {hint && <p style={{ fontSize: 'var(--font-size-sm)' }}>{hint}</p>}
    </div>
  );
}

export function ErrorState({ message, onRetry }: { message: string; onRetry?: () => void }) {
  return (
    <div
      role="alert"
      style={{
        padding: 'var(--space-4)',
        background: 'var(--color-danger-bg)',
        color: 'var(--color-danger)',
        borderRadius: 'var(--radius-sm)',
        marginBottom: 'var(--space-4)',
      }}
    >
      <p style={{ margin: 0 }}>{message}</p>
      {onRetry && (
        <button onClick={onRetry} style={{ marginTop: 'var(--space-2)', background: 'none', border: 'none', color: 'var(--color-danger)', textDecoration: 'underline', cursor: 'pointer' }}>
          إعادة المحاولة
        </button>
      )}
    </div>
  );
}
