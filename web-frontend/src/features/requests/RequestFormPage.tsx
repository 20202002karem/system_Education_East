import { useEffect, useRef, useState, type FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card } from '../../components/Card';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { Select } from '../../components/Select';
import { useSnackbar } from '../../components/Snackbar';
import { ApiError } from '../../lib/apiClient';
import { useAuth } from '../../auth/AuthContext';
import type { Priority } from '../../lib/types';
import { requestsApi } from './requestsApi';
import { draftsApi } from '../drafts/draftsApi';
import { priorityOptions } from './requestLabels';
import { useRequestLookups } from './useRequestLookups';

/** M3-UC-001. Channel is mandatory (BR-M3-01); on a failed submit the form is kept as a server draft (MD-15ب). */
export function RequestFormPage() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const { notify } = useSnackbar();
  const lookups = useRequestLookups();
  const isSecretary = user?.role === 'secretary';
  const [siteId, setSiteId] = useState('');
  const [assetId, setAssetId] = useState('');
  const [channelId, setChannelId] = useState('');
  const [description, setDescription] = useState('');
  const [priority, setPriority] = useState('');
  const [requesterName, setRequesterName] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [fields, setFields] = useState<Record<string, string[]>>({});
  const [submitting, setSubmitting] = useState(false);
  const restored = useRef(false);

  // school_manager has one site: select it automatically (M3-UC-001 Main Flow)
  useEffect(() => {
    if (!isSecretary && lookups.sites.length === 1 && !siteId) setSiteId(String(lookups.sites[0].id));
  }, [lookups.sites, isSecretary, siteId]);

  // restore a saved draft once
  useEffect(() => {
    if (restored.current) return;
    restored.current = true;
    draftsApi.list({ form_type: 'request_create' }).then((r) => {
      const p = r.data.find((d) => d.form_key === null)?.payload as Record<string, string> | undefined;
      if (!p) return;
      setSiteId(p.siteId ?? ''); setAssetId(p.assetId ?? ''); setChannelId(p.channelId ?? '');
      setDescription(p.description ?? ''); setPriority(p.priority ?? ''); setRequesterName(p.requesterName ?? '');
      notify('تم استرجاع مسودة محفوظة', 'info');
    }).catch(() => undefined);
  }, [notify]);

  async function saveDraft() {
    try {
      await draftsApi.put({ form_type: 'request_create', form_key: null, payload: { siteId, assetId, channelId, description, priority, requesterName } });
      notify('تم حفظ المسودة', 'success');
    } catch { notify('تعذّر حفظ المسودة', 'error'); }
  }

  async function submit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true); setError(null); setFields({});
    try {
      const created = await requestsApi.create({
        origin_site_id: Number(siteId), channel_id: Number(channelId), description,
        asset_id: assetId ? Number(assetId) : null,
        suggested_priority: (priority || null) as Priority | null,
        requester_name: isSecretary ? requesterName : null,
      });
      // the draft is consumed by a successful submit (MD-15ب)
      draftsApi.list({ form_type: 'request_create' }).then((r) => r.data.filter((d) => d.form_key === null).forEach((d) => draftsApi.remove(d.id))).catch(() => undefined);
      notify(`تم إنشاء الطلب ${created.ref_no}`, 'success');
      navigate(`/requests/${created.id}`);
    } catch (err) {
      if (err instanceof ApiError) { setError(err.message); setFields(err.fields ?? {}); if (err.status === 0 || err.status >= 500) void saveDraft(); }
      else setError('تعذّر إرسال الطلب');
    } finally { setSubmitting(false); }
  }

  const err = (n: string) => fields[n]?.[0];
  return (
    <Card title="طلب دعم جديد">
      <form onSubmit={submit} style={{ maxWidth: 560 }}>
        {error && <div role="alert" style={{ background: 'var(--color-danger-bg)', color: 'var(--color-danger)', padding: 'var(--space-3)', borderRadius: 'var(--radius-sm)', marginBottom: 'var(--space-4)' }}>{error}</div>}
        <Select label="الموقع *" value={siteId} required onChange={(e) => setSiteId(e.target.value)} error={err('origin_site_id')}
          options={[{ value: '', label: '— اختر —' }, ...lookups.sites.map((s) => ({ value: String(s.id), label: s.name_ar }))]} />
        <Input label="رقم الجهاز (اختياري، معرّف النظام)" type="number" min={1} value={assetId} onChange={(e) => setAssetId(e.target.value)} error={err('asset_id')} />
        <Select label="قناة الاستقبال *" value={channelId} required onChange={(e) => setChannelId(e.target.value)} error={err('channel_id')}
          options={[{ value: '', label: '— اختر —' }, ...lookups.channels.filter((c) => c.is_active !== false).map((c) => ({ value: String(c.id), label: c.name }))]} />
        {isSecretary && <Input label="اسم مقدّم الطلب *" value={requesterName} required maxLength={150} onChange={(e) => setRequesterName(e.target.value)} error={err('requester_name')} />}
        <div style={{ marginBottom: 'var(--space-4)' }}>
          <label htmlFor="req-desc" style={{ display: 'block', marginBottom: 'var(--space-1)', fontSize: 'var(--font-size-sm)', color: 'var(--color-text-muted)' }}>وصف المشكلة *</label>
          <textarea id="req-desc" rows={5} required value={description} onChange={(e) => setDescription(e.target.value)}
            style={{ width: '100%', padding: 'var(--space-2) var(--space-3)', borderRadius: 'var(--radius-sm)', border: `1px solid ${err('description') ? 'var(--color-danger)' : 'var(--color-border)'}`, font: 'inherit' }} />
          {err('description') && <p style={{ color: 'var(--color-danger)', fontSize: 'var(--font-size-sm)' }}>{err('description')}</p>}
        </div>
        <Select label="الأولوية المقترحة (استشارية)" value={priority} onChange={(e) => setPriority(e.target.value)} options={[{ value: '', label: '— بدون —' }, ...priorityOptions]} error={err('suggested_priority')} />
        <div style={{ display: 'flex', gap: 'var(--space-3)', flexWrap: 'wrap' }}>
          <Button type="submit" loading={submitting}>إرسال الطلب</Button>
          <Button type="button" variant="secondary" onClick={saveDraft} disabled={submitting}>حفظ كمسودة</Button>
          <Button type="button" variant="ghost" onClick={() => navigate('/requests')}>رجوع</Button>
        </div>
      </form>
    </Card>
  );
}
