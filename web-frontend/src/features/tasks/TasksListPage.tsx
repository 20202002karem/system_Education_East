import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Table } from '../../components/Table';
import { Badge } from '../../components/Badge';
import { Pagination } from '../../components/Pagination';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { Select } from '../../components/Select';
import { FormDialog } from '../../components/FormDialog';
import { LoadingState, EmptyState, ErrorState } from '../../components/states';
import { useSnackbar } from '../../components/Snackbar';
import { ApiError } from '../../lib/apiClient';
import { useAuth } from '../../auth/AuthContext';
import type { PageMeta, Task } from '../../lib/types';
import { tasksApi } from './tasksApi';
import { formatDateTime, requestStatusTone, taskStatusLabels } from '../requests/requestLabels';
import { useRequestLookups } from '../requests/useRequestLookups';

const statusOptions = Object.entries(taskStatusLabels).map(([value, label]) => ({ value, label }));

/** M3-UC-012 «مهامي» + task creation for chairman/secretary (M3-UC-009). */
export function TasksListPage() {
  const { user } = useAuth();
  const { notify } = useSnackbar();
  const lookups = useRequestLookups();
  const [rows, setRows] = useState<Task[] | null>(null);
  const [meta, setMeta] = useState<PageMeta>({ page: 1, per_page: 20, total: 0 });
  const [error, setError] = useState<string | null>(null);
  const [q, setQ] = useState('');
  const [status, setStatus] = useState('');
  const [mine, setMine] = useState(user?.role === 'engineer' || user?.role === 'technician');
  const [sort, setSort] = useState('-created_at');
  const [createOpen, setCreateOpen] = useState(false);
  const canCreate = user?.role === 'chairman' || user?.role === 'secretary';

  const load = useCallback(async (page = 1) => {
    setError(null); setRows(null);
    try {
      const r = await tasksApi.list({ page, per_page: 20, sort, q: q.trim() || undefined, status: (status || undefined) as never, assignee_id: mine ? user?.id : undefined });
      setRows(r.data); setMeta(r.meta);
    } catch (e) { setError(e instanceof ApiError ? e.message : 'تعذّر تحميل المهام'); }
  }, [q, status, mine, sort, user?.id]);
  useEffect(() => { void load(1); }, [status, mine, sort]); // eslint-disable-line react-hooks/exhaustive-deps

  const taskTypes = lookups.taskTypes.filter((t) => user?.role !== 'secretary' || t.is_administrative);

  return (
    <Card title={mine ? 'مهامي' : 'المهام'} actions={canCreate ? <Button onClick={() => setCreateOpen(true)}>مهمة جديدة</Button> : undefined}>
      <form onSubmit={(e) => { e.preventDefault(); void load(1); }} style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 'var(--space-3)', marginBottom: 'var(--space-4)' }}>
        <Input label="بحث (الرقم المرجعي)" value={q} onChange={(e) => setQ(e.target.value)} />
        <Select label="الحالة" value={status} onChange={(e) => setStatus(e.target.value)} options={[{ value: '', label: 'الكل' }, ...statusOptions]} />
        <Select label="الترتيب" value={sort} onChange={(e) => setSort(e.target.value)}
          options={[{ value: '-created_at', label: 'الأحدث' }, { value: 'due_at', label: 'الاستحقاق (الأقرب)' }, { value: '-due_at', label: 'الاستحقاق (الأبعد)' }]} />
        <label style={{ alignSelf: 'center' }}><input type="checkbox" checked={mine} onChange={(e) => setMine(e.target.checked)} /> مهامي فقط</label>
        <div style={{ alignSelf: 'end', marginBottom: 'var(--space-4)' }}><Button type="submit">بحث</Button></div>
      </form>
      {error && <ErrorState message={error} onRetry={() => load(meta.page)} />}
      {!error && rows === null && <LoadingState />}
      {!error && rows !== null && rows.length === 0 && <EmptyState title="لا توجد مهام" />}
      {!error && rows !== null && rows.length > 0 && (
        <>
          <Table<Task> rows={rows} columns={[
            { header: 'الرقم', render: (t) => <Link to={`/tasks/${t.id}`}>{t.ref_no}</Link> },
            { header: 'العنوان', render: (t) => t.title },
            { header: 'الحالة', render: (t) => <Badge tone={requestStatusTone(t.status)}>{taskStatusLabels[t.status]}</Badge> },
            { header: 'الاستحقاق', render: (t) => formatDateTime(t.due_at), hideOnMobile: true },
          ]} />
          <Pagination meta={meta} onPageChange={(p) => load(p)} />
        </>
      )}
      <FormDialog open={createOpen} title="مهمة جديدة" submitLabel="إنشاء" onClose={() => setCreateOpen(false)}
        fields={[
          { name: 'task_type_id', label: 'نوع المهمة', type: 'select', required: true, options: taskTypes.map((t) => ({ value: String(t.id), label: t.name })) },
          { name: 'title', label: 'العنوان', required: true, maxLength: 150 },
          { name: 'description', label: 'التفاصيل', type: 'textarea' },
          { name: 'site_id', label: 'الموقع (إلزامي للمهمة المستقلة)', type: 'select', options: lookups.sites.map((s) => ({ value: String(s.id), label: s.name_ar })) },
          { name: 'due_at', label: 'الاستحقاق', type: 'datetime-local' },
        ]}
        onSubmit={async (v) => {
          await tasksApi.create({
            task_type_id: Number(v.task_type_id), title: v.title, description: v.description || null,
            site_id: v.site_id ? Number(v.site_id) : null, due_at: v.due_at ? new Date(v.due_at).toISOString() : null,
          });
          notify('تم إنشاء المهمة', 'success'); setCreateOpen(false); await load(1);
        }} />
    </Card>
  );
}
