import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { Table } from '../../components/Table';
import { FormDialog, type FieldDef } from '../../components/FormDialog';
import { LoadingState, EmptyState, ErrorState } from '../../components/states';
import { useSnackbar } from '../../components/Snackbar';
import { ApiError } from '../../lib/apiClient';
import { useAuth } from '../../auth/AuthContext';
import type { RequestAssignment, RequestCycle, RequestNote, ServiceRequest, Task } from '../../lib/types';
import { requestsApi } from './requestsApi';
import { tasksApi } from '../tasks/tasksApi';
import { AttachmentsPanel } from '../attachments/AttachmentsPanel';
import {
  assignableRequest, cancelCategories, formatDateTime, nonTerminalRequest, priorityLabels, priorityOptions,
  requestStatusLabels, requestStatusTone, roleLabels, taskStatusLabels,
} from './requestLabels';
import { useRequestLookups } from './useRequestLookups';

type DialogKey = 'triage' | 'assign' | 'hold' | 'close' | 'cancel' | 'reopen' | 'propose' | null;

/** M3-UC-010. Action buttons are *hidden* (not just disabled) by role/state; the server re-checks everything. */
export function RequestDetailPage() {
  const { id } = useParams();
  const rid = Number(id);
  const { user } = useAuth();
  const { notify } = useSnackbar();
  const lookups = useRequestLookups(true);
  const [req, setReq] = useState<ServiceRequest | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [dialog, setDialog] = useState<DialogKey>(null);
  const [notes, setNotes] = useState<RequestNote[]>([]);
  const [assignments, setAssignments] = useState<RequestAssignment[]>([]);
  const [cycles, setCycles] = useState<RequestCycle[]>([]);
  const [tasks, setTasks] = useState<Task[]>([]);
  const [noteBody, setNoteBody] = useState('');
  const [noteVis, setNoteVis] = useState<'internal' | 'external'>('external');
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try {
      const r = await requestsApi.get(rid);
      setReq(r);
      const [n, a, c] = await Promise.all([requestsApi.notes(rid), requestsApi.assignments(rid), requestsApi.cycles(rid)]);
      setNotes(n.data); setAssignments(a.data); setCycles(c.data);
      if (r.cycle && user?.role !== 'school_manager') {
        tasksApi.list({ request_cycle_id: r.cycle.id, per_page: 100 }).then((t) => setTasks(t.data)).catch(() => setTasks([]));
      }
    } catch (e) {
      setError(e instanceof ApiError ? (e.status === 404 ? 'الطلب غير موجود' : e.message) : 'تعذّر تحميل الطلب');
    }
  }, [rid, user?.role]);
  useEffect(() => { void load(); }, [load]);

  if (error) return <ErrorState message={error} onRetry={load} />;
  if (!req || !user) return <LoadingState />;

  const status = req.status;
  const cycle = req.cycle;
  const version = cycle?.version;
  const isAssignee = cycle?.assignee_user_id === user.id;
  const isDesk = user.role === 'chairman' || user.role === 'secretary';
  const isRequester = req.requester_user_id === user.id;

  const can = {
    triage: isDesk && status === 'new',
    assign: isDesk && !!status && assignableRequest.includes(status),
    start: isAssignee && status === 'assigned',
    hold: isAssignee && status === 'in_progress',
    resume: isAssignee && status === 'held',
    close: isAssignee && status === 'in_progress',
    cancel: !!status && nonTerminalRequest.includes(status) && (user.role === 'chairman'
      || ((user.role === 'secretary' || (user.role === 'school_manager' && isRequester)) && (status === 'new' || status === 'triaged'))),
    reopen: status === 'closed' && (user.role === 'chairman' || (user.role === 'school_manager' && isRequester)),
    propose: isAssignee && (user.role === 'engineer' || user.role === 'technician') && (status === 'assigned' || status === 'in_progress' || status === 'held'),
  };
  const afterStart = status === 'in_progress' || status === 'held';

  async function run(fn: () => Promise<unknown>, ok: string) {
    setBusy(true);
    try { await fn(); notify(ok, 'success'); await load(); }
    catch (e) {
      notify(e instanceof ApiError ? e.message : 'تعذّر تنفيذ العملية', 'error');
      if (e instanceof ApiError && (e.code === 'version_conflict' || e.status === 409)) await load();
    } finally { setBusy(false); }
  }
  const done = (msg: string) => async (fn: () => Promise<unknown>) => { await fn(); notify(msg, 'success'); setDialog(null); await load(); };

  const assignees = lookups.users.filter((u) => (user.role === 'chairman' ? ['chairman', 'engineer', 'technician'] : ['engineer', 'technician']).includes(u.role));
  const assignFields: FieldDef[] = [
    { name: 'assignee_id', label: 'المكلّف', type: 'select', required: true, options: assignees.map((u) => ({ value: String(u.id), label: `${u.name} — ${roleLabels[u.role]}` })) },
    { name: 'reason', label: cycle?.assignee_user_id ? 'سبب إعادة الإسناد' : 'سبب (اختياري)', type: 'text', required: !!cycle?.assignee_user_id, maxLength: 255 },
  ];

  async function addNote() {
    if (!noteBody.trim()) return;
    await run(async () => { await requestsApi.addNote(rid, { visibility: noteVis, body: noteBody }); setNoteBody(''); }, 'تمت إضافة الملاحظة');
  }

  return (
    <>
      <Card title={`الطلب ${req.ref_no}`} actions={<Link to="/requests">رجوع للقائمة</Link>}>
        <dl style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: 'var(--space-3)', margin: 0 }}>
          <div><dt>الحالة</dt><dd><Badge tone={requestStatusTone(status)}>{status ? requestStatusLabels[status] : '—'}</Badge></dd></div>
          <div><dt>الموقع (أصل الطلب)</dt><dd>{req.origin_site_name ?? lookups.siteName(req.origin_site_id)}</dd></div>
          <div><dt>الأولوية</dt><dd>{req.priority ? priorityLabels[req.priority] : '—'}{req.suggested_priority ? ` (مقترحة: ${priorityLabels[req.suggested_priority]})` : ''}</dd></div>
          <div><dt>النوع</dt><dd>{lookups.typeName(req.request_type_id)}</dd></div>
          <div><dt>مقدّم الطلب</dt><dd>{req.requester_name ?? (req.requester_user_id ? `#${req.requester_user_id}` : '—')}</dd></div>
          <div><dt>المكلّف الحالي</dt><dd>{cycle?.assignee_name ?? lookups.userName(cycle?.assignee_user_id ?? null)}</dd></div>
          <div><dt>الدورة</dt><dd>{req.current_cycle_no} (إعادات الفتح: {req.reopen_count})</dd></div>
          <div><dt>تاريخ الإنشاء</dt><dd>{formatDateTime(req.created_at)}</dd></div>
        </dl>
        <p style={{ whiteSpace: 'pre-wrap' }}>{req.description}</p>
        {cycle?.hold_reason && status === 'held' && <p>سبب التعليق: {cycle.hold_reason}</p>}
        {status === 'closed' && cycle && <p>الإغلاق: {cycle.closure_action} — النتيجة: {cycle.closure_result} — الزمن: {cycle.closure_effort_minutes} دقيقة</p>}
        {req.cancellation && <p>سبب الإلغاء: {req.cancellation.reason_text}{req.cancellation.work_done_summary ? ` — ما أُنجز: ${req.cancellation.work_done_summary}` : ''}</p>}

        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 'var(--space-3)', marginTop: 'var(--space-4)' }}>
          {can.triage && <Button onClick={() => setDialog('triage')}>فرز</Button>}
          {can.assign && <Button onClick={() => setDialog('assign')}>{cycle?.assignee_user_id ? 'إعادة إسناد' : 'إسناد'}</Button>}
          {can.start && <Button loading={busy} onClick={() => run(() => requestsApi.start(rid, version), 'بدأ التنفيذ')}>بدء التنفيذ</Button>}
          {can.hold && <Button variant="secondary" onClick={() => setDialog('hold')}>تعليق</Button>}
          {can.resume && <Button loading={busy} onClick={() => run(() => requestsApi.resume(rid, version), 'تم الاستئناف')}>استئناف</Button>}
          {can.close && <Button onClick={() => setDialog('close')}>إغلاق</Button>}
          {can.propose && <Button variant="secondary" onClick={() => setDialog('propose')}>اقتراح إعادة إسناد/إلغاء</Button>}
          {can.cancel && <Button variant="danger" onClick={() => setDialog('cancel')}>إلغاء الطلب</Button>}
          {can.reopen && <Button variant="secondary" onClick={() => setDialog('reopen')}>اعتراض / إعادة فتح</Button>}
        </div>
      </Card>

      <Card title="الملاحظات">
        {notes.length === 0 && <EmptyState title="لا توجد ملاحظات" />}
        {notes.map((n) => (
          <div key={n.id} style={{ padding: 'var(--space-3) 0', borderBottom: '1px solid var(--color-border)' }}>
            <small style={{ color: 'var(--color-text-muted)' }}>{n.author_name ?? `#${n.author_id}`} · {formatDateTime(n.created_at)} · </small>
            <Badge tone={n.visibility === 'internal' ? 'warning' : 'neutral'}>{n.visibility === 'internal' ? 'داخلية' : 'خارجية'}</Badge>
            <p style={{ whiteSpace: 'pre-wrap', margin: 'var(--space-2) 0 0' }}>{n.body}</p>
            <AttachmentsPanel ownerType="request_note" ownerId={n.id} canUpload={n.author_id === user.id} />
          </div>
        ))}
        <div style={{ marginTop: 'var(--space-4)' }}>
          <label htmlFor="note-body" style={{ display: 'block', fontSize: 'var(--font-size-sm)', color: 'var(--color-text-muted)' }}>ملاحظة جديدة</label>
          <textarea id="note-body" rows={3} value={noteBody} onChange={(e) => setNoteBody(e.target.value)} style={{ width: '100%', font: 'inherit', padding: 'var(--space-2)' }} />
          <div style={{ display: 'flex', gap: 'var(--space-3)', alignItems: 'center', marginTop: 'var(--space-2)' }}>
            {user.role !== 'school_manager' && (
              <select aria-label="نوع الملاحظة" value={noteVis} onChange={(e) => setNoteVis(e.target.value as 'internal' | 'external')}>
                <option value="external">خارجية (يراها مقدّم الطلب)</option>
                <option value="internal">داخلية</option>
              </select>
            )}
            <Button onClick={addNote} loading={busy} disabled={!noteBody.trim()}>إضافة ملاحظة</Button>
          </div>
        </div>
      </Card>

      <AttachmentsPanel ownerType="request" ownerId={rid} canUpload />

      {user.role !== 'school_manager' && (
        <Card title="المهام الفرعية">
          {tasks.length === 0 ? <EmptyState title="لا توجد مهام مرتبطة" /> : (
            <Table<Task> rows={tasks} columns={[
              { header: 'الرقم', render: (t) => <Link to={`/tasks/${t.id}`}>{t.ref_no}</Link> },
              { header: 'العنوان', render: (t) => t.title },
              { header: 'الحالة', render: (t) => <Badge tone={requestStatusTone(t.status)}>{taskStatusLabels[t.status]}</Badge> },
            ]} />
          )}
        </Card>
      )}

      <Card title="سجل الإسناد والدورات">
        <Table<RequestCycle> rows={cycles} columns={[
          { header: 'الدورة', render: (c) => c.cycle_no },
          { header: 'الحالة', render: (c) => requestStatusLabels[c.status] },
          { header: 'بدأت', render: (c) => formatDateTime(c.created_at) },
          { header: 'أُغلقت', render: (c) => formatDateTime(c.closed_at) },
        ]} />
        {assignments.length > 0 && (
          <Table<RequestAssignment> rows={assignments} columns={[
            { header: 'المكلّف', render: (a) => lookups.userName(a.assignee_id) },
            { header: 'من', render: (a) => formatDateTime(a.from) },
            { header: 'إلى', render: (a) => formatDateTime(a.to) },
            { header: 'السبب', render: (a) => a.reason ?? '—' },
          ]} />
        )}
      </Card>

      <FormDialog open={dialog === 'triage'} title="فرز الطلب" submitLabel="فرز" onClose={() => setDialog(null)}
        fields={[
          { name: 'request_type_id', label: 'نوع الطلب', type: 'select', required: true, options: lookups.requestTypes.filter((t) => t.is_active !== false).map((t) => ({ value: String(t.id), label: t.name })) },
          { name: 'priority', label: 'الأولوية', type: 'select', required: true, options: priorityOptions },
        ]}
        onSubmit={(v) => done('تم الفرز')(() => requestsApi.triage(rid, { request_type_id: Number(v.request_type_id), priority: v.priority as never, version }))} />
      <FormDialog open={dialog === 'assign'} title={cycle?.assignee_user_id ? 'إعادة إسناد الطلب' : 'إسناد الطلب'} submitLabel="إسناد" fields={assignFields} onClose={() => setDialog(null)}
        onSubmit={(v) => done('تم الإسناد')(() => requestsApi.assign(rid, { assignee_id: Number(v.assignee_id), reason: v.reason || null, version }))} />
      <FormDialog open={dialog === 'hold'} title="تعليق الطلب" submitLabel="تعليق" onClose={() => setDialog(null)}
        fields={[{ name: 'hold_reason', label: 'سبب التعليق', type: 'text', maxLength: 255 }]}
        onSubmit={(v) => done('تم التعليق')(() => requestsApi.hold(rid, { hold_reason: v.hold_reason || null, version }))} />
      <FormDialog open={dialog === 'close'} title="إغلاق الطلب" submitLabel="إغلاق" onClose={() => setDialog(null)}
        fields={[
          { name: 'closure_action', label: 'الإجراء المنفَّذ', required: true, maxLength: 255 },
          { name: 'closure_result', label: 'النتيجة', required: true, maxLength: 255 },
          { name: 'closure_effort_minutes', label: 'الزمن المستغرق (دقائق)', type: 'number', min: 0, required: true },
        ]}
        onSubmit={(v) => done('تم إغلاق الطلب')(() => requestsApi.close(rid, { closure_action: v.closure_action, closure_result: v.closure_result, closure_effort_minutes: Number(v.closure_effort_minutes), version }))} />
      <FormDialog open={dialog === 'cancel'} title="إلغاء الطلب" description="الإلغاء نهائي ولا يمكن التراجع عنه." submitLabel="إلغاء الطلب" danger onClose={() => setDialog(null)}
        fields={[
          { name: 'reason_category', label: 'تصنيف السبب', type: 'select', required: true, options: cancelCategories },
          { name: 'reason_text', label: 'نص السبب', type: 'textarea', required: true },
          ...(afterStart ? [{ name: 'work_done_summary', label: 'ما أُنجز', type: 'textarea' as const, required: true }] : []),
        ]}
        onSubmit={(v) => done('تم إلغاء الطلب')(() => requestsApi.cancel(rid, { reason_category: v.reason_category, reason_text: v.reason_text, work_done_summary: v.work_done_summary || null, version }))} />
      <FormDialog open={dialog === 'reopen'} title="اعتراض / إعادة فتح" submitLabel="إعادة فتح" onClose={() => setDialog(null)}
        fields={[{ name: 'reason', label: 'السبب', type: 'textarea', required: true }]}
        onSubmit={(v) => done('أُعيد فتح الطلب')(() => requestsApi.reopen(rid, v.reason))} />
      <FormDialog open={dialog === 'propose'} title="اقتراح" description="لا يتغيّر شيء حتى يقرّر رئيس القسم أو السكرتير." submitLabel="إرسال الاقتراح" onClose={() => setDialog(null)}
        fields={[
          { name: 'type', label: 'نوع الاقتراح', type: 'select', required: true, options: [{ value: 'reassign', label: 'إعادة إسناد' }, { value: 'cancel', label: 'إلغاء' }] },
          { name: 'reason', label: 'السبب', type: 'textarea', required: true },
          { name: 'work_done_summary', label: 'ما أُنجز (إلزامي عند اقتراح الإلغاء)', type: 'textarea' },
        ]}
        onSubmit={(v) => done('أُرسل الاقتراح')(() => requestsApi.propose(rid, { type: v.type as never, reason: v.reason, work_done_summary: v.work_done_summary || null }))} />
    </>
  );
}
