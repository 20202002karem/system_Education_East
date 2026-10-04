type Tone = 'success' | 'danger' | 'neutral' | 'warning';

const bg: Record<Tone, string> = {
  success: 'var(--color-success-bg)',
  danger: 'var(--color-danger-bg)',
  neutral: 'var(--color-disabled-bg)',
  warning: 'var(--color-warning-bg)',
};
const fg: Record<Tone, string> = {
  success: 'var(--color-success)',
  danger: 'var(--color-danger)',
  neutral: 'var(--color-text-muted)',
  warning: 'var(--color-warning)',
};

export function Badge({ tone, children }: { tone: Tone; children: React.ReactNode }) {
  return (
    <span style={{ background: bg[tone], color: fg[tone], padding: '2px 10px', borderRadius: 999, fontSize: 'var(--font-size-sm)' }}>
      {children}
    </span>
  );
}
