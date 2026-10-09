import type { ApiErrorBody } from './types';

// Batch 3 §1 — base path /api/v1. VITE_API_BASE_URL points at the host+/api,
// this file appends /v1 so every call site just writes '/users', '/sites', etc.
const BASE_URL = `${import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api'}/v1`;

const TOKEN_STORAGE_KEY = 'm1_access_token';

// --- Token storage -----------------------------------------------------
// sessionStorage (not localStorage) so the token doesn't outlive the browser
// tab/session by default, matching the backend's own 30-min-inactivity /
// absolute-TTL session model rather than persisting indefinitely on disk.
// NEVER log this value anywhere (instructions §10/§16).
export function getToken(): string | null {
  return sessionStorage.getItem(TOKEN_STORAGE_KEY);
}
export function setToken(token: string): void {
  sessionStorage.setItem(TOKEN_STORAGE_KEY, token);
}
export function clearToken(): void {
  sessionStorage.removeItem(TOKEN_STORAGE_KEY);
}

// --- Idempotency-Key -----------------------------------------------------
// Required on every resource-creating POST (Batch 3 §1/§6).
function newIdempotencyKey(): string {
  if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
    return crypto.randomUUID();
  }
  return `idem-${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

// --- Typed API error -----------------------------------------------------
export class ApiError extends Error {
  constructor(
    public status: number,
    public code: string,
    message: string,
    public fields?: Record<string, string[]>
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

/** Human-readable Arabic fallback per status, used when the body has no message (network/5xx). */
function fallbackMessage(status: number): string {
  switch (status) {
    case 401: return 'غير مُصادَق، يرجى تسجيل الدخول مجددًا';
    case 403: return 'لا صلاحية لتنفيذ هذا الإجراء';
    case 404: return 'المورد غير موجود';
    case 409: return 'تعارض في البيانات';
    case 422: return 'بيانات غير صالحة';
    case 423: return 'الحساب مقفل مؤقتاً';
    case 429: return 'عدد كبير جدًا من المحاولات، حاول لاحقًا';
    default: return status >= 500 ? 'حدث خطأ في الخادم، حاول لاحقًا' : 'حدث خطأ غير متوقع';
  }
}

interface RequestOptions {
  method?: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE';
  body?: unknown;
  query?: Record<string, string | number | undefined>;
  idempotent?: boolean; // adds Idempotency-Key header
  signal?: AbortSignal;
}

let onUnauthorized: (() => void) | null = null;
/** Wired once by AuthContext so any 401 anywhere redirects to /login (instructions §6). */
export function registerUnauthorizedHandler(fn: () => void): void {
  onUnauthorized = fn;
}

export async function apiRequest<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const { method = 'GET', body, query, idempotent, signal } = options;

  const url = new URL(`${BASE_URL}${path}`);
  if (query) {
    Object.entries(query).forEach(([k, v]) => {
      if (v !== undefined && v !== '') url.searchParams.set(k, String(v));
    });
  }

  const headers: Record<string, string> = { Accept: 'application/json' };
  const isForm = typeof FormData !== 'undefined' && body instanceof FormData;
  if (body !== undefined && !isForm) headers['Content-Type'] = 'application/json'; // multipart sets its own boundary
  const token = getToken();
  if (token) headers['Authorization'] = `Bearer ${token}`;
  if (idempotent) headers['Idempotency-Key'] = newIdempotencyKey();

  let response: Response;
  try {
    response = await fetch(url.toString(), {
      method,
      headers,
      body: body === undefined ? undefined : isForm ? (body as FormData) : JSON.stringify(body),
      signal,
    });
  } catch {
    // Network failure — never logged with request body (which may contain a password).
    throw new ApiError(0, 'network_error', 'تعذّر الاتصال بالخادم، تحقّق من الاتصال بالإنترنت');
  }

  if (response.status === 204) {
    return undefined as T;
  }

  let payload: unknown = null;
  try {
    payload = await response.json();
  } catch {
    // No JSON body (e.g. some 5xx from an upstream proxy) — fall through to status-based message.
  }

  if (!response.ok) {
    const errBody = payload as ApiErrorBody | null;
    const code = errBody?.error?.code ?? `http_${response.status}`;
    const message = errBody?.error?.message ?? fallbackMessage(response.status);
    const fields = errBody?.error?.fields;

    if (response.status === 401 && onUnauthorized) {
      onUnauthorized();
    }

    throw new ApiError(response.status, code, message, fields);
  }

  return (payload as { data: T })?.data ?? (payload as T);
}

/** For endpoints returning {data, meta} — callers that need pagination use this instead of apiRequest. */
export async function apiRequestPaged<T>(
  path: string,
  options: RequestOptions = {}
): Promise<{ data: T; meta: { page: number; per_page: number; total: number } }> {
  const { method = 'GET', query, signal } = options;
  const url = new URL(`${BASE_URL}${path}`);
  if (query) {
    Object.entries(query).forEach(([k, v]) => {
      if (v !== undefined && v !== '') url.searchParams.set(k, String(v));
    });
  }
  const headers: Record<string, string> = { Accept: 'application/json' };
  const token = getToken();
  if (token) headers['Authorization'] = `Bearer ${token}`;

  const response = await fetch(url.toString(), { method, headers, signal });
  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    const errBody = payload as ApiErrorBody | null;
    if (response.status === 401 && onUnauthorized) onUnauthorized();
    throw new ApiError(
      response.status,
      errBody?.error?.code ?? `http_${response.status}`,
      errBody?.error?.message ?? fallbackMessage(response.status),
      errBody?.error?.fields
    );
  }

  return payload as { data: T; meta: { page: number; per_page: number; total: number } };
}
