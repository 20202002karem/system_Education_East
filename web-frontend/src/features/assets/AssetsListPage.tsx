import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
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
import type { Asset, PageMeta } from '../../lib/types';
import { assetsApi } from './assetsApi';
import { manualStatusOptions, statusLabels, statusTone } from './assetLabels';
import { useAssetLookups } from './useAssetLookups';
import { AssetFormDialog } from './AssetFormDialog';

const sortOptions = [
  { value: 'inventory_no', label: 'رقم الجرد (تصاعدي)' },
  { value: '-inventory_no', label: 'رقم الجرد (تنازلي)' },
  { value: '-created_at', label: 'الأحدث' },
  { value: 'created_at', label: 'الأقدم' },
];

export function AssetsListPage() {
  const { user } = useAuth();
  const lookups = useAssetLookups();
  const [assets, setAssets] = useState<Asset[] | null>(null);
  const [meta, setMeta] = useState<PageMeta>({ page: 1, per_page: 20, total: 0 });
  const [error, setError] = useState<string | null>(null);
  const [createOpen, setCreateOpen] = useState(false);
  const [q, setQ] = useState('');
  const [status, setStatus] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [siteId, setSiteId] = useState('');
  const [sort, setSort] = useState('inventory_no');
  // UX only: the server decides (chairman or edit_assets grant); school managers never write.
  const showCreate = !!user && user.role !== 'school_manager';

  const load = useCallback(async (page = 1) => {
    setError(null);
    setAssets(null);
    try {
      const res = await assetsApi.list({
        page, per_page: 20, sort, q: q.trim() || undefined, status: status || undefined,
        category_id: categoryId ? Number(categoryId) : undefined, site_id: siteId ? Number(siteId) : undefined,
      });
      setAssets(res.data);
      setMeta(res.meta);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'تعذّر تحميل الأجهزة');
    }
  }, [q, status, categoryId, siteId, sort]);

  useEffect(() => { void load(1); }, [status, categoryId, siteId, sort]); // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <Card title="الأجهزة" actions={showCreate ? <Button onClick={() => setCreateOpen(true)}>إضافة جهاز</Button> : undefined}>
      <form
        onSubmit={(e) => { e.preventDefault(); void load(1); }}
        style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 'var(--space-3)', marginBottom: 'var(--space-4)' }}
      >
        <Input label="بحث (رقم الجرد / التسلسلي)" value={q} onChange={(e) => setQ(e.target.value)} />
        <Select label="الحالة" value={status} onChange={(e) => setStatus(e.target.value)}
          options={[{ value: '', label: 'الكل' }, ...manualStatusOptions]} />
        {lookups.available && (
          <>
            <Select label="التصنيف" value={categoryId} onChange={(e) => setCategoryId(e.target.value)}
              options={[{ value: '', label: 'الكل' }, ...lookups.categories.map((c) => ({ value: String(c.id), label: c.name }))]} />
            <Select label="الموقع" value={siteId} onChange={(e) => setSiteId(e.target.value)}
              options={[{ value: '', label: 'الكل' }, ...lookups.sites.map((s) => ({ value: String(s.id), label: s.name_ar }))]} />
          </>
        )}
        <Select label="الترتيب" value={sort} onChange={(e) => setSort(e.target.value)} options={sortOptions} />
        <div style={{ alignSelf: 'end', marginBottom: 'var(--space-4)' }}><Button type="submit">بحث</Button></div>
      </form>

      {error && <ErrorState message={error} onRetry={() => load(meta.page)} />}
      {!error && assets === null && <LoadingState />}
      {!error && assets !== null && assets.length === 0 && <EmptyState title="لا توجد أجهزة" />}
      {!error && assets !== null && assets.length > 0 && (
        <>
          <Table<Asset>
            rows={assets}
            columns={[
              { header: 'رقم الجرد', render: (a) => <Link to={`/assets/${a.id}`}>{a.inventory_no}</Link> },
              { header: 'الرقم التسلسلي', render: (a) => a.serial_no ?? '—', hideOnMobile: true },
              { header: 'التصنيف', render: (a) => lookups.categoryName(a.category_id) },
              { header: 'الموقع', render: (a) => lookups.siteName(a.current_site_id), hideOnMobile: true },
              { header: 'الحالة', render: (a) => <Badge tone={statusTone(a.status)}>{statusLabels[a.status]}</Badge> },
            ]}
          />
          <Pagination meta={meta} onPageChange={(p) => load(p)} />
        </>
      )}
      <AssetFormDialog open={createOpen} mode="create" lookups={lookups} onClose={() => setCreateOpen(false)}
        onSaved={() => { setCreateOpen(false); void load(1); }} />
    </Card>
  );
}
