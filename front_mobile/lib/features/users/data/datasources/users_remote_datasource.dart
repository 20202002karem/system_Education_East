import '../../../../core/network/api_client.dart';

/// Raw wire calls for Batch 3 §2.2, verbatim paths/methods.
class UsersRemoteDataSource {
  UsersRemoteDataSource(this._client);
  final ApiClient _client;

  Future<({List<dynamic> data, Map<String, dynamic> meta})> list({String? role, String? status, int page = 1, int perPage = 20}) {
    return _client.requestPaged('/users', query: {
      if (role != null) 'role': role,
      if (status != null) 'status': status,
      'page': page,
      'per_page': perPage,
    });
  }

  Future<Map<String, dynamic>> get(int id) => _client.request('/users/$id');

  Future<Map<String, dynamic>> create(Map<String, dynamic> body) =>
      _client.request('/users', method: 'POST', body: body, idempotent: true);

  Future<Map<String, dynamic>> update(int id, Map<String, dynamic> body) =>
      _client.request('/users/$id', method: 'PATCH', body: body);

  Future<Map<String, dynamic>> disable(int id) => _client.request('/users/$id/disable', method: 'POST');
  Future<Map<String, dynamic>> enable(int id) => _client.request('/users/$id/enable', method: 'POST');

  Future<void> resetPassword(int id, String password) =>
      _client.request('/users/$id/reset-password', method: 'POST', body: {'password': password});

  Future<void> terminateSessions(int id) => _client.request('/users/$id/sessions/terminate', method: 'POST');

  Future<List<dynamic>> permissionGrants(int userId) => _client.request('/users/$userId/permission-grants');

  Future<Map<String, dynamic>> grantPermission(int userId, Map<String, dynamic> body) =>
      _client.request('/users/$userId/permission-grants', method: 'POST', body: body, idempotent: true);

  Future<Map<String, dynamic>> revokePermission(int grantId, {String? reason}) =>
      _client.request('/permission-grants/$grantId/revoke', method: 'POST', body: {'reason': reason});

  Future<List<dynamic>> siteScopes(int userId) => _client.request('/users/$userId/site-scopes');

  Future<List<dynamic>> replaceSiteScopes(int userId, List<int> siteIds) =>
      _client.request('/users/$userId/site-scopes', method: 'PUT', body: {'site_ids': siteIds});
}
