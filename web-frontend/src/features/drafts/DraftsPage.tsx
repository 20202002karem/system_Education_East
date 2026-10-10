import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Table } from '../../components/Table';
import { Button } from '../../components/Button';
import { ConfirmDialog } from '../../components/ConfirmDialog';
import { LoadingState, EmptyState, ErrorState } from '../../components/states';
import { useSnackbar } from '../../components/Snackbar';
import { ApiError } from '../../lib/apiClient';
import type { Draft, DraftFormType } from '../../lib/types';
import { draftsApi } from './draftsApi';
import { formatDateTime } from '../requests/requestLabels';

const typeLabels: Record<DraftFormType, string> = {
  request_create: 'طلب جديد', note: 'ملاحظة', closure: 'إغلاق', cancellation: 'إلغاء', proposal: 'اقتراح',
};

/** MD-15ب/16: the user's own drafts; manual delete is the only physical delete in the system. */
export function DraftsPage() {
  const { notify } = useSnackbar();
  const [rows, setRows] = useState<Draft[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [del, setDel] = useState<Draft | null>(null);

  const load = useCallback(async () => {
    setError(null);
    try { setRows((await draftsApi.list({ per_page: 100 })).data); }
    catch (e) { setError(e instanceof ApiError ? e.message : 'تعذّر تحميل المسودات'); }
  }, []);
  useEffect(() => { void load(); }, [load]);

  return (
    <Card title="مسوداتي">
      {error && <ErrorState message={error} onRetry={load} />}
      {!error && rows === null && <LoadingState />}
      {!error && rows !== null && rows.length === 0 && <EmptyState title="لا توجد مسودات" />}
      {!error && rows !== null && rows.length > 0 && (
        <Table<Draft> rows={rows} columns={[
          { header: 'النوع', render: (d) => typeLabels[d.form_type] },
          { header: 'آخر حفظ', render: (d) => formatDateTime(d.updated_at) },
          { header: '', render: (d) => (
            <span style={{ display: 'flex', gap: 'var(--space-2)' }}>
              {d.form_type === 'request_create' && <Link to="/requests/new">متابعة</Link>}
              <Button variant="danger" onClick={() => setDel(d)}>حذف</Button>
            </span>
          ) },
        ]} />
      )}
      <ConfirmDialog open={!!del} title="حذف المسودة" description="الحذف نهائي ولا يمكن استرجاعها." variant="danger" confirmLabel="حذف"
        onCancel={() => setDel(null)}
        onConfirm={async () => { try { await draftsApi.remove(del!.id); notify('حُذفت المسودة', 'success'); setDel(null); await load(); } catch { notify('تعذّر الحذف', 'error'); } }} />
    </Card>
  );
}
