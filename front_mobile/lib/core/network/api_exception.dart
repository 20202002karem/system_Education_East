/// Typed exception mirroring the Batch 3 §1 error envelope:
/// {"error": {"code","message","fields"}}. Every repository throws this (or
/// lets it propagate from ApiClient) so Cubits can pattern-match on `code`
/// and `statusCode` instead of parsing strings.
class ApiException implements Exception {
  final int statusCode;
  final String code;
  final String message;
  final Map<String, List<String>>? fields;

  const ApiException({
    required this.statusCode,
    required this.code,
    required this.message,
    this.fields,
  });

  bool get isUnauthorized => statusCode == 401;
  bool get isForbidden => statusCode == 403;
  bool get isNotFound => statusCode == 404;
  bool get isConflict => statusCode == 409;
  bool get isValidation => statusCode == 422;
  bool get isLocked => statusCode == 423;
  bool get isRateLimited => statusCode == 429;
  bool get isServerError => statusCode >= 500;

  @override
  String toString() => 'ApiException($statusCode, $code, $message)';
}
