import '../../../../core/utils/paginated.dart';
import '../entities/audit_chain_check.dart';
import '../entities/audit_log_entry.dart';

/// Batch 3 §2.5 — read-only, chairman-only.
abstract class AuditRepository {
  Future<Paginated<AuditLogEntryEntity>> listLog({
    String? entityType,
    String? entityId,
    String? actorId,
    String? from,
    String? to,
    int page = 1,
    int perPage = 20,
  });
  Future<Paginated<AuditChainCheckEntity>> listChainChecks({int page = 1, int perPage = 20});
}
