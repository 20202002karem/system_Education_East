/// Compile-time config — pass with `--dart-define=API_BASE_URL=...` at build
/// time. Defaults to the Android emulator loopback (10.0.2.2) pointing at a
/// locally-run M1 Laravel backend on port 8000.
class AppConfig {
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://192.168.137.1:8000/api',
  );

  /// Batch 3 §1 — base path is /api/v1; apiBaseUrl above is just host+/api.
  static const String apiVersion = 'v1';
}
