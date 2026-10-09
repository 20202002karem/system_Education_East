import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/utils/paginated.dart';
import '../../domain/entities/task.dart';
import '../../domain/repositories/tasks_repository.dart';

sealed class TasksListState extends Equatable {
  const TasksListState();
  @override
  List<Object?> get props => [];
}

class TasksListInitial extends TasksListState { const TasksListInitial(); }
class TasksListLoading extends TasksListState { const TasksListLoading(); }

class TasksListSuccess extends TasksListState {
  const TasksListSuccess(this.page);
  final Paginated<TaskEntity> page;
  @override
  List<Object?> get props => [page.items, page.page, page.total];
}

class TasksListFailure extends TasksListState {
  const TasksListFailure(this.message);
  final String message;
  @override
  List<Object?> get props => [message];
}

class TasksListCubit extends Cubit<TasksListState> {
  TasksListCubit(this._repository) : super(const TasksListInitial());
  final TasksRepository _repository;
  String? status;

  Future<void> load({int page = 1}) async {
    emit(const TasksListLoading());
    try {
      emit(TasksListSuccess(await _repository.list(page: page, status: status)));
    } on ApiException catch (e) {
      emit(TasksListFailure(e.message));
    }
  }
}
