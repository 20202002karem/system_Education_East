import { describe, expect, it } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { LoginPage } from '../src/features/auth/LoginPage';
import { renderWithProviders, mockFetchSequence } from './testUtils';

describe('Login flow', () => {
  it('shows uniform error message on invalid credentials (does not distinguish account existence)', async () => {
    mockFetchSequence({
      'POST /auth/login': { status: 401, body: { error: { code: 'invalid_credentials', message: 'بيانات الدخول غير صحيحة' } } },
    });
    renderWithProviders(<LoginPage />);

    await userEvent.type(screen.getByLabelText('البريد الإلكتروني'), 'user@example.com');
    await userEvent.type(screen.getByLabelText('كلمة المرور'), 'wrong-pass');
    await userEvent.click(screen.getByRole('button', { name: 'دخول' }));

    await waitFor(() => expect(screen.getByRole('alert')).toHaveTextContent('بيانات الدخول غير صحيحة'));
  });

  it('shows account_locked (423) distinctly', async () => {
    mockFetchSequence({
      'POST /auth/login': { status: 423, body: { error: { code: 'account_locked', message: 'الحساب مقفل مؤقتاً، حاول لاحقاً' } } },
    });
    renderWithProviders(<LoginPage />);

    await userEvent.type(screen.getByLabelText('البريد الإلكتروني'), 'chairman@example.com');
    await userEvent.type(screen.getByLabelText('كلمة المرور'), 'whatever');
    await userEvent.click(screen.getByRole('button', { name: 'دخول' }));

    await waitFor(() => expect(screen.getByRole('alert')).toHaveTextContent('مقفل'));
  });
});
