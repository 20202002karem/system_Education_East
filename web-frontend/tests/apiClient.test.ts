import { describe, expect, it, beforeEach, vi } from 'vitest';
import { apiRequest, ApiError, setToken } from '../src/lib/apiClient';
import { mockFetchSequence } from './testUtils';

describe('apiClient', () => {
  beforeEach(() => {
    sessionStorage.clear();
  });

  it('unwraps {data} envelope on success', async () => {
    mockFetchSequence({ 'GET /auth/me': { status: 200, body: { data: { id: 1, name: 'Test' } } } });
    setToken('tok');
    const result = await apiRequest<{ id: number; name: string }>('/auth/me');
    expect(result).toEqual({ id: 1, name: 'Test' });
  });

  it('maps {error} envelope to ApiError with code/message/fields', async () => {
    mockFetchSequence({
      'POST /users': { status: 422, body: { error: { code: 'validation_failed', message: 'بيانات غير صالحة', fields: { name: ['مطلوب'] } } } },
    });
    await expect(apiRequest('/users', { method: 'POST', body: {} })).rejects.toMatchObject({
      status: 422,
      code: 'validation_failed',
      fields: { name: ['مطلوب'] },
    });
  });

  it('falls back to a generic Arabic message per status when body has no message', async () => {
    global.fetch = vi.fn(async () => new Response('', { status: 500 })) as typeof fetch;
    await expect(apiRequest('/anything')).rejects.toMatchObject({ status: 500 });
    try {
      await apiRequest('/anything');
    } catch (e) {
      expect((e as ApiError).message).toContain('خطأ');
    }
  });

  it('sends Idempotency-Key header when idempotent:true', async () => {
    const calls = mockFetchSequence({ 'POST /sites': { status: 201, body: { data: { id: 1 } } } });
    await apiRequest('/sites', { method: 'POST', body: { code: 'A' }, idempotent: true });
    const headers = calls[0].init?.headers as Record<string, string>;
    expect(headers['Idempotency-Key']).toBeTruthy();
  });
});
