import { describe, expect, it } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { AuditLogPage } from '../src/features/audit/AuditLogPage';
import { renderWithProviders, mockFetchSequence } from './testUtils';

describe('Audit log read (no write affordance anywhere)', () => {
  it('lists audit entries and never renders an edit/delete action', async () => {
    mockFetchSequence({
      'GET /audit-log': {
        status: 200,
        body: {
          data: [{ seq: 10, occurred_at: '2026-09-24T08:00:00Z', actor_id: 1, action: 'user.created', entity_type: 'user', entity_id: '5', before: null, after: null, reason: null, source: 'web', ip: null, flags: null }],
          meta: { page: 1, per_page: 20, total: 1 },
        },
      },
    });
    renderWithProviders(<AuditLogPage />);
    await waitFor(() => expect(screen.getByText('user.created')).toBeInTheDocument());

    expect(screen.queryByRole('button', { name: /حذف/ })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /تعديل/ })).not.toBeInTheDocument();
  });
});
