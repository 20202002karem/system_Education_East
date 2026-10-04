import { useEffect, useState } from 'react';
import { Card } from '../../components/Card';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { Table } from '../../components/Table';
import { Badge } from '../../components/Badge';
import { LoadingState, EmptyState, ErrorState } from '../../components/states';
import { useSnackbar } from '../../components/Snackbar';
import { ApiError } from '../../lib/apiClient';
import type { ReferenceItem, ReferenceResource } from '../../lib/types';
import { settingsApi } from './settingsApi';

const tabs: { value: ReferenceResource; label: string }[] = [
  { value: 'device-categories', label: 'تصنيفات الأجهزة' },
  { value: 'request-types', label: 'أنواع الطلبات' },
  { value: 'task-types', label: 'أنواع المهام' },
  { value: 'intake-channels', label: 'قنوات الاستقبال' },
];

/**
 * Batch 2 §B12 / Batch 3 §2.4 — same pattern repeated for all four lists.
 * device-categories only has `name` (Appendices v1.0 binding version, no
 * is_active); task-types additionally has is_administrative/secretary_assignable;
 * the other two have is_active. Fields shown adapt per resource accordingly.
 */
export function ReferenceDataPage() {
  const { notify } = useSnackbar();
  const [resource, setResource] = useState<ReferenceResource>('device-categories');
  const [items, setItems] = useState<ReferenceItem[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [newName, setNewName] = useState('');
  const [creating, setCreating] = useState(false);

  async function load() {
    setError(null);
    setItems(null);
    try {
      setItems(await settingsApi.listReference(resource));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'تعذّر التحميل');
    }
  }

  useEffect(() => { void load(); }, [resource]);

  async function create() {
    setCreating(true);
    try {
      const payload: Partial<ReferenceItem> = { name: newName };
      if (resource === 'task-types') { payload.is_administrative = false; payload.secretary_assignable = false; }
      else if (resource !== 'device-categories') { payload.is_active = true; }
      await settingsApi.createReference(resource, payload);
      notify('تمت الإضافة', 'success');
      setNewName('');
      await load();
    } catch (e) {
      notify(e instanceof ApiError ? e.message : 'تعذّر الإضافة', 'error');
    } finally {
      setCreating(false);
    }
  }

  async function toggleActive(item: ReferenceItem) {
    try {
      await settingsApi.updateReference(resource, item.id, { is_active: !item.is_active });
      await load();
    } catch (e) {
      notify(e instanceof ApiError ? e.message : 'تعذّر التحديث', 'error');
    }
  }

  return (
    <Card title="القوائم المرجعية">
      <div style={{ display: 'flex', gap: 'var(--space-2)', marginBottom: 'var(--space-4)', flexWrap: 'wrap' }}>
        {tabs.map((t) => (
          <Button key={t.value} variant={resource === t.value ? 'primary' : 'secondary'} onClick={() => setResource(t.value)}>
            {t.label}
          </Button>
        ))}
      </div>

      <div style={{ display: 'flex', gap: 'var(--space-3)', alignItems: 'flex-end', marginBottom: 'var(--space-4)' }}>
        <div style={{ flex: 1 }}>
          <Input label="اسم جديد" value={newName} onChange={(e) => setNewName(e.target.value)} />
        </div>
        <Button onClick={create} loading={creating} disabled={!newName.trim()}>إضافة</Button>
      </div>

      {error && <ErrorState message={error} onRetry={load} />}
      {!error && items === null && <LoadingState />}
      {!error && items !== null && items.length === 0 && <EmptyState title="لا توجد عناصر بعد" />}
      {!error && items !== null && items.length > 0 && (
        <Table<ReferenceItem>
          rows={items}
          columns={[
            { header: 'الاسم', render: (i) => i.name },
            ...(resource !== 'device-categories' && resource !== 'task-types'
              ? [{ header: 'الحالة', render: (i: ReferenceItem) => (
                  <Badge tone={i.is_active ? 'success' : 'neutral'}>{i.is_active ? 'فعّال' : 'غير فعّال'}</Badge>
                ) }]
              : []),
            ...(resource === 'task-types'
              ? [
                  { header: 'إداري؟', render: (i: ReferenceItem) => (i.is_administrative ? 'نعم' : 'لا') },
                  { header: 'يُسند من السكرتير؟', render: (i: ReferenceItem) => (i.secretary_assignable ? 'نعم' : 'لا') },
                ]
              : []),
            ...(resource !== 'device-categories' && resource !== 'task-types'
              ? [{ header: '', render: (i: ReferenceItem) => <Button variant="secondary" onClick={() => toggleActive(i)}>{i.is_active ? 'تعطيل' : 'تفعيل'}</Button> }]
              : []),
          ]}
        />
      )}
    </Card>
  );
}
