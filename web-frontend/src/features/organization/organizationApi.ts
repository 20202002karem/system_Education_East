import { apiRequest, apiRequestPaged } from '../../lib/apiClient';
import type { Site, SiteManager } from '../../lib/types';

// Batch 3 §2.3 — Sites & Site Managers, chairman-only (read AND write in M1).
export const organizationApi = {
  listSites: (params: { type?: string; status?: string; page?: number; per_page?: number }) =>
    apiRequestPaged<Site[]>('/sites', { query: params }),
  getSite: (id: number) => apiRequest<Site>(`/sites/${id}`),
  createSite: (payload: { type: string; code: string; name_ar: string }) =>
    apiRequest<Site>('/sites', { method: 'POST', body: payload, idempotent: true }),
  updateSite: (id: number, payload: Partial<{ type: string; code: string; name_ar: string }>) =>
    apiRequest<Site>(`/sites/${id}`, { method: 'PATCH', body: payload }),
  archiveSite: (id: number) => apiRequest<Site>(`/sites/${id}/archive`, { method: 'POST' }),

  listManagers: (siteId: number) => apiRequest<SiteManager[]>(`/sites/${siteId}/managers`),
  assignManager: (siteId: number, payload: { user_id: number; from: string }) =>
    apiRequest<SiteManager>(`/sites/${siteId}/managers`, { method: 'POST', body: payload, idempotent: true }),
  endManager: (siteManagerId: number) => apiRequest<SiteManager>(`/site-managers/${siteManagerId}/end`, { method: 'POST' }),
};
