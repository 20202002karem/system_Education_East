import '../../../core/network/api_client.dart';

/// Raw wire calls for M3-API-001..016 (verbatim paths; every transition is a POST action).
class RequestsRemoteDataSource {
  RequestsRemoteDataSource(this._client);
  final ApiClient _client;

  Future<({List<dynamic> data, Map<String, dynamic> meta})> list(Map<String, dynamic> query) => _client.requestPaged('/requests', query: query);
  Future<Map<String, dynamic>> get(int id) => _client.request('/requests/$id');
  Future<Map<String, dynamic>> create(Map<String, dynamic> body) => _client.request('/requests', method: 'POST', body: body, idempotent: true);
  Future<Map<String, dynamic>> action(int id, String name, Map<String, dynamic> body) =>
      _client.request('/requests/$id/$name', method: 'POST', body: body, idempotent: true);
  Future<({List<dynamic> data, Map<String, dynamic> meta})> notes(int id, int page) => _client.requestPaged('/requests/$id/notes', query: {'page': page});
  Future<Map<String, dynamic>> addNote(int id, Map<String, dynamic> body) =>
      _client.request('/requests/$id/notes', method: 'POST', body: body, idempotent: true);
  Future<({List<dynamic> data, Map<String, dynamic> meta})> sites() => _client.requestPaged('/sites', query: {'per_page': 100});
  Future<List<dynamic>> reference(String resource) => _client.request('/reference/$resource');
}
