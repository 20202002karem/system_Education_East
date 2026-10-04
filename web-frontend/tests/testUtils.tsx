import type { ReactElement } from 'react';
import { vi } from 'vitest';
import { render } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { AuthProvider } from '../src/auth/AuthContext';
import { SnackbarProvider } from '../src/components/Snackbar';

export function renderWithProviders(ui: ReactElement, { route = '/' }: { route?: string } = {}) {
  return render(
    <MemoryRouter initialEntries={[route]}>
      <AuthProvider>
        <SnackbarProvider>{ui}</SnackbarProvider>
      </AuthProvider>
    </MemoryRouter>
  );
}

/** Minimal fetch mock keyed by "METHOD path" so tests stay close to Batch 3 contracts. */
export function mockFetchSequence(handlers: Record<string, { status: number; body: unknown }>) {
  const calls: { url: string; init?: RequestInit }[] = [];
  global.fetch = vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
    const url = typeof input === 'string' ? input : input.toString();
    calls.push({ url, init });
    const method = init?.method ?? 'GET';
    const path = new URL(url).pathname.replace(/^\/api\/v1/, '');
    const key = `${method} ${path}`;
    const handler = handlers[key];
    if (!handler) {
      throw new Error(`No mock handler for ${key}`);
    }
    return new Response(JSON.stringify(handler.body), { status: handler.status, headers: { 'Content-Type': 'application/json' } });
  }) as typeof fetch;
  return calls;
}
