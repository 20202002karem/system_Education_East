import type { InputHTMLAttributes } from 'react';

interface Props extends InputHTMLAttributes<HTMLInputElement> {
  label: string;
  error?: string;
  hint?: string;
}

export function Input({ label, error, hint, id, ...rest }: Props) {
  const inputId = id ?? `field-${label}`;
  return (
    <div style={{ marginBottom: 'var(--space-4)' }}>
      <label htmlFor={inputId} style={{ display: 'block', marginBottom: 'var(--space-1)', fontSize: 'var(--font-size-sm)', color: 'var(--color-text-muted)' }}>
        {label}
      </label>
      <input
        id={inputId}
        {...rest}
        aria-invalid={!!error}
        aria-describedby={error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined}
        style={{
          width: '100%',
          padding: 'var(--space-2) var(--space-3)',
          borderRadius: 'var(--radius-sm)',
          border: `1px solid ${error ? 'var(--color-danger)' : 'var(--color-border)'}`,
          fontSize: 'var(--font-size-md)',
        }}
      />
      {error && <p id={`${inputId}-error`} style={{ color: 'var(--color-danger)', fontSize: 'var(--font-size-sm)', margin: 'var(--space-1) 0 0' }}>{error}</p>}
      {!error && hint && <p id={`${inputId}-hint`} style={{ color: 'var(--color-text-muted)', fontSize: 'var(--font-size-sm)', margin: 'var(--space-1) 0 0' }}>{hint}</p>}
    </div>
  );
}
