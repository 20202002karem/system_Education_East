import type { SelectHTMLAttributes } from 'react';

interface Option { value: string; label: string; }
interface Props extends SelectHTMLAttributes<HTMLSelectElement> {
  label: string;
  options: Option[];
  error?: string;
}

export function Select({ label, options, error, id, ...rest }: Props) {
  const selectId = id ?? `select-${label}`;
  return (
    <div style={{ marginBottom: 'var(--space-4)' }}>
      <label htmlFor={selectId} style={{ display: 'block', marginBottom: 'var(--space-1)', fontSize: 'var(--font-size-sm)', color: 'var(--color-text-muted)' }}>
        {label}
      </label>
      <select
        id={selectId}
        {...rest}
        style={{ width: '100%', padding: 'var(--space-2) var(--space-3)', borderRadius: 'var(--radius-sm)', border: `1px solid ${error ? 'var(--color-danger)' : 'var(--color-border)'}` }}
      >
        {options.map((o) => (
          <option key={o.value} value={o.value}>{o.label}</option>
        ))}
      </select>
      {error && <p style={{ color: 'var(--color-danger)', fontSize: 'var(--font-size-sm)', margin: 'var(--space-1) 0 0' }}>{error}</p>}
    </div>
  );
}
