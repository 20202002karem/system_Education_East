import { useEffect, useState } from 'react';
import { apiRequest, apiRequestPaged } from '../../lib/apiClient';
import type { AssignableUser, ReferenceItem, Site, User } from '../../lib/types';

/** Read-only lookups for M3 forms (BASELINE CHANGE: reference lists/sites/users are now readable per role scope). */
export function useRequestLookups(withUsers = false) {
  const [sites, setSites] = useState<Site[]>([]);
  const [channels, setChannels] = useState<ReferenceItem[]>([]);
  const [requestTypes, setRequestTypes] = useState<ReferenceItem[]>([]);
  const [taskTypes, setTaskTypes] = useState<ReferenceItem[]>([]);
  const [users, setUsers] = useState<AssignableUser[]>([]);

  useEffect(() => {
    let alive = true;
    const safe = async <T,>(fn: () => Promise<T>, set: (v: T) => void) => {
      try { const v = await fn(); if (alive) set(v); } catch { /* names fall back to #id */ }
    };
    void safe(() => apiRequestPaged<Site[]>('/sites', { query: { per_page: 100 } }), (r) => setSites(r.data));
    void safe(() => apiRequest<ReferenceItem[]>('/reference/intake-channels'), setChannels);
    void safe(() => apiRequest<ReferenceItem[]>('/reference/request-types'), setRequestTypes);
    void safe(() => apiRequest<ReferenceItem[]>('/reference/task-types'), setTaskTypes);
    if (withUsers) {
      void safe(() => apiRequestPaged<User[]>('/users', { query: { per_page: 100, status: 'active' } }),
        (r) => setUsers(r.data.map((u) => ({ id: u.id, name: u.name, role: u.role }))));
    }
    return () => { alive = false; };
  }, [withUsers]);

  return {
    sites, channels, requestTypes, taskTypes, users,
    siteName: (id: number | null) => (id == null ? '—' : sites.find((s) => s.id === id)?.name_ar ?? `#${id}`),
    userName: (id: number | null) => (id == null ? '—' : users.find((u) => u.id === id)?.name ?? `#${id}`),
    typeName: (id: number | null) => (id == null ? '—' : requestTypes.find((t) => t.id === id)?.name ?? `#${id}`),
  };
}
