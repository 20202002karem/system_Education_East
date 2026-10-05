import type { AssetStatus } from '../../lib/types';

export const statusLabels: Record<AssetStatus, string> = {
  working: 'يعمل',
  under_maintenance: 'قيد الصيانة',
  broken: 'معطّل',
  stored: 'مخزّن',
  in_transfer: 'قيد النقل',
  decommissioned: 'خارج الخدمة',
};

export const manualStatusOptions = (['working', 'under_maintenance', 'broken', 'stored'] as const)
  .map((v) => ({ value: v, label: statusLabels[v] }));

export function statusTone(s: AssetStatus): 'success' | 'warning' | 'danger' | 'neutral' {
  if (s === 'working') return 'success';
  if (s === 'under_maintenance') return 'warning';
  if (s === 'broken') return 'danger';
  return 'neutral';
}

/** Gaza time (Asia/Gaza) display of UTC ISO strings (Batch 3 §4: API is UTC, UI localizes). */
export function formatDateTime(iso: string | null | undefined): string {
  if (!iso) return '—';
  return new Intl.DateTimeFormat('ar', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Gaza' }).format(new Date(iso));
}
