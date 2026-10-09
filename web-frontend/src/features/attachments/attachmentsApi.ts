import { apiRequest, apiRequestPaged } from '../../lib/apiClient';
import type { Attachment } from '../../lib/types';

// M3-API-030/031/037 — no replace, no delete (IN-01). Download = temporary signed link.
export const attachmentsApi = {
  list: (owner_type: Attachment['owner_type'], owner_id: number, page = 1) =>
    apiRequestPaged<Attachment[]>('/attachments', { query: { owner_type, owner_id, page } }),
  upload: (owner_type: Attachment['owner_type'], owner_id: number, file: File) => {
    const form = new FormData();
    form.append('owner_type', owner_type);
    form.append('owner_id', String(owner_id));
    form.append('file', file);
    return apiRequest<Attachment>('/attachments', { method: 'POST', body: form, idempotent: true });
  },
  downloadLink: (id: number) => apiRequest<{ url: string; expires_at: string }>(`/attachments/${id}/download`),
};
