import { apiRequestPaged } from '../../lib/apiClient';
import type { AuditChainCheck, AuditLogEntry } from '../../lib/types';

// Batch 3 §2.5 — read-only, chairman-only. No write route exists anywhere (IN-12).
export const auditApi = {
  listLog: (params: { entity_type?: string; entity_id?: string; actor_id?: string; from?: string; to?: string; page?: number; per_page?: number }) =>
    apiRequestPaged<AuditLogEntry[]>('/audit-log', { query: params }),
  listChainChecks: (params: { page?: number; per_page?: number }) =>
    apiRequestPaged<AuditChainCheck[]>('/audit/chain-checks', { query: params }),
};
