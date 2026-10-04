import { describe, expect, it } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { SitesListPage } from '../src/features/organization/SitesListPage';
import { renderWithProviders, mockFetchSequence } from './testUtils';

describe('Sites list flow', () => {
  it('renders sites with status badges', async () => {
    mockFetchSequence({
      'GET /sites': {
        status: 200,
        body: { data: [{ id: 12, type: 'school', code: 'SCH-012', name_ar: 'مدرسة الأمل', status: 'active' }], meta: { page: 1, per_page: 20, total: 1 } },
      },
    });
    renderWithProviders(<SitesListPage />);
    await waitFor(() => expect(screen.getByText('مدرسة الأمل')).toBeInTheDocument());
    expect(screen.getByText('فعّال')).toBeInTheDocument();
  });

  it('shows a friendly error state with retry on API failure', async () => {
    mockFetchSequence({ 'GET /sites': { status: 500, body: {} } });
    renderWithProviders(<SitesListPage />);
    await waitFor(() => expect(screen.getByRole('alert')).toBeInTheDocument());
    expect(screen.getByText('إعادة المحاولة')).toBeInTheDocument();
  });
});
