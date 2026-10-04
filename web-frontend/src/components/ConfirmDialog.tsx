import { Button } from './Button';

interface Props {
  open: boolean;
  title: string;
  description?: string;
  confirmLabel?: string;
  variant?: 'primary' | 'danger';
  loading?: boolean;
  onConfirm: () => void;
  onCancel: () => void;
}

/** Confirmation dialog for sensitive operations (instructions §4: disable user, archive site, revoke permission, etc.). */
export function ConfirmDialog({ open, title, description, confirmLabel = 'تأكيد', variant = 'primary', loading, onConfirm, onCancel }: Props) {
  if (!open) return null;
  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="confirm-dialog-title"
      style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.4)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000 }}
    >
      <div style={{ background: 'var(--color-surface)', borderRadius: 'var(--radius-md)', padding: 'var(--space-5)', maxWidth: 420, width: '90%', boxShadow: 'var(--shadow-md)' }}>
        <h2 id="confirm-dialog-title" style={{ marginTop: 0, fontSize: 'var(--font-size-lg)' }}>{title}</h2>
        {description && <p style={{ color: 'var(--color-text-muted)' }}>{description}</p>}
        <div style={{ display: 'flex', gap: 'var(--space-3)', justifyContent: 'flex-start', marginTop: 'var(--space-5)' }}>
          <Button variant={variant} onClick={onConfirm} loading={loading}>{confirmLabel}</Button>
          <Button variant="secondary" onClick={onCancel} disabled={loading}>إلغاء</Button>
        </div>
      </div>
    </div>
  );
}
