import '../../../core/network/api_client.dart';

class NotificationsRemoteDataSource {
  NotificationsRemoteDataSource(this._client);
  final ApiClient _client;

  Future<({List<dynamic> data, Map<String, dynamic> meta})> list(Map<String, dynamic> query) => _client.requestPaged('/notifications', query: query);
  Future<Map<String, dynamic>> markRead(int id) => _client.request('/notifications/$id/read', method: 'POST');
}
