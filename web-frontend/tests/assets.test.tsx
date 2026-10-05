import { describe, expect, it, beforeEach } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { Route, Routes } from 'react-router-dom';
import { AssetsListPage } from '../src/features/assets/AssetsListPage';
import { AssetDetailPage } from '../src/features/assets/AssetDetailPage';
import { renderWithProviders, mockFetchSequence } from './testUtils';
import { setToken } from '../src/lib/apiClient';

const me = (role: string) => ({ status: 200, body: { data: { id: 1, name: 'U', email: 'u@x', role, view_scope: 'all', status: 'active' } } });
const asset = { id: 5, inventory_no: 'PC-1', serial_no: null, category_id: 3, current_site_id: 12, status: 'working', holder_text: null, version: 1, created_at: '2026-10-01T07:00:00Z', updated_at: '2026-10-01T07:00:00Z' };
const empty = { status: 200, body: { data: [], meta: { page: 1, per_page: 20, total: 0 } } };

describe('Assets (M2)', () => {
  beforeEach(() => { sessionStorage.clear(); setToken('t'); });

  it('lists assets from GET /assets with status badge', async () => {
    mockFetchSequence({
      'GET /auth/me': me('secretary'),
      'GET /reference/device-categories': { status: 403, body: { error: { code: 'forbidden', message: 'x' } } },
      'GET /sites': { status: 403, body: { error: { code: 'forbidden', message: 'x' } } },
      'GET /assets': { status: 200, body: { data: [asset], meta: { page: 1, per_page: 20, total: 1 } } },
    });
    renderWithProviders(<AssetsListPage />);
    await waitFor(() => expect(screen.getByText('PC-1')).toBeInTheDocument());
    expect(screen.getAllByText('يعمل').length).toBeGreaterThan(0);
    expect(screen.getByText('#3')).toBeInTheDocument(); // names unavailable for non-chairman
  });

  it('shows empty and error states', async () => {
    mockFetchSequence({
      'GET /auth/me': me('chairman'),
      'GET /reference/device-categories': { status: 200, body: { data: [] } },
      'GET /sites': empty,
      'GET /assets': { status: 500, body: {} },
    });
    renderWithProviders(<AssetsListPage />);
    await waitFor(() => expect(screen.getByRole('alert')).toBeInTheDocument());
  });

  it('hides create for school_manager and the correction action for non-chairman', async () => {
    mockFetchSequence({
      'GET /auth/me': me('school_manager'),
      'GET /reference/device-categories': { status: 403, body: {} },
      'GET /sites': { status: 403, body: {} },
      'GET /assets': empty,
    });
    renderWithProviders(<AssetsListPage />);
    await waitFor(() => expect(screen.getByText('لا توجد أجهزة')).toBeInTheDocument());
    expect(screen.queryByText('إضافة جهاز')).toBeNull();
  });

  it('detail: chairman sees correction action and status change posts with version + Idempotency-Key', async () => {
    const calls = mockFetchSequence({
      'GET /auth/me': me('chairman'),
      'GET /reference/device-categories': { status: 200, body: { data: [{ id: 3, name: 'حاسوب' }] } },
      'GET /sites': { status: 200, body: { data: [{ id: 12, name_ar: 'مدرسة الأمل', type: 'school', code: 'S', status: 'active' }], meta: { page: 1, per_page: 100, total: 1 } } },
      'GET /assets/5': { status: 200, body: { data: asset } },
      'GET /assets/5/status-history': empty,
      'GET /assets/5/identifier-corrections': empty,
      'GET /assets/5/legacy-numbers': empty,
      'POST /assets/5/status-changes': { status: 201, body: { data: { asset: { id: 5, status: 'broken', version: 2 }, status_history_entry: { id: 1, from_status: 'working', to_status: 'broken', changed_by: 1, reason: null, changed_at: '2026-10-01T07:00:00Z' } } } },
    });
    renderWithProviders(
      <Routes><Route path="/assets/:id" element={<AssetDetailPage />} /></Routes>,
      { route: '/assets/5' },
    );
    await waitFor(() => expect(screen.getByText('تصحيح الأرقام')).toBeInTheDocument());
    fireEvent.click(screen.getByText('تحديث الحالة'));
    fireEvent.change(screen.getByLabelText('الحالة الجديدة'), { target: { value: 'broken' } });
    fireEvent.click(screen.getByRole('button', { name: 'تحديث' }));
    await waitFor(() => expect(calls.some((c) => c.init?.method === 'POST')).toBe(true));
    const post = calls.find((c) => c.init?.method === 'POST')!;
    expect(JSON.parse(String(post.init!.body))).toMatchObject({ to_status: 'broken', version: 1 });
    expect((post.init!.headers as Record<string, string>)['Idempotency-Key']).toBeTruthy();
  });
});
