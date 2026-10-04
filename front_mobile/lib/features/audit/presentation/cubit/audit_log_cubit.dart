import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../domain/repositories/audit_repository.dart';
import 'audit_log_state.dart';

class AuditLogCubit extends Cubit<AuditLogState> {
  AuditLogCubit(this._repository) : super(const AuditLogInitial());
  final AuditRepository _repository;

  String? _entityType;
  String? _actorId;

  Future<void> load({int page = 1, String? entityType, String? actorId}) async {
    _entityType = entityType ?? _entityType;
    _actorId = actorId ?? _actorId;
    emit(const AuditLogLoading());
    try {
      final result = await _repository.listLog(entityType: _entityType, actorId: _actorId, page: page);
      emit(AuditLogSuccess(result));
    } on ApiException catch (e) {
      emit(AuditLogFailure(e.message));
    }
  }
}
