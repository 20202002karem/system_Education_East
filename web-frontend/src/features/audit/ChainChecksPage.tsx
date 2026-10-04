import { useEffect, useState } from 'react';
import { Card } from '../../components/Card';
import { Table } from '../../components/Table';
import { Badge } from '../../components/Badge';
import { LoadingState, EmptyState, ErrorState } from '../../components/states';
import { ApiError } from '../../lib/apiClient';
import type { AuditChainCheck } from '../../lib/types';
import { auditApi } from './auditApi';

/** M1 criterion 5 — results of the daily hash-chain verification job. */
export function ChainChecksPage() {
  const [checks, setChecks] = useState<AuditChainCheck[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function load() {
    setError(null);
    try {
      const res = await auditApi.listChainChecks({ page: 1, per_page: 20 });
      setChecks(res.data);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'تعذّر التحميل');
    }
  }

  useEffect(() => { void load(); }, []);

  if (error) return <ErrorState message={error} onRetry={load} />;
  if (!checks) return <LoadingState />;
  if (checks.length === 0) return <EmptyState title="لا توجد نتائج فحص بعد" hint="يعمل الفحص يوميًا الساعة 02:00 UTC" />;

  return (
    <Card title="فحوصات سلسلة التدقيق">
      <Table<AuditChainCheck>
        rows={checks}
        columns={[
          { header: 'وقت التشغيل', render: (c) => new Date(c.run_at).toISOString() },
          { header: 'من', render: (c) => c.from_seq },
          { header: 'إلى', render: (c) => c.to_seq },
          { header: 'النتيجة', render: (c) => <Badge tone={c.result === 'ok' ? 'success' : 'danger'}>{c.result === 'ok' ? 'سليمة' : 'مكسورة'}</Badge> },
        ]}
      />
    </Card>
  );
}
