import { apiRequest, apiRequestPaged } from '../../lib/apiClient';
import type {
  Asset, AssetCorrection, AssetLegacyNumber, AssetStatusEntry, ManualAssetStatus,
} from '../../lib/types';

// M2 Batch 3 — API-AST-01..09, exact paths.
export interface AssetListParams {
  page?: number; per_page?: number; category_id?: number; status?: string;
  site_id?: number; q?: string; sort?: string;
}

export const assetsApi = {
  list: (params: AssetListParams) => apiRequestPaged<Asset[]>('/assets', { query: { ...params } }),
  get: (id: number) => apiRequest<Asset>(`/assets/${id}`),
  create: (payload: {
    inventory_no: string; serial_no?: string | null; category_id: number; current_site_id: number;
    holder_text?: string | null; legacy_numbers?: string[];
  }) => apiRequest<Asset>('/assets', { method: 'POST', body: payload, idempotent: true }),
  update: (id: number, payload: { version: number; category_id?: number; holder_text?: string | null }) =>
    apiRequest<Asset>(`/assets/${id}`, { method: 'PATCH', body: payload }),
  changeStatus: (id: number, payload: { to_status: ManualAssetStatus; reason?: string | null; version: number }) =>
    apiRequest<{ asset: Pick<Asset, 'id' | 'status' | 'version'>; status_history_entry: AssetStatusEntry }>(
      `/assets/${id}/status-changes`, { method: 'POST', body: payload, idempotent: true }),
  correctIdentifier: (id: number, payload: { field_name: 'inventory_no' | 'serial_no'; new_value: string; reason: string }) =>
    apiRequest<{ asset: Partial<Asset>; correction: AssetCorrection }>(
      `/assets/${id}/identifier-corrections`, { method: 'POST', body: payload, idempotent: true }),
  statusHistory: (id: number, page = 1) => apiRequestPaged<AssetStatusEntry[]>(`/assets/${id}/status-history`, { query: { page } }),
  corrections: (id: number, page = 1) => apiRequestPaged<AssetCorrection[]>(`/assets/${id}/identifier-corrections`, { query: { page } }),
  legacyNumbers: (id: number, page = 1) => apiRequestPaged<AssetLegacyNumber[]>(`/assets/${id}/legacy-numbers`, { query: { page } }),
};
