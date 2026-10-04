import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { ConfirmDialog } from '../../components/ConfirmDialog';
import { LoadingState, ErrorState, EmptyState } from '../../components/states';
import { Table } from '../../components/Table';
import { Select } from '../../components/Select';
import { Input } from '../../components/Input';
import { useSnackbar } from '../../components/Snackbar';
import { ApiError } from '../../lib/apiClient';
import type { PermissionGrant, PermissionKey, User } from '../../lib/types';
import { usersApi } from './usersApi';
import { UserFormDialog } from './UserFormDialog';

const permissionLabels: Record<PermissionKey, string> = {
  initiate_transfer: 'بدء نقل',
  initiate_decommission: 'بدء إخراج',
  edit_assets: 'تعديل أجهزة',
};

type ConfirmAction = 'disable' | 'enable' | 'terminate-sessions' | null;

export function UserDetailPage() {
  const { id } = useParams<{ id: string }>();
  const userId = Number(id);
  const navigate = useNavigate();
  const { notify } = useSnackbar();

  const [user, setUser] = useState<User | null>(null);
  const [grants, setGrants] = useState<PermissionGrant[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [editOpen, setEditOpen] = useState(false);
  const [confirmAction, setConfirmAction] = useState<ConfirmAction>(null);
  const [busy, setBusy] = useState(false);

  const [grantKey, setGrantKey] = useState<PermissionKey>('initiate_transfer');
  const [grantReason, setGrantReason] = useState('');
  const [resetPassword, setResetPassword] = useState('');
  const [resetOpen, setResetOpen] = useState(false);

  async function load() {
    setError(null);
    try {
      const [u, g] = await Promise.all([usersApi.get(userId), usersApi.permissionGrants(userId)]);
      setUser(u);
      setGrants(g);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'تعذّر تحميل بيانات المستخدم');
    }
  }

  useEffect(() => {
    void load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [userId]);

  async function runConfirmed() {
    if (!confirmAction) return;
    setBusy(true);
    try {
      if (confirmAction === 'disable') {
        await usersApi.disable(userId);
        notify('تم تعطيل الحساب', 'success');
      } else if (confirmAction === 'enable') {
        await usersApi.enable(userId);
        notify('تم تفعيل الحساب', 'success');
      } else if (confirmAction === 'terminate-sessions') {
        await usersApi.terminateSessions(userId);
        notify('تم إنهاء جميع جلسات المستخدم', 'success');
      }
      await load();
    } catch (e) {
      notify(e instanceof ApiError ? e.message : 'تعذّر تنفيذ الإجراء', 'error');
    } finally {
      setBusy(false);
      setConfirmAction(null);
    }
  }

  async function handleGrant() {
    setBusy(true);
    try {
      await usersApi.grantPermission(userId, { permission_key: grantKey, reason: grantReason || undefined });
      notify('تم منح الصلاحية', 'success');
      setGrantReason('');
      const g = await usersApi.permissionGrants(userId);
      setGrants(g);
    } catch (e) {
      notify(e instanceof ApiError ? e.message : 'تعذّر منح الصلاحية', 'error');
    } finally {
      setBusy(false);
    }
  }

  async function handleRevoke(grantId: number) {
    setBusy(true);
    try {
      await usersApi.revokePermission(grantId);
      notify('تم سحب الصلاحية', 'success');
      const g = await usersApi.permissionGrants(userId);
      setGrants(g);
    } catch (e) {
      notify(e instanceof ApiError ? e.message : 'تعذّر سحب الصلاحية', 'error');
    } finally {
      setBusy(false);
    }
  }

  async function handleResetPassword() {
    setBusy(true);
    try {
      await usersApi.resetPassword(userId, resetPassword);
      notify('تم إعادة تعيين كلمة المرور', 'success');
      setResetOpen(false);
      setResetPassword('');
    } catch (e) {
      notify(e instanceof ApiError ? e.message : 'تعذّر إعادة تعيين كلمة المرور', 'error');
    } finally {
      setBusy(false);
    }
  }

  if (error) return <ErrorState message={error} onRetry={load} />;
  if (!user) return <LoadingState />;

  return (
    <div>
      <Button variant="ghost" onClick={() => navigate('/users')}>← رجوع للمستخدمين</Button>

      <Card
        title={user.name}
        actions={
          <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
            <Button variant="secondary" onClick={() => setEditOpen(true)}>تعديل</Button>
            {user.status === 'active' ? (
              <Button variant="danger" onClick={() => setConfirmAction('disable')}>تعطيل</Button>
            ) : (
              <Button onClick={() => setConfirmAction('enable')}>تفعيل</Button>
            )}
          </div>
        }
      >
        <dl style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 'var(--space-3)' }}>
          <div><dt style={{ color: 'var(--color-text-muted)', fontSize: 'var(--font-size-sm)' }}>البريد الإلكتروني</dt><dd style={{ margin: 0 }}>{user.email}</dd></div>
          <div><dt style={{ color: 'var(--color-text-muted)', fontSize: 'var(--font-size-sm)' }}>الحالة</dt><dd style={{ margin: 0 }}><Badge tone={user.status === 'active' ? 'success' : 'neutral'}>{user.status === 'active' ? 'فعّال' : 'معطّل'}</Badge></dd></div>
          <div><dt style={{ color: 'var(--color-text-muted)', fontSize: 'var(--font-size-sm)' }}>نطاق العرض</dt><dd style={{ margin: 0 }}>{user.view_scope}</dd></div>
        </dl>
      </Card>

      <Card title="الجلسات">
        <p style={{ color: 'var(--color-text-muted)' }}>إنهاء كل جلسات هذا المستخدم فورًا (مثال: فقدان الجهاز).</p>
        <Button variant="danger" onClick={() => setConfirmAction('terminate-sessions')}>إنهاء كل الجلسات</Button>
      </Card>

      <Card title="إعادة تعيين كلمة المرور">
        {!resetOpen ? (
          <Button variant="secondary" onClick={() => setResetOpen(true)}>إعادة تعيين كلمة المرور</Button>
        ) : (
          <div style={{ display: 'flex', gap: 'var(--space-3)', alignItems: 'flex-end', flexWrap: 'wrap' }}>
            <Input label="كلمة المرور الجديدة" type="password" value={resetPassword} onChange={(e) => setResetPassword(e.target.value)} />
            <Button onClick={handleResetPassword} loading={busy} disabled={resetPassword.length < 5}>تأكيد</Button>
            <Button variant="secondary" onClick={() => setResetOpen(false)}>إلغاء</Button>
          </div>
        )}
      </Card>

      <Card title="الصلاحيات الفردية">
        <div style={{ display: 'flex', gap: 'var(--space-3)', alignItems: 'flex-end', flexWrap: 'wrap', marginBottom: 'var(--space-4)' }}>
          <Select
            label="الصلاحية"
            value={grantKey}
            onChange={(e) => setGrantKey(e.target.value as PermissionKey)}
            options={Object.entries(permissionLabels).map(([v, l]) => ({ value: v, label: l }))}
          />
          <Input label="السبب (اختياري)" value={grantReason} onChange={(e) => setGrantReason(e.target.value)} />
          <Button onClick={handleGrant} loading={busy}>منح</Button>
        </div>

        {grants === null && <LoadingState />}
        {grants !== null && grants.length === 0 && <EmptyState title="لا توجد صلاحيات فردية" />}
        {grants !== null && grants.length > 0 && (
          <Table<PermissionGrant>
            rows={grants}
            columns={[
              { header: 'الصلاحية', render: (g) => permissionLabels[g.permission_key] },
              { header: 'الحالة', render: (g) => (g.revoked_at ? <Badge tone="neutral">مسحوبة</Badge> : <Badge tone="success">فعّالة</Badge>) },
              { header: 'منحت في', render: (g) => new Date(g.granted_at).toLocaleString('ar') },
              {
                header: '',
                render: (g) => (!g.revoked_at ? <Button variant="danger" onClick={() => handleRevoke(g.id)} disabled={busy}>سحب</Button> : null),
              },
            ]}
          />
        )}
      </Card>

      <UserFormDialog open={editOpen} mode="edit" user={user} onClose={() => setEditOpen(false)} onSaved={() => { setEditOpen(false); void load(); }} />

      <ConfirmDialog
        open={confirmAction !== null}
        title={
          confirmAction === 'disable' ? 'تعطيل الحساب؟' :
          confirmAction === 'enable' ? 'تفعيل الحساب؟' :
          'إنهاء كل الجلسات؟'
        }
        description={confirmAction === 'disable' ? 'لن يستطيع هذا المستخدم تسجيل الدخول بعد التعطيل، لكن سجلاته تبقى محفوظة (لا حذف فعلي).' : undefined}
        variant={confirmAction === 'disable' || confirmAction === 'terminate-sessions' ? 'danger' : 'primary'}
        loading={busy}
        onConfirm={runConfirmed}
        onCancel={() => setConfirmAction(null)}
      />
    </div>
  );
}
