import { apiRequest, apiRequestPaged } from '../../lib/apiClient';
import type { PermissionGrant, Site, User } from '../../lib/types';

// Batch 3 §2.2 — verbatim endpoint set, chairman-only.
export const usersApi = {
  list: (params: { role?: string; status?: string; page?: number; per_page?: number }) =>
    apiRequestPaged<User[]>('/users', { query: params }),

  get: (id: number) => apiRequest<User>(`/users/${id}`),

  create: (payload: {
    name: string;
    login_identifier: string;
    role: string;
    view_scope: string;
    specialization?: string;
    site_scope_ids?: number[];
  }) => apiRequest<User>('/users', { method: 'POST', body: payload, idempotent: true }),

  update: (id: number, payload: Partial<{ name: string; role: string; view_scope: string; specialization: string | null; site_scope_ids: number[] }>) =>
    apiRequest<User>(`/users/${id}`, { method: 'PATCH', body: payload }),

  disable: (id: number) => apiRequest<User>(`/users/${id}/disable`, { method: 'POST' }),
  enable: (id: number) => apiRequest<User>(`/users/${id}/enable`, { method: 'POST' }),

  resetPassword: (id: number, password: string) =>
    apiRequest<void>(`/users/${id}/reset-password`, { method: 'POST', body: { password } }),

  terminateSessions: (id: number) => apiRequest<void>(`/users/${id}/sessions/terminate`, { method: 'POST' }),

  permissionGrants: (id: number) => apiRequest<PermissionGrant[]>(`/users/${id}/permission-grants`),
  grantPermission: (id: number, payload: { permission_key: string; reason?: string }) =>
    apiRequest<PermissionGrant>(`/users/${id}/permission-grants`, { method: 'POST', body: payload, idempotent: true }),
  revokePermission: (grantId: number, reason?: string) =>
    apiRequest<PermissionGrant>(`/permission-grants/${grantId}/revoke`, { method: 'POST', body: { reason } }),

  siteScopes: (id: number) => apiRequest<{ site_id: number; site: Site }[]>(`/users/${id}/site-scopes`),
  replaceSiteScopes: (id: number, siteIds: number[]) =>
    apiRequest<{ site_id: number; site: Site }[]>(`/users/${id}/site-scopes`, { method: 'PUT', body: { site_ids: siteIds } }),
};
