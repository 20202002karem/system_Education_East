import '../../../../core/network/api_client.dart';

/// Raw wire calls for Batch 3 §2.4, verbatim paths/methods.
class SettingsRemoteDataSource {
  SettingsRemoteDataSource(this._client);
  final ApiClient _client;

  Future<List<dynamic>> listSettings() => _client.request('/settings');

  Future<Map<String, dynamic>> updateSetting(String key, String value) =>
      _client.request('/settings/$key', method: 'PATCH', body: {'value': value});

  Future<List<dynamic>> listReference(String resource) => _client.request('/reference/$resource');

  Future<Map<String, dynamic>> createReference(String resource, Map<String, dynamic> body) =>
      _client.request('/reference/$resource', method: 'POST', body: body, idempotent: true);

  Future<Map<String, dynamic>> updateReference(String resource, int id, Map<String, dynamic> body) =>
      _client.request('/reference/$resource/$id', method: 'PATCH', body: body);
}
