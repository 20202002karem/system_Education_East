import '../../../../core/network/api_client.dart';

/// Raw wire calls for Batch 3 §2.1. Kept separate from AuthRepositoryImpl so
/// the repository can be unit-tested against a mocked datasource, and so the
/// HTTP shape (field names) lives in exactly one place.
class AuthRemoteDataSource {
  AuthRemoteDataSource(this._client);
  final ApiClient _client;

  Future<Map<String, dynamic>> login(String loginIdentifier, String password) {
    return _client.request<Map<String, dynamic>>(
      '/auth/login',
      method: 'POST',
      body: {'login_identifier': loginIdentifier, 'password': password},
    );
  }

  Future<void> logout() {
    return _client.request<void>('/auth/logout', method: 'POST');
  }

  Future<Map<String, dynamic>> me() {
    return _client.request<Map<String, dynamic>>('/auth/me');
  }
}
