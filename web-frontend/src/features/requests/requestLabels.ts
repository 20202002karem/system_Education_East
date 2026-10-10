import type { Priority, RequestStatus, Role, TaskStatus } from '../../lib/types';

export const requestStatusLabels: Record<RequestStatus, string> = {
  new: 'جديد',
  triaged: 'تم الفرز – بانتظار الإسناد',
  assigned: 'مُسنَد',
  in_progress: 'قيد التنفيذ',
  held: 'معلّق',
  reopened_pending_assignment: 'مُعاد فتحه – بانتظار الإسناد',
  closed: 'مغلق',
  cancelled: 'ملغى',
};

export const taskStatusLabels: Record<TaskStatus, string> = {
  new: 'جديدة', assigned: 'مُسنَدة', in_progress: 'قيد التنفيذ', held: 'معلّقة', completed: 'مكتملة', cancelled: 'ملغاة',
};

export const priorityLabels: Record<Priority, string> = { emergency: 'طارئة', high: 'عالية', normal: 'عادية', low: 'منخفضة' };
export const priorityOptions = (Object.keys(priorityLabels) as Priority[]).map((value) => ({ value, label: priorityLabels[value] }));

type Tone = 'success' | 'danger' | 'neutral' | 'warning';
export function requestStatusTone(s: string | null): Tone {
  if (s === 'closed' || s === 'completed') return 'success';
  if (s === 'cancelled') return 'danger';
  if (s === 'held' || s === 'new' || s === 'reopened_pending_assignment') return 'warning';
  return 'neutral';
}

export const roleLabels: Record<Role, string> = {
  chairman: 'رئيس القسم', secretary: 'السكرتير', engineer: 'مهندس', technician: 'فني', school_manager: 'مدير مدرسة',
};

export const cancelCategories = [
  { value: 'duplicate', label: 'طلب مكرر' },
  { value: 'resolved_itself', label: 'زالت المشكلة' },
  { value: 'withdrawn', label: 'سحب مقدّم الطلب' },
  { value: 'invalid', label: 'طلب غير صالح' },
  { value: 'other', label: 'أخرى' },
];

export function formatDateTime(iso: string | null | undefined): string {
  return iso ? new Date(iso).toLocaleString('ar') : '—';
}

/** The terminal request states: nothing but reading is possible afterwards (reopen aside for 'closed'). */
export const nonTerminalRequest: RequestStatus[] = ['new', 'triaged', 'assigned', 'in_progress', 'held', 'reopened_pending_assignment'];
export const assignableRequest: RequestStatus[] = ['triaged', 'assigned', 'in_progress', 'held', 'reopened_pending_assignment'];
