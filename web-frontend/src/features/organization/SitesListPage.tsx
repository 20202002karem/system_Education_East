import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Table } from '../../components/Table';
import { Badge } from '../../components/Badge';
import { Pagination } from '../../components/Pagination';
import { Button } from '../../components/Button';
import { LoadingState, EmptyState, ErrorState } from '../../components/states';
import { ApiError } from '../../lib/apiClient';
import type { PageMeta, Site } from '../../lib/types';
import { organizationApi } from './organizationApi';
import { SiteFormDialog } from './SiteFormDialog';

const typeLabels: Record<string, string> = { school: 'مدرسة', department: 'قسم', warehouse: 'Warehouse' };

export function SitesListPage() {
  const [sites, setSites] = useState<Site[] | null>(null);
  const [meta, setMeta] = useState<PageMeta>({ page: 1, per_page: 20, total: 0 });
  const [error, setError] = useState<string | null>(null);
  const [createOpen, setCreateOpen] = useState(false);

  async function load(page = 1) {
    setError(null);
    setSites(null);
    try {
      const res = await organizationApi.listSites({ page, per_page: 20 });
      setSites(res.data);
      setMeta(res.meta);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'تعذّر تحميل المواقع');
    }
  }

  useEffect(() => { void load(1); }, []);

  return (
    <Card title="المواقع" actions={<Button onClick={() => setCreateOpen(true)}>إنشاء موقع</Button>}>
      {error && <ErrorState message={error} onRetry={() => load(meta.page)} />}
      {!error && sites === null && <LoadingState />}
      {!error && sites !== null && sites.length === 0 && <EmptyState title="لا توجد مواقع" />}
      {!error && sites !== null && sites.length > 0 && (
        <>
          <Table<Site>
            rows={sites}
            columns={[
              { header: 'الرمز', render: (s) => <Link to={`/sites/${s.id}`}>{s.code}</Link> },
              { header: 'الاسم', render: (s) => s.name_ar },
              { header: 'النوع', render: (s) => typeLabels[s.type] ?? s.type },
              { header: 'الحالة', render: (s) => <Badge tone={s.status === 'active' ? 'success' : 'neutral'}>{s.status === 'active' ? 'فعّال' : 'مؤرشف'}</Badge> },
            ]}
          />
          <Pagination meta={meta} onPageChange={(p) => load(p)} />
        </>
      )}
      <SiteFormDialog open={createOpen} mode="create" onClose={() => setCreateOpen(false)} onSaved={() => { setCreateOpen(false); void load(1); }} />
    </Card>
  );
}
