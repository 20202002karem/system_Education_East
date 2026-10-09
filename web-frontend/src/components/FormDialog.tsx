import { useEffect, useState, type FormEvent } from 'react';
import { Button } from './Button';
import { Input } from './Input';
import { Select } from './Select';
import { ApiError } from '../lib/apiClient';

export interface FieldDef {
  name: string;
  label: string;
  type?: 'text' | 'textarea' | 'number' | 'select' | 'datetime-local';
  required?: boolean;
  options?: { value: string; label: string }[];
  min?: number;
  maxLength?: number;
  initial?: string;
  hint?: string;
}

interface Props {
  open: boolean;
  title: string;
  description?: string;
  fields: FieldDef[];
  submitLabel: string;
  danger?: boolean;
  onSubmit: (values: Record<string, string>) => Promise<void>;
  onClose: () => void;
}

/**
 * Generic modal form used by every M3 action (triage, assign, close, cancel, reopen, propose…).
 * Server 422/409 messages are shown verbatim; the server stays the source of truth.
 */
export function FormDialog({ open, title, description, fields, submitLabel, danger, onSubmit, onClose }: Props) {
  const [values, setValues] = useState<Record<string, string>>({});
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (open) {
      setValues(Object.fromEntries(fields.map((f) => [f.name, f.initial ?? ''])));
      setError(null); setFieldErrors({});
    }
  }, [open]); // eslint-disable-line react-hooks/exhaustive-deps

  if (!open) return null;

  async function submit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true); setError(null); setFieldErrors({});
    try {
      await onSubmit(values);
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
        setFieldErrors(err.fields ?? {});
      } else {
        setError('تعذّر تنفيذ العملية');
      }
    } finally {
      setSubmitting(false);
    }
  }

  const set = (name: string, v: string) => setValues((cur) => ({ ...cur, [name]: v }));

  return (
    <div role="dialog" aria-modal="true" aria-label={title} style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.4)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000, overflowY: 'auto' }}>
      <form onSubmit={submit} style={{ background: 'var(--color-surface)', borderRadius: 'var(--radius-md)', padding: 'var(--space-5)', width: '92%', maxWidth: 480, boxShadow: 'var(--shadow-md)' }}>
        <h2 style={{ marginTop: 0, fontSize: 'var(--font-size-lg)' }}>{title}</h2>
        {description && <p style={{ color: 'var(--color-text-muted)' }}>{description}</p>}
        {error && <div role="alert" style={{ background: 'var(--color-danger-bg)', color: 'var(--color-danger)', padding: 'var(--space-3)', borderRadius: 'var(--radius-sm)', marginBottom: 'var(--space-4)' }}>{error}</div>}
        {fields.map((f) => {
          const err = fieldErrors[f.name]?.[0];
          const common = { id: `fd-${f.name}`, label: f.required ? `${f.label} *` : f.label, error: err };
          if (f.type === 'select') {
            return (
              <Select key={f.name} {...common} value={values[f.name] ?? ''} required={f.required}
                onChange={(e) => set(f.name, e.target.value)} options={[{ value: '', label: '— اختر —' }, ...(f.options ?? [])]} />
            );
          }
          if (f.type === 'textarea') {
            return (
              <div key={f.name} style={{ marginBottom: 'var(--space-4)' }}>
                <label htmlFor={common.id} style={{ display: 'block', marginBottom: 'var(--space-1)', fontSize: 'var(--font-size-sm)', color: 'var(--color-text-muted)' }}>{common.label}</label>
                <textarea id={common.id} rows={4} required={f.required} maxLength={f.maxLength} value={values[f.name] ?? ''}
                  onChange={(e) => set(f.name, e.target.value)} aria-invalid={!!err}
                  style={{ width: '100%', padding: 'var(--space-2) var(--space-3)', borderRadius: 'var(--radius-sm)', border: `1px solid ${err ? 'var(--color-danger)' : 'var(--color-border)'}`, font: 'inherit' }} />
                {err && <p style={{ color: 'var(--color-danger)', fontSize: 'var(--font-size-sm)', margin: 'var(--space-1) 0 0' }}>{err}</p>}
              </div>
            );
          }
          return (
            <Input key={f.name} {...common} type={f.type ?? 'text'} min={f.min} maxLength={f.maxLength} required={f.required} hint={f.hint}
              value={values[f.name] ?? ''} onChange={(e) => set(f.name, e.target.value)} />
          );
        })}
        <div style={{ display: 'flex', gap: 'var(--space-3)', marginTop: 'var(--space-5)' }}>
          <Button type="submit" variant={danger ? 'danger' : 'primary'} loading={submitting}>{submitLabel}</Button>
          <Button type="button" variant="secondary" onClick={onClose} disabled={submitting}>إلغاء</Button>
        </div>
      </form>
    </div>
  );
}
