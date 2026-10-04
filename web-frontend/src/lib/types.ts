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
