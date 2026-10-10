import { apiRequest, apiRequestPaged } from '../../lib/apiClient';
import type { Draft, DraftFormType } from '../../lib/types';

// M3-API-032..034 — the owner always comes from the token. DELETE is the only physical delete in the API.
export const draftsApi = {
  list: (p: { form_type?: DraftFormType; page?: number; per_page?: number } = {}) => apiRequestPaged<Draft[]>('/drafts', { query: { ...p } }),
  put: (b: { form_type: DraftFormType; form_key?: string | null; payload: Record<string, unknown> }) =>
    apiRequest<Draft>('/drafts', { method: 'PUT', body: b }),
  remove: (id: number) => apiRequest<void>(`/drafts/${id}`, { method: 'DELETE' }),
};
