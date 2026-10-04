import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { ConfirmDialog } from '../../components/ConfirmDialog';
import { LoadingState, ErrorState, EmptyState } from '../../components/states';
import { Table } from '../../components/Table';
import { useSnackbar } from '../../components/Snackbar';
import { ApiError } from '../../lib/apiClient';
import type { Site, SiteManager } from '../../lib/types';
import { organizationApi } from './organizationApi';
import { SiteFormDialog } from './SiteFormDialog';
import { AssignManagerDialog } from './AssignManagerDialog';

const typeLabels: Record<string, string> = { school: 'مدرسة', department: 'قسم', warehouse: 'Warehouse' };

export function SiteDetailPage() {
  const { id } = useParams<{ id: string }>();
  const siteId = Number(id);
  const navigate = useNavigate();
  const { notify } = useSnackbar();

  const [site, setSite] = useState<Site | null>(null);
  const [managers, setManagers] = useState<SiteManager[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [editOpen, setEditOpen] = useState(false);
  const [assignOpen, setAssignOpen] = useState(false);
  const [archiveConfirmOpen, setArchiveConfirmOpen] = useState(false);
  const [endConfirmId, setEndConfirmId] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);

  async function load() {
    setError(null);
    try {
      const [s, m] = await Promise.all([organizationApi.getSite(siteId), organizationApi.listManagers(siteId)]);
      setSite(s);
      setManagers(m);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'تعذّر تحميل بيانات الموقع');
    }
  }

  useEffect(() => { void load(); }, [siteId]);

  async function handleArchive() {
    setBusy(true);
    try {
      await organizationApi.archiveSite(siteId);
      notify('تم أرشفة الموقع', 'success');
      await load();
    } catch (e) {
      notify(e instanceof ApiError ? e.message : 'تعذّر الأرشفة', 'error');
    } finally {
      setBusy(false);
      setArchiveConfirmOpen(false);
    }
  }

  async function handleEndManager() {
    if (!endConfirmId) return;
    setBusy(true);
    try {
      await organizationApi.endManager(endConfirmId);
      notify('تم إنهاء فترة تولي المسؤول', 'success');
      const m = await organizationApi.listManagers(siteId);
      setManagers(m);
    } catch (e) {
      notify(e instanceof ApiError ? e.message : 'تعذّر الإنهاء', 'error');
    } finally {
      setBusy(false);
      setEndConfirmId(null);
    }
  }

  if (error) return <ErrorState message={error} onRetry={load} />;
  if (!site) return <LoadingState />;

  return (
    <div>
      <Button variant="ghost" onClick={() => navigate('/sites')}>← رجوع للمواقع</Button>

      <Card
        title={site.name_ar}
        actions={
          <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
            <Button variant="secondary" onClick={() => setEditOpen(true)}>تعديل</Button>
            {site.status === 'active' && <Button variant="danger" onClick={() => setArchiveConfirmOpen(true)}>أرشفة</Button>}
          </div>
        }
      >
        <dl style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(160px, 1fr))', gap: 'var(--space-3)' }}>
          <div><dt style={{ color: 'var(--color-text-muted)', fontSize: 'var(--font-size-sm)' }}>الرمز</dt><dd style={{ margin: 0 }}>{site.code}</dd></div>
          <div><dt style={{ color: 'var(--color-text-muted)', fontSize: 'var(--font-size-sm)' }}>النوع</dt><dd style={{ margin: 0 }}>{typeLabels[site.type]}</dd></div>
          <div><dt style={{ color: 'var(--color-text-muted)', fontSize: 'var(--font-size-sm)' }}>الحالة</dt><dd style={{ margin: 0 }}><Badge tone={site.status === 'active' ? 'success' : 'neutral'}>{site.status === 'active' ? 'فعّال' : 'مؤرشف'}</Badge></dd></div>
        </dl>
      </Card>

      <Card title="مسؤولو الموقع" actions={<Button variant="secondary" onClick={() => setAssignOpen(true)}>تعيين مسؤول</Button>}>
        {managers === null && <LoadingState />}
        {managers !== null && managers.length === 0 && <EmptyState title="لا يوجد سجل لمسؤولي الموقع" />}
        {managers !== null && managers.length > 0 && (
          <Table<SiteManager>
            rows={managers}
            columns={[
              { header: 'المستخدم (User ID)', render: (m) => m.user_id },
              { header: 'من', render: (m) => m.from },
              { header: 'إلى', render: (m) => m.to ?? <Badge tone="success">ساري حاليًا</Badge> },
              { header: '', render: (m) => (!m.to ? <Button variant="danger" onClick={() => setEndConfirmId(m.id)}>إنهاء</Button> : null) },
            ]}
          />
        )}
      </Card>

      <SiteFormDialog open={editOpen} mode="edit" site={site} onClose={() => setEditOpen(false)} onSaved={() => { setEditOpen(false); void load(); }} />
      <AssignManagerDialog open={assignOpen} siteId={siteId} onClose={() => setAssignOpen(false)} onSaved={() => { setAssignOpen(false); void load(); }} />

      <ConfirmDialog
        open={archiveConfirmOpen}
        title="أرشفة الموقع؟"
        description="لا يوجد حذف فعلي — الأرشفة فقط، ويمكن مراجعة السجل لاحقًا."
        variant="danger"
        loading={busy}
        onConfirm={handleArchive}
        onCancel={() => setArchiveConfirmOpen(false)}
      />
      <ConfirmDialog
        open={endConfirmId !== null}
        title="إنهاء فترة تولي المسؤول؟"
        variant="danger"
        loading={busy}
        onConfirm={handleEndManager}
        onCancel={() => setEndConfirmId(null)}
      />
    </div>
  );
}
