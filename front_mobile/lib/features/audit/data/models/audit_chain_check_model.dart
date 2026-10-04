import '../../domain/entities/audit_chain_check.dart';

class AuditChainCheckModel {
  static AuditChainCheckEntity fromJson(Map<String, dynamic> json) {
    return AuditChainCheckEntity(
      id: json['id'] as int,
      runAt: DateTime.parse(json['run_at'] as String),
      fromSeq: json['from_seq'] as int,
      toSeq: json['to_seq'] as int,
      result: json['result'] as String,
    );
  }
}
