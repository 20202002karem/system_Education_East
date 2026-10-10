import { describe, expect, it, beforeEach } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { Route, Routes } from 'react-router-dom';
import { RequestsListPage } from '../src/features/requests/RequestsListPage';
import { RequestDetailPage } from '../src/features/requests/RequestDetailPage';
import { TaskDetailPage } from '../src/features/tasks/TaskDetailPage';
import { renderWithProviders, mockFetchSequence } from './testUtils';
import { setToken } from '../src/lib/apiClient';

const me = (role: string, id = 1) => ({ status: 200, body: { data: { id, name: 'U', email: 'u@x', role, view_scope: 'all', status: 'active' } } });
const page = (data: unknown[]) => ({ status: 200, body: { data, meta: { page: 1, per_page: 20, total: data.length } } });
const ok = (data: unknown) => ({ status: 200, body: { data } });
const cycle = (status: string, assignee: number | null = null) => ({
  id: 9, request_id: 5, cycle_no: 1, status, assignee_user_id: assignee, started_at: null, closed_at: null, closure_action: null,
  closure_result: null, closure_effort_minutes: null, hold_reason: null, version: 3, created_at: '2026-10-01T07:00:00Z', updated_at: '2026-10-01T07:00:00Z',
});
const request = (status: string, assignee: number | null = null, requester: number | null = 1) => ({
  id: 5, ref_no: 'REQ-2026-000005', origin_site_id: 2, origin_site_name: 'مدرسة الشجاعية', asset_id: null, requester_user_id: requester,
  requester_name: null, registered_by_user_id: 1, channel_id: 1, request_type_id: null, suggested_priority: null, priority: null,
  description: 'الجهاز لا يعمل', current_cycle_no: 1, reopen_count: 0, status, cycle: cycle(status, assignee), cancellation: null,
  created_at: '2026-10-01T07:00:00Z', updated_at: '2026-10-01T07:00:00Z',
});
const lookups = {
  'GET /sites': page([]), 'GET /reference/intake-channels': ok([]), 'GET /reference/request-types': ok([]),
  'GET /reference/task-types': ok([]), 'GET /users': page([]),
};
const detail = (role: string, req: unknown, id = 1) => ({
  'GET /auth/me': me(role, id), ...lookups, 'GET /requests/5': ok(req), 'GET /requests/5/notes': page([]),
  'GET /requests/5/assignments': page([]), 'GET /requests/5/cycles': page([]), 'GET /tasks': page([]),
  'GET /attachments': page([]),
});
const renderDetail = () => renderWithProviders(
  <Routes><Route path="/requests/:id" element={<RequestDetailPage />} /></Routes>, { route: '/requests/5' });

describe('Requests (M3)', () => {
  beforeEach(() => { sessionStorage.clear(); setToken('t'); });

  it('lists requests with status badge and empty state wording', async () => {
    mockFetchSequence({ 'GET /auth/me': me('chairman'), ...lookups, 'GET /requests': page([request('new')]) });
    renderWithProviders(<RequestsListPage />);
    await waitFor(() => expect(screen.getByText('REQ-2026-000005')).toBeInTheDocument());
    expect(screen.getAllByText('جديد').length).toBeGreaterThan(0);
  });

  it('shows the empty state', async () => {
    mockFetchSequence({ 'GET /auth/me': me('technician'), ...lookups, 'GET /requests': page([]) });
    renderWithProviders(<RequestsListPage />);
    await waitFor(() => expect(screen.getByText('لا طلبات مفتوحة ضمن نطاقك')).toBeInTheDocument());
  });

  it('create button only for school_manager and secretary', async () => {
    mockFetchSequence({ 'GET /auth/me': me('engineer'), ...lookups, 'GET /requests': page([]) });
    renderWithProviders(<RequestsListPage />);
    await waitFor(() => expect(screen.getByText('لا طلبات مفتوحة ضمن نطاقك')).toBeInTheDocument());
    expect(screen.queryByText('طلب جديد')).toBeNull();
  });

  it('chairman sees triage on a new request; school_manager sees only cancel', async () => {
    mockFetchSequence(detail('chairman', request('new')));
    const view = renderDetail();
    await waitFor(() => expect(screen.getByText('فرز')).toBeInTheDocument());
    view.unmount();
    mockFetchSequence(detail('school_manager', request('new'), 1));
    renderDetail();
    await waitFor(() => expect(screen.getByText('إلغاء الطلب')).toBeInTheDocument());
    expect(screen.queryByText('فرز')).toBeNull();
    expect(screen.queryByLabelText('نوع الملاحظة')).toBeNull(); // no internal notes for school managers
  });

  it('actions are hidden for someone who is not the current assignee', async () => {
    mockFetchSequence(detail('engineer', request('in_progress', 99), 1));
    renderDetail();
    await waitFor(() => expect(screen.getByText('الطلب REQ-2026-000005')).toBeInTheDocument());
    expect(screen.queryByText('إغلاق')).toBeNull();
    expect(screen.queryByText('تعليق')).toBeNull();
  });

  it('the assignee sees close/hold and the proposal action on an in-progress request', async () => {
    mockFetchSequence(detail('engineer', request('in_progress', 1), 1));
    renderDetail();
    await waitFor(() => expect(screen.getByText('إغلاق')).toBeInTheDocument());
    expect(screen.getByText('تعليق')).toBeInTheDocument();
    expect(screen.getByText('اقتراح إعادة إسناد/إلغاء')).toBeInTheDocument();
  });

  it('reopen is offered to the requester of a closed request', async () => {
    mockFetchSequence(detail('school_manager', request('closed', 7, 1), 1));
    renderDetail();
    await waitFor(() => expect(screen.getByText('اعتراض / إعادة فتح')).toBeInTheDocument());
  });

  it('shows a not-found message for out-of-scope requests (404)', async () => {
    mockFetchSequence({ ...detail('technician', request('new')), 'GET /requests/5': { status: 404, body: { error: { code: 'not_found', message: 'x' } } } });
    renderDetail();
    await waitFor(() => expect(screen.getByRole('alert')).toHaveTextContent('الطلب غير موجود'));
  });

  it('task detail hides cancel for a secretary on a non-administrative task', async () => {
    const task = { id: 3, ref_no: 'TSK-2026-000003', task_type_id: 1, title: 'فحص', description: null, request_cycle_id: null, asset_id: null, site_id: 2, assignee_id: null, status: 'new', due_at: null, result_summary: null, created_by: 1, version: 1, created_at: '2026-10-01T07:00:00Z', updated_at: '2026-10-01T07:00:00Z' };
    mockFetchSequence({ 'GET /auth/me': me('secretary'), ...lookups, 'GET /reference/task-types': ok([{ id: 1, name: 'عام', is_administrative: false }]), 'GET /tasks/3': ok(task), 'GET /attachments': page([]) });
    renderWithProviders(<Routes><Route path="/tasks/:id" element={<TaskDetailPage />} /></Routes>, { route: '/tasks/3' });
    await waitFor(() => expect(screen.getByText('المهمة TSK-2026-000003')).toBeInTheDocument());
    expect(screen.queryByText('إلغاء المهمة')).toBeNull();
    expect(screen.getByText('إسناد')).toBeInTheDocument();
  });
});
