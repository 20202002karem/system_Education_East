import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Table } from '../../components/Table';
import { Badge } from '../../components/Badge';
import { Pagination } from '../../components/Pagination';
import { Select } from '../../components/Select';
import { Button } from '../../components/Button';
import { LoadingState, EmptyState, ErrorState } from '../../components/states';
import { ApiError } from '../../lib/apiClient';
import type { PageMeta, Role, User } from '../../lib/types';
import { usersApi } from './usersApi';
import { UserFormDialog } from './UserFormDialog';

const roleLabels: Record<Role, string> = {
  chairman: 'رئيس القسم',
  secretary: 'السكرتير',
  engineer: 'مهندس',
  technician: 'فني',
  school_manager: 'مدير مدرسة',
};

export function UsersListPage() {
  const [users, setUsers] = useState<User[] | null>(null);
  const [meta, setMeta] = useState<PageMeta>({ page: 1, per_page: 20, total: 0 });
  const [role, setRole] = useState('');
  const [status, setStatus] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [createOpen, setCreateOpen] = useState(false);

  async function load(page = 1) {
    setError(null);
    setUsers(null);
    try {
      const res = await usersApi.list({ role: role || undefined, status: status || undefined, page, per_page: 20 });
      setUsers(res.data);
      setMeta(res.meta);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'تعذّر تحميل المستخدمين');
    }
  }

  useEffect(() => {
    void load(1);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [role, status]);

  return (
    <Card
      title="المستخدمون"
      actions={<Button onClick={() => setCreateOpen(true)}>إنشاء حساب</Button>}
    >
      <div style={{ display: 'flex', gap: 'var(--space-4)', flexWrap: 'wrap' }}>
        <Select
          label="الدور"
          value={role}
          onChange={(e) => setRole(e.target.value)}
          options={[{ value: '', label: 'الكل' }, ...Object.entries(roleLabels).map(([v, l]) => ({ value: v, label: l }))]}
        />
        <Select
          label="الحالة"
          value={status}
          onChange={(e) => setStatus(e.target.value)}
          options={[{ value: '', label: 'الكل' }, { value: 'active', label: 'فعّال' }, { value: 'disabled', label: 'معطّل' }]}
        />
      </div>

      {error && <ErrorState message={error} onRetry={() => load(meta.page)} />}
      {!error && users === null && <LoadingState />}
      {!error && users !== null && users.length === 0 && <EmptyState title="لا يوجد مستخدمون" hint="جرّب تغيير الفلاتر أو إنشاء حساب جديد" />}
      {!error && users !== null && users.length > 0 && (
        <>
          <Table<User>
            rows={users}
            columns={[
              { header: 'الاسم', render: (u) => <Link to={`/users/${u.id}`}>{u.name}</Link> },
              { header: 'البريد الإلكتروني', render: (u) => u.email, hideOnMobile: true },
              { header: 'الدور', render: (u) => roleLabels[u.role] },
              { header: 'الحالة', render: (u) => <Badge tone={u.status === 'active' ? 'success' : 'neutral'}>{u.status === 'active' ? 'فعّال' : 'معطّل'}</Badge> },
            ]}
          />
          <Pagination meta={meta} onPageChange={(p) => load(p)} />
        </>
      )}

      <UserFormDialog
        open={createOpen}
        mode="create"
        onClose={() => setCreateOpen(false)}
        onSaved={() => {
          setCreateOpen(false);
          void load(1);
        }}
      />
    </Card>
  );
}
