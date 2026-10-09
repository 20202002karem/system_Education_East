import { apiRequest, apiRequestPaged } from '../../lib/apiClient';
import type { Proposal, Task, TaskStatus } from '../../lib/types';

// M3 Batch 3 — M3-API-017..026.
export const tasksApi = {
  list: (p: { page?: number; per_page?: number; status?: TaskStatus | ''; assignee_id?: number; request_cycle_id?: number; q?: string; sort?: string }) =>
    apiRequestPaged<Task[]>('/tasks', { query: { ...p } }),
  get: (id: number) => apiRequest<Task>(`/tasks/${id}`),
  create: (b: { task_type_id: number; title: string; description?: string | null; request_cycle_id?: number | null; asset_id?: number | null; site_id?: number | null; due_at?: string | null }) =>
    apiRequest<Task>('/tasks', { method: 'POST', body: b, idempotent: true }),
  assign: (id: number, b: { assignee_id: number; reason?: string | null; version?: number }) =>
    apiRequest<Task>(`/tasks/${id}/assignment`, { method: 'POST', body: b, idempotent: true }),
  start: (id: number, version?: number) => apiRequest<Task>(`/tasks/${id}/start`, { method: 'POST', body: { version }, idempotent: true }),
  hold: (id: number, version?: number) => apiRequest<Task>(`/tasks/${id}/hold`, { method: 'POST', body: { version }, idempotent: true }),
  resume: (id: number, version?: number) => apiRequest<Task>(`/tasks/${id}/resume`, { method: 'POST', body: { version }, idempotent: true }),
  complete: (id: number, b: { result_summary: string; version?: number }) =>
    apiRequest<Task>(`/tasks/${id}/complete`, { method: 'POST', body: b, idempotent: true }),
  cancel: (id: number, b: { reason: string; work_done_summary?: string | null; version?: number }) =>
    apiRequest<Task>(`/tasks/${id}/cancel`, { method: 'POST', body: b, idempotent: true }),
  propose: (id: number, b: { type: 'reassign' | 'cancel'; reason: string; work_done_summary?: string | null }) =>
    apiRequest<Proposal>(`/tasks/${id}/proposals`, { method: 'POST', body: b, idempotent: true }),
};
