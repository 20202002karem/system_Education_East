import '../../../../core/network/api_client.dart';

/// Raw wire calls for Batch 3 §2.5. No write methods exist here by design (IN-12).
class AuditRemoteDataSource {
  AuditRemoteDataSource(this._client);
  final ApiClient _client;

  Future<({List<dynamic> data, Map<String, dynamic> meta})> listLog({
    String? entityType,
    String? entityId,
    String? actorId,
    String? from,
    String? to,
    int page = 1,
    int perPage = 20,
  }) {
    return _client.requestPaged('/audit-log', query: {
      if (entityType != null) 'entity_type': entityType,
      if (entityId != null) 'entity_id': entityId,
      if (actorId != null) 'actor_id': actorId,
      if (from != null) 'from': from,
      if (to != null) 'to': to,
      'page': page,
      'per_page': perPage,
    });
  }

  Future<({List<dynamic> data, Map<String, dynamic> meta})> listChainChecks({int page = 1, int perPage = 20}) {
    return _client.requestPaged('/audit/chain-checks', query: {'page': page, 'per_page': perPage});
  }
}
