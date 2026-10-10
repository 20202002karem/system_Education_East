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

// ---- M3 Requests & Tasks (M3 Batch 2 data dictionary / Batch 3 contracts) ----
export type RequestStatus =
  | 'new' | 'triaged' | 'assigned' | 'in_progress' | 'held' | 'reopened_pending_assignment' | 'closed' | 'cancelled';
export type Priority = 'emergency' | 'high' | 'normal' | 'low';
export type TaskStatus = 'new' | 'assigned' | 'in_progress' | 'held' | 'completed' | 'cancelled';

export interface RequestCycle {
  id: number;
  request_id: number;
  cycle_no: number;
  status: RequestStatus;
  assignee_user_id: number | null;
  assignee_name?: string | null;
  started_at: string | null;
  closed_at: string | null;
  closure_action: string | null;
  closure_result: string | null;
  closure_effort_minutes: number | null;
  hold_reason: string | null;
  version: number;
  created_at: string;
  updated_at: string;
}

export interface RequestCancellationInfo {
  reason_category: string;
  reason_text: string;
  work_done_summary: string | null;
  cancelled_by: number;
  stage: 'before_assignment' | 'after_start';
  cancelled_at: string;
}

export interface ServiceRequest {
  id: number;
  ref_no: string;
  origin_site_id: number;
  origin_site_name?: string | null;
  asset_id: number | null;
  requester_user_id: number | null;
  requester_name: string | null;
  registered_by_user_id: number;
  channel_id: number;
  request_type_id: number | null;
  suggested_priority: Priority | null;
  priority: Priority | null;
  description: string;
  current_cycle_no: number;
  reopen_count: number;
  status: RequestStatus | null;
  cycle: RequestCycle | null;
  cancellation: RequestCancellationInfo | null;
  created_at: string;
  updated_at: string;
}

export interface RequestAssignment {
  id: number; cycle_id: number; assignee_id: number; assigned_by: number; reason: string | null; from: string; to: string | null;
}

export interface RequestNote {
  id: number; cycle_id: number; author_id: number; author_name?: string | null; visibility: 'internal' | 'external'; body: string; created_at: string;
}

export interface Task {
  id: number;
  ref_no: string;
  task_type_id: number;
  title: string;
  description: string | null;
  request_cycle_id: number | null;
  asset_id: number | null;
  site_id: number | null;
  assignee_id: number | null;
  status: TaskStatus;
  due_at: string | null;
  result_summary: string | null;
  created_by: number;
  version: number;
  created_at: string;
  updated_at: string;
}

export interface Proposal {
  id: number;
  type: 'reassign' | 'cancel';
  subject_type: 'request' | 'task';
  subject_id: number;
  proposer_id: number;
  reason: string;
  work_done_summary: string | null;
  status: 'pending' | 'accepted' | 'rejected';
  decided_by: number | null;
  decided_at: string | null;
  created_at: string;
}

export interface Attachment {
  id: number;
  owner_type: 'request' | 'request_note' | 'task';
  owner_id: number;
  uploaded_by: number;
  sha256: string;
  size_bytes: number;
  mime_type: string;
  original_filename: string;
  created_at: string;
}

export type DraftFormType = 'request_create' | 'note' | 'closure' | 'cancellation' | 'proposal';
export interface Draft {
  id: number; form_type: DraftFormType; form_key: string | null; payload: Record<string, unknown>; updated_at: string;
}

export interface AppNotification {
  id: number; event_type: string; source_type: 'request' | 'task'; source_id: number; message: string; read_at: string | null; created_at: string;
}

export interface AssignableUser { id: number; name: string; role: Role }
