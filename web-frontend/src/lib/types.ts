// Domain types mirroring M1 Batch 2 (ERD) / Batch 3 (API Contracts) exactly.
// Shared between every feature module so Web (and, structurally, Flutter)
// speak the same M1 domain model (instructions §9).

export type Role = 'school_manager' | 'chairman' | 'secretary' | 'engineer' | 'technician';
export type ViewScope = 'own_site' | 'sites' | 'all' | 'assigned_only';
export type UserStatus = 'active' | 'disabled';
export type SiteType = 'school' | 'department' | 'warehouse';
export type SiteStatus = 'active' | 'archived';
export type PermissionKey = 'initiate_transfer' | 'initiate_decommission' | 'edit_assets';

export interface User {
  id: number;
  name: string;
  email: string;
  role: Role;
  view_scope: ViewScope;
  status: UserStatus;
  specialization?: string | null;
  created_at?: string;
}

export interface PermissionGrant {
  id: number;
  user_id: number;
  permission_key: PermissionKey;
  granted_by: number;
  granted_at: string;
  revoked_by?: number | null;
  revoked_at?: string | null;
  reason?: string | null;
}

export interface Site {
  id: number;
  type: SiteType;
  code: string;
  name_ar: string;
  status: SiteStatus;
  created_at?: string;
}

export interface SiteManager {
  id: number;
  site_id: number;
  user_id: number;
  from: string;
  to: string | null;
}

export interface Setting {
  key: string;
  value: string | null;
  updated_by?: number | null;
}

export interface ReferenceItem {
  id: number;
  name: string;
  is_active?: boolean;
  is_administrative?: boolean;
  secretary_assignable?: boolean;
}

export type ReferenceResource = 'device-categories' | 'request-types' | 'task-types' | 'intake-channels';

export interface AuditLogEntry {
  seq: number;
  occurred_at: string;
  actor_id: number;
  action: string;
  entity_type: string;
  entity_id: string;
  before: Record<string, unknown> | null;
  after: Record<string, unknown> | null;
  reason: string | null;
  source: 'web' | 'mobile' | 'system';
  ip: string | null;
  flags: string[] | null;
}

export interface AuditChainCheck {
  id: number;
  run_at: string;
  from_seq: number;
  to_seq: number;
  result: 'ok' | 'broken';
}

export interface PageMeta {
  page: number;
  per_page: number;
  total: number;
}

export interface ApiListResponse<T> {
  data: T[];
  meta: PageMeta;
}

export interface ApiItemResponse<T> {
  data: T;
}

export interface ApiErrorBody {
  error: {
    code: string;
    message: string;
    fields?: Record<string, string[]>;
  };
}

// ---- M2 Assets (M2 Batch 2 data dictionary / Batch 3 contracts) ----
export type AssetStatus = 'working' | 'under_maintenance' | 'broken' | 'stored' | 'in_transfer' | 'decommissioned';
export type ManualAssetStatus = 'working' | 'under_maintenance' | 'broken' | 'stored';

export interface Asset {
  id: number;
  inventory_no: string;
  serial_no: string | null;
  category_id: number;
  current_site_id: number;
  status: AssetStatus;
  holder_text: string | null;
  version: number;
  created_at: string;
  updated_at: string;
}

export interface AssetStatusEntry {
  id: number;
  from_status: AssetStatus;
  to_status: AssetStatus;
  changed_by: number;
  reason: string | null;
  changed_at: string;
}

export interface AssetCorrection {
  id: number;
  field_name: 'inventory_no' | 'serial_no';
  old_value: string;
  new_value: string;
  corrected_by: number;
  reason: string;
  corrected_at: string;
}

export interface AssetLegacyNumber {
  id: number;
  legacy_number: string;
  source: string | null;
  added_by: number;
  added_at: string;
}
