import 'package:flutter_test/flutter_test.dart';
import 'package:moehe_eastgaza_m1/core/network/api_exception.dart';

/// Unit tests for the status-code -> boolean-flag mapping every Cubit/page
/// relies on (e.g. `e.isValidation` to show inline field errors, `e.isLocked`
/// to show the 423 account-locked message distinctly). Full HTTP-level
/// mapping inside ApiClient itself needs a mocked Dio HttpClientAdapter,
/// which is NOT set up in this pass (see the final report's "Not Tested"
/// section) — this covers the exception's own contract in isolation.
void main() {
  group('ApiException status flags', () {
    test('401 -> isUnauthorized', () {
      const e = ApiException(statusCode: 401, code: 'unauthenticated', message: 'غير مُصادَق');
      expect(e.isUnauthorized, isTrue);
      expect(e.isForbidden, isFalse);
    });

    test('403 -> isForbidden', () {
      const e = ApiException(statusCode: 403, code: 'forbidden', message: 'لا صلاحية');
      expect(e.isForbidden, isTrue);
    });

    test('404 -> isNotFound (used for scope-isolation "not found, never 403", IN-08/E-10)', () {
      const e = ApiException(statusCode: 404, code: 'not_found', message: 'غير موجود');
      expect(e.isNotFound, isTrue);
    });

    test('409 -> isConflict', () {
      const e = ApiException(statusCode: 409, code: 'conflict', message: 'تعارض');
      expect(e.isConflict, isTrue);
    });

    test('422 -> isValidation carries fields map', () {
      const e = ApiException(statusCode: 422, code: 'validation_failed', message: 'بيانات غير صالحة', fields: {'name': ['مطلوب']});
      expect(e.isValidation, isTrue);
      expect(e.fields?['name'], contains('مطلوب'));
    });

    test('423 -> isLocked, distinct from 401 invalid_credentials', () {
      const e = ApiException(statusCode: 423, code: 'account_locked', message: 'الحساب مقفل مؤقتاً');
      expect(e.isLocked, isTrue);
      expect(e.isUnauthorized, isFalse);
    });

    test('429 -> isRateLimited', () {
      const e = ApiException(statusCode: 429, code: 'too_many_requests', message: 'عدد كبير جدًا من المحاولات');
      expect(e.isRateLimited, isTrue);
    });

    test('5xx -> isServerError', () {
      const e = ApiException(statusCode: 500, code: 'server_error', message: 'خطأ خادم');
      expect(e.isServerError, isTrue);
    });
  });
}
