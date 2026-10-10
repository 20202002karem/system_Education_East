import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Table } from '../../components/Table';
import { Badge } from '../../components/Badge';
import { Pagination } from '../../components/Pagination';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { Select } from '../../components/Select';
import { LoadingState, EmptyState, ErrorState } from '../../components/states';
import { ApiError } from '../../lib/apiClient';
import { useAuth } from '../../auth/AuthContext';
import type { PageMeta, ServiceRequest } from '../../lib/types';
import { requestsApi } from './requestsApi';
import { priorityLabels, priorityOptions, requestStatusLabels, requestStatusTone, formatDateTime } from './requestLabels';
import { useRequestLookups } from './useRequestLookups';

const statusOptions = Object.entries(requestStatusLabels).map(([value, label]) => ({ value, label }));
const sortOptions = [
  { value: '-created_at', label: 'الأحدث' }, { value: 'created_at', label: 'الأقدم' },
  { value: 'priority', label: 'الأولوية (الأعلى أولاً)' }, { value: '-priority', label: 'الأولوية (الأدنى أولاً)' },
];

/** M3-UC-010 / triage board. Scope is enforced by the server; this only renders what it returns. */
export function RequestsListPage() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const lookups = useRequestLookups();
  const [rows, setRows] = useState<ServiceRequest[] | null>(null);
  const [meta, setMeta] = useState<PageMeta>({ page: 1, per_page: 20, total: 0 });
  const [error, setError] = useState<string | null>(null);
  const [q, setQ] = useState('');
  const [status, setStatus] = useState('');
  const [priority, setPriority] = useState('');
  const [sort, setSort] = useState('-created_at');
  const canCreate = user?.role === 'school_manager' || user?.role === 'secretary';

  const load = useCallback(async (page = 1) => {
    setError(null); setRows(null);
    try {
      const res = await requestsApi.list({
        page, per_page: 20, sort, q: q.trim() || undefined,
        status: (status || undefined) as never, priority: (priority || undefined) as never,
      });
      setRows(res.data); setMeta(res.meta);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'تعذّر تحميل الطلبات');
    }
  }, [q, status, priority, sort]);

  useEffect(() => { void load(1); }, [status, priority, sort]); // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <Card title="طلبات الدعم" actions={canCreate ? <Button onClick={() => navigate('/requests/new')}>طلب جديد</Button> : undefined}>
      <form onSubmit={(e) => { e.preventDefault(); void load(1); }}
        style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 'var(--space-3)', marginBottom: 'var(--space-4)' }}>
        <Input label="بحث (الرقم المرجعي / الوصف)" value={q} onChange={(e) => setQ(e.target.value)} />
        <Select label="الحالة" value={status} onChange={(e) => setStatus(e.target.value)} options={[{ value: '', label: 'الكل' }, ...statusOptions]} />
        <Select label="الأولوية" value={priority} onChange={(e) => setPriority(e.target.value)} options={[{ value: '', label: 'الكل' }, ...priorityOptions]} />
        <Select label="الترتيب" value={sort} onChange={(e) => setSort(e.target.value)} options={sortOptions} />
        <div style={{ alignSelf: 'end', marginBottom: 'var(--space-4)' }}><Button type="submit">بحث</Button></div>
      </form>

      {error && <ErrorState message={error} onRetry={() => load(meta.page)} />}
      {!error && rows === null && <LoadingState />}
      {!error && rows !== null && rows.length === 0 && <EmptyState title="لا طلبات مفتوحة ضمن نطاقك" />}
      {!error && rows !== null && rows.length > 0 && (
        <>
          <Table<ServiceRequest>
            rows={rows}
            columns={[
              { header: 'الرقم المرجعي', render: (r) => <Link to={`/requests/${r.id}`}>{r.ref_no}</Link> },
              { header: 'الموقع', render: (r) => r.origin_site_name ?? lookups.siteName(r.origin_site_id), hideOnMobile: true },
              { header: 'الوصف', render: (r) => (r.description.length > 60 ? `${r.description.slice(0, 60)}…` : r.description) },
              { header: 'الأولوية', render: (r) => (r.priority ? priorityLabels[r.priority] : '—'), hideOnMobile: true },
              { header: 'الحالة', render: (r) => <Badge tone={requestStatusTone(r.status)}>{r.status ? requestStatusLabels[r.status] : '—'}</Badge> },
              { header: 'التاريخ', render: (r) => formatDateTime(r.created_at), hideOnMobile: true },
            ]}
          />
          <Pagination meta={meta} onPageChange={(p) => load(p)} />
        </>
      )}
    </Card>
  );
}
