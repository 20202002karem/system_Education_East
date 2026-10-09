import '../../../core/network/api_client.dart';

class TasksRemoteDataSource {
  TasksRemoteDataSource(this._client);
  final ApiClient _client;

  Future<({List<dynamic> data, Map<String, dynamic> meta})> list(Map<String, dynamic> query) => _client.requestPaged('/tasks', query: query);
  Future<Map<String, dynamic>> get(int id) => _client.request('/tasks/$id');
  Future<Map<String, dynamic>> action(int id, String name, Map<String, dynamic> body) =>
      _client.request('/tasks/$id/$name', method: 'POST', body: body, idempotent: true);
}
