import 'package:equatable/equatable.dart';

/// Batch 2 §B8 — read-only on the client, matching the backend's read-only
/// route set (IN-12: no write route exists anywhere for audit_log).
class AuditLogEntryEntity extends Equatable {
  const AuditLogEntryEntity({
    required this.seq,
    required this.occurredAt,
    required this.actorId,
    required this.action,
    required this.entityType,
    required this.entityId,
    this.reason,
    required this.source,
    this.ip,
  });

  final int seq;
  final DateTime occurredAt;
  final int actorId;
  final String action;
  final String entityType;
  final String entityId;
  final String? reason;
  final String source;
  final String? ip;

  @override
  List<Object?> get props => [seq, occurredAt, actorId, action, entityType, entityId, reason, source, ip];
}
