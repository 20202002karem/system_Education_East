import '../../../../core/network/api_client.dart';

/// Raw wire calls for Batch 3 §2.3, verbatim paths/methods.
class OrganizationRemoteDataSource {
  OrganizationRemoteDataSource(this._client);
  final ApiClient _client;

  Future<({List<dynamic> data, Map<String, dynamic> meta})> listSites({String? type, String? status, int page = 1, int perPage = 20}) {
    return _client.requestPaged('/sites', query: {
      if (type != null) 'type': type,
      if (status != null) 'status': status,
      'page': page,
      'per_page': perPage,
    });
  }

  Future<Map<String, dynamic>> getSite(int id) => _client.request('/sites/$id');

  Future<Map<String, dynamic>> createSite(Map<String, dynamic> body) =>
      _client.request('/sites', method: 'POST', body: body, idempotent: true);

  Future<Map<String, dynamic>> updateSite(int id, Map<String, dynamic> body) =>
      _client.request('/sites/$id', method: 'PATCH', body: body);

  Future<Map<String, dynamic>> archiveSite(int id) => _client.request('/sites/$id/archive', method: 'POST');

  Future<List<dynamic>> listManagers(int siteId) => _client.request('/sites/$siteId/managers');

  Future<Map<String, dynamic>> assignManager(int siteId, Map<String, dynamic> body) =>
      _client.request('/sites/$siteId/managers', method: 'POST', body: body, idempotent: true);

  Future<Map<String, dynamic>> endManager(int siteManagerId) =>
      _client.request('/site-managers/$siteManagerId/end', method: 'POST');
}
