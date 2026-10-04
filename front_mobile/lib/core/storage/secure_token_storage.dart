import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Token storage using the platform Keychain (iOS) / Keystore (Android) via
/// flutter_secure_storage — never SharedPreferences, never plain files.
/// Holds ONLY the bearer access token. Never store password, mfa_secret, or
/// any other secret here or anywhere else in the app (instructions §16).
class SecureTokenStorage {
  SecureTokenStorage({FlutterSecureStorage? storage})
      : _storage = storage ?? const FlutterSecureStorage();

  final FlutterSecureStorage _storage;
  static const _tokenKey = 'm1_access_token';

  Future<void> save(String token) => _storage.write(key: _tokenKey, value: token);

  Future<String?> read() => _storage.read(key: _tokenKey);

  Future<void> clear() => _storage.delete(key: _tokenKey);
}
