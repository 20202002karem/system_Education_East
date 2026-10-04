import 'package:equatable/equatable.dart';
import '../../../../core/utils/paginated.dart';
import '../../domain/entities/user.dart';

sealed class UsersListState extends Equatable {
  const UsersListState();
  @override
  List<Object?> get props => [];
}

class UsersListInitial extends UsersListState {
  const UsersListInitial();
}

class UsersListLoading extends UsersListState {
  const UsersListLoading();
}

class UsersListSuccess extends UsersListState {
  const UsersListSuccess(this.page);
  final Paginated<UserEntity> page;

  @override
  List<Object?> get props => [page];
}

class UsersListFailure extends UsersListState {
  const UsersListFailure(this.message);
  final String message;

  @override
  List<Object?> get props => [message];
}
