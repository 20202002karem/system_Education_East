import { describe, expect, it } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { SettingsPage } from '../src/features/settings/SettingsPage';
import { ReferenceDataPage } from '../src/features/settings/ReferenceDataPage';
import { renderWithProviders, mockFetchSequence } from './testUtils';

describe('Settings & Reference Data flows', () => {
  it('renders settings with their current values', async () => {
    mockFetchSequence({
      'GET /settings': { status: 200, body: { data: [{ key: 'reopen_window_days', value: '7', updated_by: 1 }] } },
    });
    renderWithProviders(<SettingsPage />);
    await waitFor(() => expect(screen.getByDisplayValue('7')).toBeInTheDocument());
  });

  it('renders device-categories reference tab by default', async () => {
    mockFetchSequence({
      'GET /reference/device-categories': { status: 200, body: { data: [{ id: 1, name: 'حاسوب مكتبي' }] } },
    });
    renderWithProviders(<ReferenceDataPage />);
    await waitFor(() => expect(screen.getByText('حاسوب مكتبي')).toBeInTheDocument());
  });
});
