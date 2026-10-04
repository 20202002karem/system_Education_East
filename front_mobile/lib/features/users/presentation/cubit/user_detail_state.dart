import 'package:equatable/equatable.dart';
import '../../domain/entities/permission_grant.dart';
import '../../domain/entities/user.dart';

sealed class UserDetailState extends Equatable {
  const UserDetailState();
  @override
  List<Object?> get props => [];
}

class UserDetailInitial extends UserDetailState {
  const UserDetailInitial();
}

class UserDetailLoading extends UserDetailState {
  const UserDetailLoading();
}

class UserDetailSuccess extends UserDetailState {
  const UserDetailSuccess(this.user, this.grants, {this.actionInFlight = false, this.actionError});
  final UserEntity user;
  final List<PermissionGrantEntity> grants;
  final bool actionInFlight;
  final String? actionError;

  UserDetailSuccess copyWith({UserEntity? user, List<PermissionGrantEntity>? grants, bool? actionInFlight, String? actionError}) {
    return UserDetailSuccess(
      user ?? this.user,
      grants ?? this.grants,
      actionInFlight: actionInFlight ?? false,
      actionError: actionError,
    );
  }

  @override
  List<Object?> get props => [user, grants, actionInFlight, actionError];
}

class UserDetailFailure extends UserDetailState {
  const UserDetailFailure(this.message);
  final String message;

  @override
  List<Object?> get props => [message];
}
