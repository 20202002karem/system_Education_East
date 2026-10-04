import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../domain/repositories/auth_repository.dart';
import 'auth_state.dart';

/// Batch 3 §2.1/§3.1 — login() either completes authentication directly
/// (non-chairman) or transitions to AuthMfaRequired (chairman, AUTH-07).
class AuthCubit extends Cubit<AuthState> {
  AuthCubit(this._repository) : super(const AuthInitial());

  final AuthRepository _repository;

  /// Called once at app start to silently restore a stored session (page
  /// refresh equivalent). Mirrors AuthContext's boot effect on Web.
  Future<void> bootstrap() async {
    emit(const AuthLoading());
    final hasSession = await _repository.hasStoredSession();
    if (!hasSession) {
      emit(const AuthUnauthenticated());
      return;
    }
    try {
      final user = await _repository.getCurrentUser();
      emit(AuthAuthenticated(user));
    } on ApiException {
      // Expired/invalid token — same handling as any other 401 (instructions §6).
      emit(const AuthUnauthenticated());
    }
  }

  Future<void> login(String loginIdentifier, String password) async {
    emit(const AuthLoading());
    try {
      await _repository.login(
        loginIdentifier: loginIdentifier,
        password: password,
      );

      final user = await _repository.getCurrentUser();

      emit(AuthAuthenticated(user));
    } on ApiException catch (e) {
      // invalid_credentials (401) / account_locked (423) / mfa_not_provisioned
      // (403) all surface here with the backend's own Arabic message —
      // deliberately NOT distinguishing "account doesn't exist" from "wrong
      // password" (uniform failure message, MD-01).
      emit(AuthUnauthenticated(error: e.message));
    }
  }

  Future<void> logout() async {
    await _repository.logout();
    emit(const AuthUnauthenticated());
  }

  /// Wired to ApiClient.onUnauthorized so ANY 401 anywhere in the app (not
  /// just from auth endpoints) forces a return to the login screen
  /// (instructions §6: "التعامل مع انتهاء الجلسة / إعادة توجيه المستخدم").
  void forceUnauthenticated() {
    if (state is AuthAuthenticated) {
      emit(const AuthUnauthenticated(
          error: 'انتهت الجلسة، يرجى تسجيل الدخول مجددًا'));
    }
  }
}
