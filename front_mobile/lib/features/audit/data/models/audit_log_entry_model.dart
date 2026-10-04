import '../../domain/entities/audit_log_entry.dart';

class AuditLogEntryModel {
  static AuditLogEntryEntity fromJson(Map<String, dynamic> json) {
    return AuditLogEntryEntity(
      seq: json['seq'] as int,
      occurredAt: DateTime.parse(json['occurred_at'] as String),
      actorId: json['actor_id'] as int,
      action: json['action'] as String,
      entityType: json['entity_type'] as String,
      entityId: json['entity_id'].toString(),
      reason: json['reason'] as String?,
      source: json['source'] as String? ?? 'system',
      ip: json['ip'] as String?,
    );
  }
}
