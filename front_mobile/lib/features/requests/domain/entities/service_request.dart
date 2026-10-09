import 'package:equatable/equatable.dart';

/// M3 request statuses (BR-M3 request cycle) — wire values verbatim from the API.
const requestStatusLabels = <String, String>{
  'new': 'جديد',
  'triaged': 'تم الفرز – بانتظار الإسناد',
  'assigned': 'مُسنَد',
  'in_progress': 'قيد التنفيذ',
  'held': 'معلّق',
  'reopened_pending_assignment': 'مُعاد فتحه – بانتظار الإسناد',
  'closed': 'مغلق',
  'cancelled': 'ملغى',
};

const cancelCategoryLabels = <String, String>{
  'duplicate': 'طلب مكرر',
  'resolved_itself': 'زالت المشكلة',
  'withdrawn': 'سحب مقدّم الطلب',
  'invalid': 'طلب غير صالح',
  'other': 'أخرى',
};

class LookupItem extends Equatable {
  const LookupItem(this.id, this.name);
  final int id;
  final String name;
  @override
  List<Object?> get props => [id, name];
}

class RequestCycleEntity extends Equatable {
  const RequestCycleEntity({required this.id, required this.cycleNo, required this.status, this.assigneeUserId, this.assigneeName, this.version});
  final int id;
  final int cycleNo;
  final String status;
  final int? assigneeUserId;
  final String? assigneeName;
  final int? version;

  factory RequestCycleEntity.fromJson(Map<String, dynamic> j) => RequestCycleEntity(
        id: j['id'] as int,
        cycleNo: (j['cycle_no'] as num?)?.toInt() ?? 1,
        status: j['status'] as String,
        assigneeUserId: j['assignee_user_id'] as int?,
        assigneeName: j['assignee_name'] as String?,
        version: (j['version'] as num?)?.toInt(),
      );

  @override
  List<Object?> get props => [id, cycleNo, status, assigneeUserId, assigneeName, version];
}

class ServiceRequestEntity extends Equatable {
  const ServiceRequestEntity({
    required this.id,
    required this.refNo,
    required this.originSiteId,
    this.originSiteName,
    required this.description,
    this.priority,
    this.suggestedPriority,
    this.requesterUserId,
    this.requesterName,
    this.status,
    this.cycle,
    this.createdAt,
  });

  final int id;
  final String refNo;
  final int originSiteId;
  final String? originSiteName;
  final String description;
  final String? priority;
  final String? suggestedPriority;
  final int? requesterUserId;
  final String? requesterName;
  final String? status;
  final RequestCycleEntity? cycle;
  final String? createdAt;

  String get statusLabel => requestStatusLabels[status] ?? (status ?? '—');

  factory ServiceRequestEntity.fromJson(Map<String, dynamic> j) => ServiceRequestEntity(
        id: j['id'] as int,
        refNo: j['ref_no'] as String,
        originSiteId: j['origin_site_id'] as int,
        originSiteName: j['origin_site_name'] as String?,
        description: (j['description'] as String?) ?? '',
        priority: j['priority'] as String?,
        suggestedPriority: j['suggested_priority'] as String?,
        requesterUserId: j['requester_user_id'] as int?,
        requesterName: j['requester_name'] as String?,
        status: j['status'] as String?,
        cycle: j['cycle'] is Map<String, dynamic> ? RequestCycleEntity.fromJson(j['cycle'] as Map<String, dynamic>) : null,
        createdAt: j['created_at'] as String?,
      );

  @override
  List<Object?> get props => [id, refNo, status, cycle, priority, description];
}

class RequestNoteEntity extends Equatable {
  const RequestNoteEntity({required this.id, required this.visibility, required this.body, this.createdAt});
  final int id;
  final String visibility;
  final String body;
  final String? createdAt;

  factory RequestNoteEntity.fromJson(Map<String, dynamic> j) => RequestNoteEntity(
        id: j['id'] as int,
        visibility: (j['visibility'] as String?) ?? 'internal',
        body: (j['body'] as String?) ?? '',
        createdAt: j['created_at'] as String?,
      );

  @override
  List<Object?> get props => [id, visibility, body];
}
