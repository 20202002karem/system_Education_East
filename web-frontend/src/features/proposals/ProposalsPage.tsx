import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Table } from '../../components/Table';
import { Button } from '../../components/Button';
import { Pagination } from '../../components/Pagination';
import { FormDialog } from '../../components/FormDialog';
import { LoadingState, EmptyState, ErrorState } from '../../components/states';
import { useSnackbar } from '../../components/Snackbar';
import { ApiError } from '../../lib/apiClient';
import { useAuth } from '../../auth/AuthContext';
import type { PageMeta, Proposal } from '../../lib/types';
import { proposalsApi } from './proposalsApi';
import { formatDateTime, roleLabels } from '../requests/requestLabels';
import { useRequestLookups } from '../requests/useRequestLookups';

/** M3-UC-008 — pending proposals; accept delegates to the real assignment/cancellation on the server. */
export function ProposalsPage() {
  const { user } = useAuth();
  const { notify } = useSnackbar();
  const lookups = useRequestLookups(true);
  const [rows, setRows] = useState<Proposal[] | null>(null);
  const [meta, setMeta] = useState<PageMeta>({ page: 1, per_page: 20, total: 0 });
  const [error, setError] = useState<string | null>(null);
  const [accepting, setAccepting] = useState<Proposal | null>(null);
  const [rejecting, setRejecting] = useState<Proposal | null>(null);

  const load = useCallback(async (page = 1) => {
    setError(null); setRows(null);
    try { const r = await proposalsApi.list({ page, per_page: 20 }); setRows(r.data); setMeta(r.meta); }
    catch (e) { setError(e instanceof ApiError ? e.message : 'تعذّر تحميل الاقتراحات'); }
  }, []);
  useEffect(() => { void load(1); }, [load]);

  const allowed = user?.role === 'chairman' ? ['chairman', 'engineer', 'technician', 'secretary'] : ['engineer', 'technician'];
  const assignees = lookups.users.filter((u) => allowed.includes(u.role));

  return (
    <Card title="الاقتراحات المعلّقة">
      {error && <ErrorState message={error} onRetry={() => load(meta.page)} />}
      {!error && rows === null && <LoadingState />}
      {!error && rows !== null && rows.length === 0 && <EmptyState title="لا اقتراحات معلّقة" />}
      {!error && rows !== null && rows.length > 0 && (
        <>
          <Table<Proposal> rows={rows} columns={[
            { header: 'الهدف', render: (p) => <Link to={`/${p.subject_type === 'request' ? 'requests' : 'tasks'}/${p.subject_id}`}>{p.subject_type === 'request' ? 'طلب' : 'مهمة'} #{p.subject_id}</Link> },
            { header: 'النوع', render: (p) => (p.type === 'reassign' ? 'إعادة إسناد' : 'إلغاء') },
            { header: 'السبب', render: (p) => p.reason },
            { header: 'المقترِح', render: (p) => lookups.userName(p.proposer_id), hideOnMobile: true },
            { header: 'التاريخ', render: (p) => formatDateTime(p.created_at), hideOnMobile: true },
            { header: 'قرار', render: (p) => (
              <span style={{ display: 'flex', gap: 'var(--space-2)' }}>
                <Button onClick={() => setAccepting(p)}>قبول</Button>
                <Button variant="secondary" onClick={() => setRejecting(p)}>رفض</Button>
              </span>
            ) },
          ]} />
          <Pagination meta={meta} onPageChange={(p) => load(p)} />
        </>
      )}
      <FormDialog open={!!accepting} title="قبول الاقتراح" submitLabel="قبول" onClose={() => setAccepting(null)}
        fields={accepting?.type === 'reassign'
          ? [{ name: 'new_assignee_id', label: 'المكلّف الجديد', type: 'select', required: true, options: assignees.map((u) => ({ value: String(u.id), label: `${u.name} — ${roleLabels[u.role]}` })) }]
          : []}
        description={accepting?.type === 'cancel' ? 'سيتم إلغاء الكيان فعلياً وفق صلاحياتك.' : undefined}
        onSubmit={async (v) => {
          await proposalsApi.accept(accepting!.id, { new_assignee_id: v.new_assignee_id ? Number(v.new_assignee_id) : null });
          notify('تم قبول الاقتراح', 'success'); setAccepting(null); await load(1);
        }} />
      <FormDialog open={!!rejecting} title="رفض الاقتراح" submitLabel="رفض" danger onClose={() => setRejecting(null)}
        fields={[{ name: 'decision_reason', label: 'سبب الرفض (اختياري)', type: 'textarea' }]}
        onSubmit={async (v) => { await proposalsApi.reject(rejecting!.id, v.decision_reason || null); notify('تم رفض الاقتراح', 'success'); setRejecting(null); await load(1); }} />
    </Card>
  );
}
