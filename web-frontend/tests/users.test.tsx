import { describe, expect, it } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { UsersListPage } from '../src/features/users/UsersListPage';
import { renderWithProviders, mockFetchSequence } from './testUtils';

describe('Users list & create (chairman-only CRUD flows)', () => {
  it('renders the paginated users list', async () => {
    mockFetchSequence({
      'GET /users': {
        status: 200,
      },
    });

    renderWithProviders(<UsersListPage />);

    await waitFor(() => expect(screen.getByText('خالد')).toBeInTheDocument());
  });

  it('shows empty state when there are no users', async () => {
    mockFetchSequence({ 'GET /users': { status: 200, body: { data: [], meta: { page: 1, per_page: 20, total: 0 } } } } );
    renderWithProviders(<UsersListPage />);
    await waitFor(() => expect(screen.getByText('لا يوجد مستخدمون')).toBeInTheDocument());
  });

  it('shows 422 validation errors inline on the create-user form', async () => {
    mockFetchSequence({
      'GET /users': { status: 200, body: { data: [], meta: { page: 1, per_page: 20, total: 0 } } },
      'POST /users': {
        status: 422,
        body: { error: { code: 'validation_failed', message: 'بيانات غير صالحة', fields: { site_scope_ids: ['مطلوب عند view_scope=sites'] } } },
      },
    });

    renderWithProviders(<UsersListPage />);
    await waitFor(() => screen.getByText('لا يوجد مستخدمون'));

    await userEvent.click(screen.getByRole('button', { name: 'إنشاء حساب' }));
    await userEvent.type(screen.getByLabelText('الاسم'), 'موظف جديد');
    await userEvent.type(screen.getByLabelText('البريد الإلكتروني'), 'new@moehe.example');

    const scopeSelect = screen.getByLabelText('نطاق العرض');
    await userEvent.selectOptions(scopeSelect, 'sites');

    await userEvent.click(screen.getByRole('button', { name: 'حفظ' }));

    await waitFor(() => expect(screen.getByText('مطلوب عند view_scope=sites')).toBeInTheDocument());
  });
});
