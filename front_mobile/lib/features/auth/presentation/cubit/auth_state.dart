import 'package:equatable/equatable.dart';
import '../../../users/domain/entities/user.dart';

/// Cubit states (instructions §11): Initial / Loading / Success / Failure,
/// plus the MFA-specific intermediate state Batch 3's login flow requires.
sealed class AuthState extends Equatable {
  const AuthState();
  @override
  List<Object?> get props => [];
}

class AuthInitial extends AuthState {
  const AuthInitial();
}

class AuthLoading extends AuthState {
  const AuthLoading();
}

class AuthAuthenticated extends AuthState {
  const AuthAuthenticated(this.user);
  final UserEntity user;

  @override
  List<Object?> get props => [user];
}

class AuthUnauthenticated extends AuthState {
  const AuthUnauthenticated({this.error});
  final String? error;

  @override
  List<Object?> get props => [error];
}
