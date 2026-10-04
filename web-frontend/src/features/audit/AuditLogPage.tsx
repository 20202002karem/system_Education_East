import { useEffect, useState } from 'react';
import { Card } from '../../components/Card';
import { Table } from '../../components/Table';
import { Badge } from '../../components/Badge';
import { Pagination } from '../../components/Pagination';
import { Input } from '../../components/Input';
import { Button } from '../../components/Button';
import { LoadingState, EmptyState, ErrorState } from '../../components/states';
import { ApiError } from '../../lib/apiClient';
import type { AuditLogEntry, PageMeta } from '../../lib/types';
import { auditApi } from './auditApi';

/** Read-only by design — no edit/delete affordance exists anywhere on this page (IN-12). */
export function AuditLogPage() {
  const [entries, setEntries] = useState<AuditLogEntry[] | null>(null);
  const [meta, setMeta] = useState<PageMeta>({ page: 1, per_page: 20, total: 0 });
  const [error, setError] = useState<string | null>(null);
  const [entityType, setEntityType] = useState('');
  const [actorId, setActorId] = useState('');

  async function load(page = 1) {
    setError(null);
    setEntries(null);
    try {
      const res = await auditApi.listLog({ entity_type: entityType || undefined, actor_id: actorId || undefined, page, per_page: 20 });
      setEntries(res.data);
      setMeta(res.meta);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'تعذّر تحميل سجل التدقيق');
    }
  }

  useEffect(() => { void load(1); }, []);

  return (
    <Card title="سجل التدقيق">
      <div style={{ display: 'flex', gap: 'var(--space-3)', flexWrap: 'wrap', alignItems: 'flex-end' }}>
        <Input label="نوع الكيان" value={entityType} onChange={(e) => setEntityType(e.target.value)} hint="مثال: user, site" />
        <Input label="معرّف المنفّذ (Actor ID)" value={actorId} onChange={(e) => setActorId(e.target.value)} />
        <Button variant="secondary" onClick={() => load(1)}>تصفية</Button>
      </div>

      {error && <ErrorState message={error} onRetry={() => load(meta.page)} />}
      {!error && entries === null && <LoadingState />}
      {!error && entries !== null && entries.length === 0 && <EmptyState title="لا توجد سجلات مطابقة" />}
      {!error && entries !== null && entries.length > 0 && (
        <>
          <Table<AuditLogEntry & { id: number }>
            rows={entries.map((e) => ({ ...e, id: e.seq }))}
            columns={[
              { header: '#', render: (e) => e.seq },
              { header: 'الحدث', render: (e) => <Badge tone="neutral">{e.action}</Badge> },
              { header: 'الكيان', render: (e) => `${e.entity_type} #${e.entity_id}` },
              { header: 'المنفّذ', render: (e) => e.actor_id },
              { header: 'الوقت (UTC)', render: (e) => new Date(e.occurred_at).toISOString() },
              { header: 'السبب', render: (e) => e.reason ?? '—', hideOnMobile: true },
            ]}
          />
          <Pagination meta={meta} onPageChange={(p) => load(p)} />
        </>
      )}
    </Card>
  );
}
