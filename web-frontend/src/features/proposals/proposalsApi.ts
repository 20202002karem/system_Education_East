import { apiRequest, apiRequestPaged } from '../../lib/apiClient';
import type { Proposal } from '../../lib/types';

// M3-API-027..029
export const proposalsApi = {
  list: (p: { page?: number; per_page?: number; status?: string; subject_type?: string }) => apiRequestPaged<Proposal[]>('/proposals', { query: { ...p } }),
  accept: (id: number, b: { new_assignee_id?: number | null; decision_reason?: string | null }) =>
    apiRequest<Proposal>(`/proposals/${id}/accept`, { method: 'POST', body: b, idempotent: true }),
  reject: (id: number, decision_reason?: string | null) =>
    apiRequest<Proposal>(`/proposals/${id}/reject`, { method: 'POST', body: { decision_reason }, idempotent: true }),
};
