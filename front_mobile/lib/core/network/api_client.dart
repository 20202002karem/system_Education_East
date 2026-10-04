import 'dart:math';
import 'package:dio/dio.dart';
import '../storage/secure_token_storage.dart';
import '../utils/app_config.dart';
import 'api_exception.dart';

typedef UnauthorizedHandler = void Function();

/// Thin wrapper around Dio implementing the Batch 3 §1 API conventions:
/// - base path /api/v1
/// - Bearer token from SecureTokenStorage on every request
/// - Idempotency-Key header on resource-creating POSTs
/// - {"data": ...} / {"data": [...], "meta": {...}} unwrapping
/// - {"error": {"code","message","fields"}} -> ApiException
/// - a single onUnauthorized hook so AuthCubit can react to any 401, anywhere.
class ApiClient {
  ApiClient({Dio? dio, SecureTokenStorage? tokenStorage})
      : _tokenStorage = tokenStorage ?? SecureTokenStorage(),
        _dio = dio ??
            Dio(BaseOptions(
              baseUrl: '${AppConfig.apiBaseUrl}/${AppConfig.apiVersion}',
              connectTimeout: const Duration(seconds: 15),
              receiveTimeout: const Duration(seconds: 15),
              headers: {'Accept': 'application/json'},
            ));

  final Dio _dio;
  final SecureTokenStorage _tokenStorage;
  UnauthorizedHandler? onUnauthorized;

  String _newIdempotencyKey() {
    final rand = Random.secure();
    final bytes = List<int>.generate(16, (_) => rand.nextInt(256));
    return bytes.map((b) => b.toRadixString(16).padLeft(2, '0')).join();
  }

  Future<Map<String, String>> _authHeaders({bool idempotent = false}) async {
    final token = await _tokenStorage.read();
    return {
      if (token != null) 'Authorization': 'Bearer $token',
      if (idempotent) 'Idempotency-Key': _newIdempotencyKey(),
    };
  }

  /// Returns the unwrapped `data` object for single-item responses.
  Future<T> request<T>(
    String path, {
    String method = 'GET',
    Object? body,
    Map<String, dynamic>? query,
    bool idempotent = false,
  }) async {
    try {
      final response = await _dio.request<Map<String, dynamic>>(
        path,
        data: body,
        queryParameters: query,
        options: Options(method: method, headers: await _authHeaders(idempotent: idempotent)),
      );
      return response.data?['data'] as T;
    } on DioException catch (e) {
      throw _mapError(e);
    }
  }

  /// Returns both `data` (as a raw List) and `meta` for paginated list endpoints.
  Future<({List<dynamic> data, Map<String, dynamic> meta})> requestPaged(
    String path, {
    Map<String, dynamic>? query,
  }) async {
    try {
      final response = await _dio.request<Map<String, dynamic>>(
        path,
        queryParameters: query,
        options: Options(method: 'GET', headers: await _authHeaders()),
      );
      final body = response.data ?? {};
      return (
        data: (body['data'] as List<dynamic>?) ?? const [],
        meta: (body['meta'] as Map<String, dynamic>?) ?? const {'page': 1, 'per_page': 20, 'total': 0},
      );
    } on DioException catch (e) {
      throw _mapError(e);
    }
  }

  ApiException _mapError(DioException e) {
    final status = e.response?.statusCode ?? 0;

    if (status == 401 && onUnauthorized != null) {
      onUnauthorized!();
    }

    if (status == 0) {
      return const ApiException(statusCode: 0, code: 'network_error', message: 'تعذّر الاتصال بالخادم، تحقّق من الاتصال بالإنترنت');
    }

    final data = e.response?.data;
    if (data is Map<String, dynamic> && data['error'] is Map<String, dynamic>) {
      final err = data['error'] as Map<String, dynamic>;
      final rawFields = err['fields'] as Map<String, dynamic>?;
      return ApiException(
        statusCode: status,
        code: (err['code'] as String?) ?? 'http_$status',
        message: (err['message'] as String?) ?? _fallbackMessage(status),
        fields: rawFields?.map((k, v) => MapEntry(k, (v as List).map((e) => e.toString()).toList())),
      );
    }

    return ApiException(statusCode: status, code: 'http_$status', message: _fallbackMessage(status));
  }

  String _fallbackMessage(int status) {
    switch (status) {
      case 401:
        return 'غير مُصادَق، يرجى تسجيل الدخول مجددًا';
      case 403:
        return 'لا صلاحية لتنفيذ هذا الإجراء';
      case 404:
        return 'المورد غير موجود';
      case 409:
        return 'تعارض في البيانات';
      case 422:
        return 'بيانات غير صالحة';
      case 423:
        return 'الحساب مقفل مؤقتاً';
      case 429:
        return 'عدد كبير جدًا من المحاولات، حاول لاحقًا';
      default:
        return status >= 500 ? 'حدث خطأ في الخادم، حاول لاحقًا' : 'حدث خطأ غير متوقع';
    }
  }
}
