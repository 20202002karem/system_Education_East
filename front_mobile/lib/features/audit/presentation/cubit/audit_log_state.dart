import 'package:equatable/equatable.dart';
import '../../../../core/utils/paginated.dart';
import '../../domain/entities/audit_log_entry.dart';

sealed class AuditLogState extends Equatable {
  const AuditLogState();
  @override
  List<Object?> get props => [];
}

class AuditLogInitial extends AuditLogState { const AuditLogInitial(); }
class AuditLogLoading extends AuditLogState { const AuditLogLoading(); }

class AuditLogSuccess extends AuditLogState {
  const AuditLogSuccess(this.page);
  final Paginated<AuditLogEntryEntity> page;
  @override
  List<Object?> get props => [page];
}

class AuditLogFailure extends AuditLogState {
  const AuditLogFailure(this.message);
  final String message;
  @override
  List<Object?> get props => [message];
}
