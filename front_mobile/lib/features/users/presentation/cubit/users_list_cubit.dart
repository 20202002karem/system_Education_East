import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../domain/repositories/users_repository.dart';
import 'users_list_state.dart';

/// Batch 3 §2.2 GET /users — filters + pagination (instructions §4/§14).
class UsersListCubit extends Cubit<UsersListState> {
  UsersListCubit(this._repository) : super(const UsersListInitial());
  final UsersRepository _repository;

  String? _role;
  String? _status;

  Future<void> load({int page = 1, String? role, String? status}) async {
    _role = role ?? _role;
    _status = status ?? _status;
    emit(const UsersListLoading());
    try {
      final result = await _repository.list(role: _role, status: _status, page: page);
      emit(UsersListSuccess(result));
    } on ApiException catch (e) {
      emit(UsersListFailure(e.message));
    }
  }

  void setRoleFilter(String? role) {
    _role = role;
    load(page: 1);
  }

  void setStatusFilter(String? status) {
    _status = status;
    load(page: 1);
  }
}
