import '../../../../core/utils/paginated.dart';
import '../../domain/entities/audit_chain_check.dart';
import '../../domain/entities/audit_log_entry.dart';
import '../../domain/repositories/audit_repository.dart';
import '../datasources/audit_remote_datasource.dart';
import '../models/audit_chain_check_model.dart';
import '../models/audit_log_entry_model.dart';

class AuditRepositoryImpl implements AuditRepository {
  AuditRepositoryImpl(this._remote);
  final AuditRemoteDataSource _remote;

  @override
  Future<Paginated<AuditLogEntryEntity>> listLog({
    String? entityType,
    String? entityId,
    String? actorId,
    String? from,
    String? to,
    int page = 1,
    int perPage = 20,
  }) async {
    final result = await _remote.listLog(entityType: entityType, entityId: entityId, actorId: actorId, from: from, to: to, page: page, perPage: perPage);
    return Paginated(
      items: result.data.map((e) => AuditLogEntryModel.fromJson(e as Map<String, dynamic>)).toList(),
      page: result.meta['page'] as int,
      perPage: result.meta['per_page'] as int,
      total: result.meta['total'] as int,
    );
  }

  @override
  Future<Paginated<AuditChainCheckEntity>> listChainChecks({int page = 1, int perPage = 20}) async {
    final result = await _remote.listChainChecks(page: page, perPage: perPage);
    return Paginated(
      items: result.data.map((e) => AuditChainCheckModel.fromJson(e as Map<String, dynamic>)).toList(),
      page: result.meta['page'] as int,
      perPage: result.meta['per_page'] as int,
      total: result.meta['total'] as int,
    );
  }
}
