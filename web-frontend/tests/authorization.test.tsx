import { describe, expect, it } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { AuthProvider } from '../src/auth/AuthContext';
import { SnackbarProvider } from '../src/components/Snackbar';
import { ProtectedRoute } from '../src/auth/ProtectedRoute';
import { mockFetchSequence } from './testUtils';

/**
 * Frontend role gating is a UX layer only (instructions §7) — this test
 * verifies the UI *hides* the route for a non-chairman role. It does NOT
 * assert this is a security boundary; the backend AuthorizationTest (in the
 * Laravel test suite) is what proves the real boundary holds.
 */
describe('Authorization visibility (UX layer, not the security boundary)', () => {
  it('redirects a non-chairman user away from a chairman-only route', async () => {
    sessionStorage.setItem('m1_access_token', 'tok-technician');
    mockFetchSequence({
    });

    render(
      <MemoryRouter initialEntries={['/users']}>
        <AuthProvider>
          <SnackbarProvider>
            <Routes>
              <Route element={<ProtectedRoute roles={['chairman']} />}>
                <Route path="/users" element={<div>Users Page</div>} />
              </Route>
              <Route path="/403" element={<div>Forbidden Page</div>} />
            </Routes>
          </SnackbarProvider>
        </AuthProvider>
      </MemoryRouter>
    );

    await waitFor(() => expect(screen.getByText('Forbidden Page')).toBeInTheDocument());
    expect(screen.queryByText('Users Page')).not.toBeInTheDocument();
  });

  it('allows a chairman user through', async () => {
    sessionStorage.setItem('m1_access_token', 'tok-chairman');
    mockFetchSequence({
    });

    render(
      <MemoryRouter initialEntries={['/users']}>
        <AuthProvider>
          <SnackbarProvider>
            <Routes>
              <Route element={<ProtectedRoute roles={['chairman']} />}>
                <Route path="/users" element={<div>Users Page</div>} />
              </Route>
            </Routes>
          </SnackbarProvider>
        </AuthProvider>
      </MemoryRouter>
    );

    await waitFor(() => expect(screen.getByText('Users Page')).toBeInTheDocument());
  });
});
