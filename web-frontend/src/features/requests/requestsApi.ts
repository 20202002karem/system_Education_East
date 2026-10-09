import { apiRequest, apiRequestPaged } from '../../lib/apiClient';
import type {
  Priority, RequestAssignment, RequestCycle, RequestNote, RequestStatus, ServiceRequest, Proposal,
} from '../../lib/types';

// M3 Batch 3 — M3-API-001..016, exact paths. Every transition is a POST action (no PATCH, DD-M3-02).
export interface RequestListParams {
  page?: number; per_page?: number; q?: string; status?: RequestStatus | ''; origin_site_id?: number;
  priority?: Priority | ''; assignee_id?: number; sort?: string;
}

export const requestsApi = {
  list: (p: RequestListParams) => apiRequestPaged<ServiceRequest[]>('/requests', { query: { ...p } }),
  get: (id: number) => apiRequest<ServiceRequest>(`/requests/${id}`),
  create: (b: { origin_site_id: number; asset_id?: number | null; channel_id: number; description: string; suggested_priority?: Priority | null; requester_name?: string | null }) =>
    apiRequest<ServiceRequest>('/requests', { method: 'POST', body: b, idempotent: true }),
  triage: (id: number, b: { request_type_id: number; priority: Priority; version?: number }) =>
    apiRequest<ServiceRequest>(`/requests/${id}/triage`, { method: 'POST', body: b, idempotent: true }),
  assign: (id: number, b: { assignee_id: number; reason?: string | null; version?: number }) =>
    apiRequest<ServiceRequest & { assignment: RequestAssignment }>(`/requests/${id}/assignment`, { method: 'POST', body: b, idempotent: true }),
  start: (id: number, version?: number) => apiRequest<ServiceRequest>(`/requests/${id}/start`, { method: 'POST', body: { version }, idempotent: true }),
  hold: (id: number, b: { hold_reason?: string | null; version?: number }) =>
    apiRequest<ServiceRequest>(`/requests/${id}/hold`, { method: 'POST', body: b, idempotent: true }),
  resume: (id: number, version?: number) => apiRequest<ServiceRequest>(`/requests/${id}/resume`, { method: 'POST', body: { version }, idempotent: true }),
  close: (id: number, b: { closure_action: string; closure_result: string; closure_effort_minutes: number; version?: number }) =>
    apiRequest<ServiceRequest>(`/requests/${id}/close`, { method: 'POST', body: b, idempotent: true }),
  cancel: (id: number, b: { reason_category: string; reason_text: string; work_done_summary?: string | null; version?: number }) =>
    apiRequest<ServiceRequest>(`/requests/${id}/cancel`, { method: 'POST', body: b, idempotent: true }),
  reopen: (id: number, reason: string) =>
    apiRequest<ServiceRequest>(`/requests/${id}/reopen`, { method: 'POST', body: { reason }, idempotent: true }),
  cycles: (id: number, page = 1) => apiRequestPaged<RequestCycle[]>(`/requests/${id}/cycles`, { query: { page } }),
  assignments: (id: number, page = 1) => apiRequestPaged<RequestAssignment[]>(`/requests/${id}/assignments`, { query: { page } }),
  notes: (id: number, page = 1) => apiRequestPaged<RequestNote[]>(`/requests/${id}/notes`, { query: { page } }),
  addNote: (id: number, b: { visibility: 'internal' | 'external'; body: string }) =>
    apiRequest<RequestNote>(`/requests/${id}/notes`, { method: 'POST', body: b, idempotent: true }),
  propose: (id: number, b: { type: 'reassign' | 'cancel'; reason: string; work_done_summary?: string | null }) =>
    apiRequest<Proposal>(`/requests/${id}/proposals`, { method: 'POST', body: b, idempotent: true }),
};
