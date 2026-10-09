import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/utils/paginated.dart';
import '../../domain/entities/service_request.dart';
import '../../domain/repositories/requests_repository.dart';

sealed class RequestsListState extends Equatable {
  const RequestsListState();
  @override
  List<Object?> get props => [];
}

class RequestsListInitial extends RequestsListState { const RequestsListInitial(); }
class RequestsListLoading extends RequestsListState { const RequestsListLoading(); }

class RequestsListSuccess extends RequestsListState {
  const RequestsListSuccess(this.page);
  final Paginated<ServiceRequestEntity> page;
  @override
  List<Object?> get props => [page.items, page.page, page.total];
}

class RequestsListFailure extends RequestsListState {
  const RequestsListFailure(this.message);
  final String message;
  @override
  List<Object?> get props => [message];
}

/// Filtering/search is server-side; visibility scope is enforced by the backend (BR-M3 scope rules).
class RequestsListCubit extends Cubit<RequestsListState> {
  RequestsListCubit(this._repository) : super(const RequestsListInitial());
  final RequestsRepository _repository;

  String query = '';
  String? status;

  Future<void> load({int page = 1}) async {
    emit(const RequestsListLoading());
    try {
      emit(RequestsListSuccess(await _repository.list(page: page, q: query, status: status)));
    } on ApiException catch (e) {
      emit(RequestsListFailure(e.message));
    }
  }
}
