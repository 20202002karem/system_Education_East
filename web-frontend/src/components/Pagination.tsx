import { Button } from './Button';
import type { PageMeta } from '../lib/types';

export function Pagination({ meta, onPageChange }: { meta: PageMeta; onPageChange: (page: number) => void }) {
  const totalPages = Math.max(1, Math.ceil(meta.total / meta.per_page));
  if (totalPages <= 1) return null;

  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 'var(--space-3)', marginTop: 'var(--space-4)' }}>
      <Button variant="secondary" disabled={meta.page <= 1} onClick={() => onPageChange(meta.page - 1)}>السابق</Button>
      <span style={{ color: 'var(--color-text-muted)', fontSize: 'var(--font-size-sm)' }}>
        صفحة {meta.page} من {totalPages} — {meta.total} سجل
      </span>
      <Button variant="secondary" disabled={meta.page >= totalPages} onClick={() => onPageChange(meta.page + 1)}>التالي</Button>
    </div>
  );
}
