import { useCallback, useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Pagination } from '../../components/Pagination';
import { Button } from '../../components/Button';
import { LoadingState, EmptyState, ErrorState } from '../../components/states';
import { ApiError } from '../../lib/apiClient';
import type { AppNotification, PageMeta } from '../../lib/types';
import { notificationsApi } from './notificationsApi';
import { formatDateTime } from '../requests/requestLabels';

/** M3-API-035/036 — internal notifications of the current user only. */
export function NotificationsPage() {
  const navigate = useNavigate();
  const [rows, setRows] = useState<AppNotification[] | null>(null);
  const [meta, setMeta] = useState<PageMeta>({ page: 1, per_page: 20, total: 0 });
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async (page = 1) => {
    setError(null);
    try { const r = await notificationsApi.list({ page, per_page: 20 }); setRows(r.data); setMeta(r.meta); }
    catch (e) { setError(e instanceof ApiError ? e.message : 'تعذّر تحميل الإشعارات'); }
  }, []);
  useEffect(() => { void load(1); }, [load]);

  async function open(n: AppNotification) {
    if (!n.read_at) await notificationsApi.markRead(n.id).catch(() => undefined);
    navigate(`/${n.source_type === 'request' ? 'requests' : 'tasks'}/${n.source_id}`);
  }

  return (
    <Card title="الإشعارات">
      {error && <ErrorState message={error} onRetry={() => load(meta.page)} />}
      {!error && rows === null && <LoadingState />}
      {!error && rows !== null && rows.length === 0 && <EmptyState title="لا توجد إشعارات" />}
      {!error && rows !== null && rows.map((n) => (
        <div key={n.id} style={{ display: 'flex', justifyContent: 'space-between', gap: 'var(--space-3)', padding: 'var(--space-3) 0', borderBottom: '1px solid var(--color-border)', fontWeight: n.read_at ? 'normal' : 'bold' }}>
          <span>{n.message}<br /><small style={{ color: 'var(--color-text-muted)', fontWeight: 'normal' }}>{formatDateTime(n.created_at)}</small></span>
          <Button variant="ghost" onClick={() => void open(n)}>فتح</Button>
        </div>
      ))}
      {rows !== null && <Pagination meta={meta} onPageChange={(p) => load(p)} />}
    </Card>
  );
}
