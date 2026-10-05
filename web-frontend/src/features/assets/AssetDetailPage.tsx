import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { Table } from '../../components/Table';
import { Pagination } from '../../components/Pagination';
import { LoadingState, EmptyState, ErrorState } from '../../components/states';
import { ApiError } from '../../lib/apiClient';
import { useAuth } from '../../auth/AuthContext';
import type { Asset, AssetCorrection, AssetLegacyNumber, AssetStatusEntry, PageMeta } from '../../lib/types';
import { assetsApi } from './assetsApi';
import { formatDateTime, statusLabels, statusTone } from './assetLabels';
import { useAssetLookups } from './useAssetLookups';
import { AssetFormDialog } from './AssetFormDialog';
import { StatusChangeDialog } from './StatusChangeDialog';
import { IdentifierCorrectionDialog } from './IdentifierCorrectionDialog';

const fieldLabels = { inventory_no: 'رقم الجرد', serial_no: 'الرقم التسلسلي' };
const emptyMeta: PageMeta = { page: 1, per_page: 20, total: 0 };

function HistoryCard<T extends { id: number }>({ title, load, columns }: {
  title: string;
  load: (page: number) => Promise<{ data: T[]; meta: PageMeta }>;
  columns: { header: string; render: (r: T) => React.ReactNode }[];
}) {
  const [rows, setRows] = useState<T[] | null>(null);
  const [meta, setMeta] = useState<PageMeta>(emptyMeta);
  const [error, setError] = useState<string | null>(null);
  const fetchPage = useCallback(async (p: number) => {
    setError(null);
    try { const r = await load(p); setRows(r.data); setMeta(r.meta); }
    catch (e) { setError(e instanceof ApiError ? e.message : 'تعذّر التحميل'); }
  }, [load]);
  useEffect(() => { void fetchPage(1); }, [fetchPage]);
  return (
    <Card title={title}>
      {error && <ErrorState message={error} onRetry={() => fetchPage(meta.page)} />}
      {!error && rows === null && <LoadingState />}
      {!error && rows !== null && rows.length === 0 && <EmptyState title="لا توجد سجلات" />}
      {!error && rows !== null && rows.length > 0 && (
        <>
          <Table<T> rows={rows} columns={columns} />
          <Pagination meta={meta} onPageChange={(p) => fetchPage(p)} />
        </>
      )}
    </Card>
  );
}

export function AssetDetailPage() {
  const { id } = useParams();
  const assetId = Number(id);
  const { user } = useAuth();
  const lookups = useAssetLookups();
  const [asset, setAsset] = useState<Asset | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [dialog, setDialog] = useState<'edit' | 'status' | 'correct' | null>(null);
  const [rev, setRev] = useState(0); // bumps to reload history cards

  const load = useCallback(async () => {
    setError(null);
    try { setAsset(await assetsApi.get(assetId)); }
    catch (e) { setError(e instanceof ApiError ? (e.status === 404 ? 'الجهاز غير موجود' : e.message) : 'تعذّر تحميل الجهاز'); }
  }, [assetId]);
  useEffect(() => { void load(); }, [load]);

  const refresh = () => { setDialog(null); setRev((r) => r + 1); void load(); };
  const canWrite = !!user && user.role !== 'school_manager'; // UX only; server enforces
  const isChairman = user?.role === 'chairman';

  const statusLoader = useCallback((p: number) => assetsApi.statusHistory(assetId, p), [assetId, rev]); // eslint-disable-line react-hooks/exhaustive-deps
  const corrLoader = useCallback((p: number) => assetsApi.corrections(assetId, p), [assetId, rev]); // eslint-disable-line react-hooks/exhaustive-deps
  const legacyLoader = useCallback((p: number) => assetsApi.legacyNumbers(assetId, p), [assetId, rev]); // eslint-disable-line react-hooks/exhaustive-deps

  if (error) return <><Link to="/assets">← الأجهزة</Link><ErrorState message={error} onRetry={load} /></>;
  if (!asset) return <LoadingState />;

  return (
    <>
      <p><Link to="/assets">← الأجهزة</Link></p>
      <Card
        title={`جهاز ${asset.inventory_no}`}
        actions={
          <div style={{ display: 'flex', gap: 'var(--space-2)', flexWrap: 'wrap' }}>
            {canWrite && <Button variant="secondary" onClick={() => setDialog('edit')}>تعديل</Button>}
            {canWrite && <Button variant="secondary" onClick={() => setDialog('status')}>تحديث الحالة</Button>}
            {isChairman && <Button variant="danger" onClick={() => setDialog('correct')}>تصحيح الأرقام</Button>}
          </div>
        }
      >
        <dl style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: 'var(--space-3)', margin: 0 }}>
          <div><dt>رقم الجرد</dt><dd>{asset.inventory_no}</dd></div>
          <div><dt>الرقم التسلسلي</dt><dd>{asset.serial_no ?? '—'}</dd></div>
          <div><dt>التصنيف</dt><dd>{lookups.categoryName(asset.category_id)}</dd></div>
          <div><dt>الموقع الحالي</dt><dd>{lookups.siteName(asset.current_site_id)}</dd></div>
          <div><dt>الحالة</dt><dd><Badge tone={statusTone(asset.status)}>{statusLabels[asset.status]}</Badge></dd></div>
          <div><dt>الحائز</dt><dd>{asset.holder_text ?? '—'}</dd></div>
          <div><dt>آخر تحديث</dt><dd>{formatDateTime(asset.updated_at)}</dd></div>
        </dl>
      </Card>

      <HistoryCard<AssetStatusEntry> title="سجل الحالات" load={statusLoader} columns={[
        { header: 'من', render: (r) => statusLabels[r.from_status] },
        { header: 'إلى', render: (r) => statusLabels[r.to_status] },
        { header: 'السبب', render: (r) => r.reason ?? '—' },
        { header: 'التاريخ', render: (r) => formatDateTime(r.changed_at) },
      ]} />
      <HistoryCard<AssetCorrection> title="سجل تصحيح الأرقام" load={corrLoader} columns={[
        { header: 'الحقل', render: (r) => fieldLabels[r.field_name] },
        { header: 'القيمة القديمة', render: (r) => r.old_value },
        { header: 'القيمة الجديدة', render: (r) => r.new_value },
        { header: 'السبب', render: (r) => r.reason },
        { header: 'التاريخ', render: (r) => formatDateTime(r.corrected_at) },
      ]} />
      <HistoryCard<AssetLegacyNumber> title="أرقام الجرد القديمة" load={legacyLoader} columns={[
        { header: 'الرقم القديم', render: (r) => r.legacy_number },
        { header: 'المصدر', render: (r) => r.source ?? '—' },
        { header: 'تاريخ الإضافة', render: (r) => formatDateTime(r.added_at) },
      ]} />

      <AssetFormDialog open={dialog === 'edit'} mode="edit" asset={asset} lookups={lookups} onClose={() => setDialog(null)} onSaved={refresh} />
      <StatusChangeDialog open={dialog === 'status'} asset={asset} onClose={() => setDialog(null)} onSaved={refresh} />
      <IdentifierCorrectionDialog open={dialog === 'correct'} asset={asset} onClose={() => setDialog(null)} onSaved={refresh} />
    </>
  );
}
