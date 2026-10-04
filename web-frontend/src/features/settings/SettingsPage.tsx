import { useEffect, useState } from 'react';
import { Card } from '../../components/Card';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { LoadingState, ErrorState } from '../../components/states';
import { useSnackbar } from '../../components/Snackbar';
import { ApiError } from '../../lib/apiClient';
import type { Setting } from '../../lib/types';
import { settingsApi } from './settingsApi';

const settingLabels: Record<string, string> = {
  reopen_window_days: 'مدة إعادة فتح الطلب (أيام) — D-21',
  approval_validity_days: 'مدة صلاحية الاعتماد (أيام) — D-21',
  transfer_reminder_days: 'مدة تذكير النقل (أيام) — D-21',
  late_approval_hours: 'مهلة الاعتماد اللاحق (ساعات) — D-21',
  session_inactivity_minutes: 'مهلة الخمول (دقائق) — MD-01',
  max_failed_login_attempts: 'عدد المحاولات قبل القفل — MD-01',
  lockout_minutes: 'مدة القفل (دقائق) — MD-01',
  attachment_size_limit: 'حد حجم المرفقات — D-34 (Pending)',
};

/** Each PATCH here writes a settings_history row on the backend automatically (Batch 3 §3.6). */
export function SettingsPage() {
  const { notify } = useSnackbar();
  const [settings, setSettings] = useState<Setting[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [drafts, setDrafts] = useState<Record<string, string>>({});
  const [savingKey, setSavingKey] = useState<string | null>(null);

  async function load() {
    setError(null);
    try {
      const data = await settingsApi.list();
      setSettings(data);
      setDrafts(Object.fromEntries(data.map((s) => [s.key, s.value ?? ''])));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'تعذّر تحميل الإعدادات');
    }
  }

  useEffect(() => { void load(); }, []);

  async function save(key: string) {
    setSavingKey(key);
    try {
      await settingsApi.update(key, drafts[key]);
      notify('تم حفظ الإعداد وتسجيله في السجل التاريخي', 'success');
      await load();
    } catch (e) {
      notify(e instanceof ApiError ? e.message : 'تعذّر الحفظ', 'error');
    } finally {
      setSavingKey(null);
    }
  }

  if (error) return <ErrorState message={error} onRetry={load} />;
  if (!settings) return <LoadingState />;

  return (
    <Card title="الإعدادات">
      {settings.map((s) => (
        <div key={s.key} style={{ display: 'flex', gap: 'var(--space-3)', alignItems: 'flex-end', flexWrap: 'wrap', borderBottom: '1px solid var(--color-border)', paddingBottom: 'var(--space-3)', marginBottom: 'var(--space-3)' }}>
          <div style={{ flex: 1, minWidth: 240 }}>
            <Input
              label={settingLabels[s.key] ?? s.key}
              value={drafts[s.key] ?? ''}
              onChange={(e) => setDrafts((d) => ({ ...d, [s.key]: e.target.value }))}
              hint={s.value === null ? 'لا قيمة محددة بعد' : undefined}
            />
          </div>
          <Button onClick={() => save(s.key)} loading={savingKey === s.key} disabled={drafts[s.key] === (s.value ?? '')}>
            حفظ
          </Button>
        </div>
      ))}
    </Card>
  );
}
