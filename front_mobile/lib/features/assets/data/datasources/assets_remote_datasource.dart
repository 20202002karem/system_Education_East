import '../../../../core/network/api_client.dart';

/// Raw wire calls for M2 Batch 3 (API-AST-01..09), verbatim paths/methods.
class AssetsRemoteDataSource {
  AssetsRemoteDataSource(this._client);
  final ApiClient _client;

  Future<({List<dynamic> data, Map<String, dynamic> meta})> list(Map<String, dynamic> query) =>
      _client.requestPaged('/assets', query: query);

  Future<Map<String, dynamic>> get(int id) => _client.request('/assets/$id');

  Future<Map<String, dynamic>> create(Map<String, dynamic> body) =>
      _client.request('/assets', method: 'POST', body: body, idempotent: true);

  Future<Map<String, dynamic>> update(int id, Map<String, dynamic> body) =>
      _client.request('/assets/$id', method: 'PATCH', body: body);

  Future<Map<String, dynamic>> changeStatus(int id, Map<String, dynamic> body) =>
      _client.request('/assets/$id/status-changes', method: 'POST', body: body, idempotent: true);

  Future<Map<String, dynamic>> correctIdentifier(int id, Map<String, dynamic> body) =>
      _client.request('/assets/$id/identifier-corrections', method: 'POST', body: body, idempotent: true);

  Future<({List<dynamic> data, Map<String, dynamic> meta})> statusHistory(int id, int page) =>
      _client.requestPaged('/assets/$id/status-history', query: {'page': page});

  Future<({List<dynamic> data, Map<String, dynamic> meta})> corrections(int id, int page) =>
      _client.requestPaged('/assets/$id/identifier-corrections', query: {'page': page});

  Future<({List<dynamic> data, Map<String, dynamic> meta})> legacyNumbers(int id, int page) =>
      _client.requestPaged('/assets/$id/legacy-numbers', query: {'page': page});
}
