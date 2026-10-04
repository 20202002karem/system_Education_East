import type { ReactNode } from 'react';

export function Card({ title, actions, children }: { title?: string; actions?: ReactNode; children: ReactNode }) {
  return (
    <section
      style={{
        background: 'var(--color-surface)',
        border: '1px solid var(--color-border)',
        borderRadius: 'var(--radius-md)',
        boxShadow: 'var(--shadow-sm)',
        padding: 'var(--space-5)',
        marginBottom: 'var(--space-5)',
      }}
    >
      {(title || actions) && (
        <header style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 'var(--space-4)' }}>
          {title && <h2 style={{ fontSize: 'var(--font-size-lg)', margin: 0 }}>{title}</h2>}
          {actions}
        </header>
      )}
      {children}
    </section>
  );
}
