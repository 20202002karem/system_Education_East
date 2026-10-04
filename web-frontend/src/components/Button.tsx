import type { ButtonHTMLAttributes } from 'react';

type Variant = 'primary' | 'secondary' | 'danger' | 'ghost';

interface Props extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: Variant;
  loading?: boolean;
}

const styles: Record<Variant, React.CSSProperties> = {
  primary: { background: 'var(--color-primary)', color: 'var(--color-primary-contrast)', border: '1px solid var(--color-primary)' },
  secondary: { background: 'var(--color-surface)', color: 'var(--color-text)', border: '1px solid var(--color-border)' },
  danger: { background: 'var(--color-danger)', color: '#fff', border: '1px solid var(--color-danger)' },
  ghost: { background: 'transparent', color: 'var(--color-primary)', border: '1px solid transparent' },
};

export function Button({ variant = 'primary', loading, children, disabled, style, ...rest }: Props) {
  return (
    <button
      {...rest}
      disabled={disabled || loading}
      style={{
        ...styles[variant],
        padding: 'var(--space-2) var(--space-4)',
        borderRadius: 'var(--radius-sm)',
        fontSize: 'var(--font-size-md)',
        cursor: disabled || loading ? 'not-allowed' : 'pointer',
        opacity: disabled || loading ? 0.6 : 1,
        ...style,
      }}
    >
      {loading ? 'جارٍ التنفيذ…' : children}
    </button>
  );
}
