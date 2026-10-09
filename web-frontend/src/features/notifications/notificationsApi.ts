import { apiRequest, apiRequestPaged } from '../../lib/apiClient';
import type { AppNotification } from '../../lib/types';

// M3-API-035/036 — internal notifications only (no email/push in M3).
export const notificationsApi = {
  list: (p: { page?: number; per_page?: number; read?: 'true' | 'false' }) => apiRequestPaged<AppNotification[]>('/notifications', { query: { ...p } }),
  markRead: (id: number) => apiRequest<AppNotification>(`/notifications/${id}/read`, { method: 'POST' }),
};
