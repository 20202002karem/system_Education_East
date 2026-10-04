import { apiRequest } from '../../lib/apiClient';
import type { ReferenceItem, ReferenceResource, Setting } from '../../lib/types';

// Batch 3 §2.4 — Settings & Reference Data, chairman-only.
export const settingsApi = {
  list: () => apiRequest<Setting[]>('/settings'),
  update: (key: string, value: string) => apiRequest<Setting>(`/settings/${key}`, { method: 'PATCH', body: { value } }),

  listReference: (resource: ReferenceResource) => apiRequest<ReferenceItem[]>(`/reference/${resource}`),
  createReference: (resource: ReferenceResource, payload: Partial<ReferenceItem>) =>
    apiRequest<ReferenceItem>(`/reference/${resource}`, { method: 'POST', body: payload, idempotent: true }),
  updateReference: (resource: ReferenceResource, id: number, payload: Partial<ReferenceItem>) =>
    apiRequest<ReferenceItem>(`/reference/${resource}/${id}`, { method: 'PATCH', body: payload }),
};
