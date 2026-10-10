import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { FormDialog } from '../../components/FormDialog';
import { LoadingState, ErrorState } from '../../components/states';
import { useSnackbar } from '../../components/Snackbar';
import { ApiError } from '../../lib/apiClient';
import { useAuth } from '../../auth/AuthContext';
import type { Task } from '../../lib/types';
import { tasksApi } from './tasksApi';
import { AttachmentsPanel } from '../attachments/AttachmentsPanel';
import { formatDateTime, requestStatusTone, roleLabels, taskStatusLabels } from '../requests/requestLabels';
import { useRequestLookups } from '../requests/useRequestLookups';

type DialogKey = 'assign' | 'complete' | 'cancel' | 'propose' | null;

/** M3-UC-009. Buttons are hidden by role/state; the server decides. */
export function TaskDetailPage() {
  const rid = Number(useParams().id);
  const { user } = useAuth();
  const { notify } = useSnackbar();
  const lookups = useRequestLookups(true);
  const [task, setTask] = useState<Task | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [dialog, setDialog] = useState<DialogKey>(null);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try { setTask(await tasksApi.get(rid)); }
    catch (e) { setError(e instanceof ApiError ? (e.status === 404 ? 'المهمة غير موجودة' : e.message) : 'تعذّر التحميل'); }
  }, [rid]);
  useEffect(() => { void load(); }, [load]);

  if (error) return <ErrorState message={error} onRetry={load} />;
  if (!task || !user) return <LoadingState />;

  const isOwner = task.assignee_id === user.id;
  const isChair = user.role === 'chairman';
  const isDesk = isChair || user.role === 'secretary';
  const taskType = lookups.taskTypes.find((t) => t.id === task.task_type_id);
  const live = ['new', 'assigned', 'in_progress', 'held'].includes(task.status);
  const can = {
    assign: isDesk && live,
    start: isOwner && task.status === 'assigned',
    hold: isOwner && task.status === 'in_progress',
    resume: isOwner && task.status === 'held',
    complete: isOwner && task.status === 'in_progress',
    cancel: live && (isChair || (user.role === 'secretary' && (task.status === 'new' || task.status === 'assigned') && !!taskType?.is_administrative)),
    propose: isOwner && (user.role === 'engineer' || user.role === 'technician') && ['assigned', 'in_progress', 'held'].includes(task.status),
  };
  const afterStart = task.status === 'in_progress' || task.status === 'held';

  async function run(fn: () => Promise<unknown>, ok: string) {
    setBusy(true);
    try { await fn(); notify(ok, 'success'); await load(); }
    catch (e) { notify(e instanceof ApiError ? e.message : 'تعذّر تنفيذ العملية', 'error'); if (e instanceof ApiError && e.status === 409) await load(); }
    finally { setBusy(false); }
  }
  const done = (msg: string) => async (fn: () => Promise<unknown>) => { await fn(); notify(msg, 'success'); setDialog(null); await load(); };
  const allowed = isChair ? ['chairman', 'engineer', 'technician', 'secretary'] : ['engineer', 'technician'];
  const assignees = lookups.users.filter((u) => allowed.includes(u.role));
  const v = task.version;

  return (
    <>
      <Card title={`المهمة ${task.ref_no}`} actions={<Link to="/tasks">رجوع للقائمة</Link>}>
        <h3 style={{ marginTop: 0 }}>{task.title}</h3>
        <p><Badge tone={requestStatusTone(task.status)}>{taskStatusLabels[task.status]}</Badge></p>
        {task.description && <p style={{ whiteSpace: 'pre-wrap' }}>{task.description}</p>}
        <p>المسؤول: {lookups.userName(task.assignee_id)} · الاستحقاق: {formatDateTime(task.due_at)} · الموقع: {lookups.siteName(task.site_id)}</p>
        {task.request_cycle_id && <p>مرتبطة بدورة طلب #{task.request_cycle_id}</p>}
        {task.result_summary && <p>ملخص النتيجة: {task.result_summary}</p>}
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 'var(--space-3)' }}>
          {can.assign && <Button onClick={() => setDialog('assign')}>{task.assignee_id ? 'إعادة إسناد' : 'إسناد'}</Button>}
          {can.start && <Button loading={busy} onClick={() => run(() => tasksApi.start(rid, v), 'بدأت المهمة')}>بدء</Button>}
          {can.hold && <Button variant="secondary" loading={busy} onClick={() => run(() => tasksApi.hold(rid, v), 'عُلّقت المهمة')}>تعليق</Button>}
          {can.resume && <Button loading={busy} onClick={() => run(() => tasksApi.resume(rid, v), 'استُؤنفت المهمة')}>استئناف</Button>}
          {can.complete && <Button onClick={() => setDialog('complete')}>إتمام</Button>}
          {can.propose && <Button variant="secondary" onClick={() => setDialog('propose')}>اقتراح إعادة إسناد/إلغاء</Button>}
          {can.cancel && <Button variant="danger" onClick={() => setDialog('cancel')}>إلغاء المهمة</Button>}
        </div>
      </Card>
      <AttachmentsPanel ownerType="task" ownerId={rid} canUpload />

      <FormDialog open={dialog === 'assign'} title={task.assignee_id ? 'إعادة إسناد المهمة' : 'إسناد المهمة'} submitLabel="إسناد" onClose={() => setDialog(null)}
        fields={[
          { name: 'assignee_id', label: 'المسؤول', type: 'select', required: true, options: assignees.map((u) => ({ value: String(u.id), label: `${u.name} — ${roleLabels[u.role]}` })) },
          { name: 'reason', label: task.assignee_id ? 'سبب إعادة الإسناد' : 'سبب (اختياري)', required: !!task.assignee_id, maxLength: 255 },
        ]}
        onSubmit={(f) => done('تم الإسناد')(() => tasksApi.assign(rid, { assignee_id: Number(f.assignee_id), reason: f.reason || null, version: v }))} />
      <FormDialog open={dialog === 'complete'} title="إتمام المهمة" submitLabel="إتمام" onClose={() => setDialog(null)}
        fields={[{ name: 'result_summary', label: 'ملخص النتيجة', type: 'textarea', required: true }]}
        onSubmit={(f) => done('اكتملت المهمة')(() => tasksApi.complete(rid, { result_summary: f.result_summary, version: v }))} />
      <FormDialog open={dialog === 'cancel'} title="إلغاء المهمة" submitLabel="إلغاء المهمة" danger onClose={() => setDialog(null)}
        fields={[
          { name: 'reason', label: 'السبب', type: 'textarea', required: true },
          ...(afterStart ? [{ name: 'work_done_summary', label: 'ما أُنجز', type: 'textarea' as const, required: true }] : []),
        ]}
        onSubmit={(f) => done('أُلغيت المهمة')(() => tasksApi.cancel(rid, { reason: f.reason, work_done_summary: f.work_done_summary || null, version: v }))} />
      <FormDialog open={dialog === 'propose'} title="اقتراح" submitLabel="إرسال" onClose={() => setDialog(null)}
        fields={[
          { name: 'type', label: 'النوع', type: 'select', required: true, options: [{ value: 'reassign', label: 'إعادة إسناد' }, { value: 'cancel', label: 'إلغاء' }] },
          { name: 'reason', label: 'السبب', type: 'textarea', required: true },
          { name: 'work_done_summary', label: 'ما أُنجز (إلزامي عند الإلغاء)', type: 'textarea' },
        ]}
        onSubmit={(f) => done('أُرسل الاقتراح')(() => tasksApi.propose(rid, { type: f.type as never, reason: f.reason, work_done_summary: f.work_done_summary || null }))} />
    </>
  );
}
