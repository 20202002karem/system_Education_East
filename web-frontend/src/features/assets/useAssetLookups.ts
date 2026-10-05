import { useEffect, useState } from 'react';
import { apiRequest, apiRequestPaged } from '../../lib/apiClient';
import type { ReferenceItem, Site } from '../../lib/types';

/**
 * Names for category/site ids. M1 exposes these lists only to the chairman
 * (/reference/device-categories, /sites). For other roles the fetch is refused (403)
 * and the UI falls back to "#id" — logged in the M2 report as an OPEN DECISION
 * (no approved endpoint gives other roles these lists).
 */
export function useAssetLookups() {
  const [categories, setCategories] = useState<ReferenceItem[]>([]);
  const [sites, setSites] = useState<Site[]>([]);
  const [available, setAvailable] = useState(false);

  useEffect(() => {
    let alive = true;
    (async () => {
      try {
        const [c, s] = await Promise.all([
          apiRequest<ReferenceItem[]>('/reference/device-categories'),
          apiRequestPaged<Site[]>('/sites', { query: { per_page: 100, status: 'active' } }),
        ]);
        if (!alive) return;
        setCategories(c);
        setSites(s.data);
        setAvailable(true);
      } catch {
        /* non-chairman: names unavailable */
      }
    })();
    return () => { alive = false; };
  }, []);

  return {
    categories, sites, available,
    categoryName: (id: number) => categories.find((c) => c.id === id)?.name ?? `#${id}`,
    siteName: (id: number) => sites.find((s) => s.id === id)?.name_ar ?? `#${id}`,
  };
}
