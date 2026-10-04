import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../domain/repositories/organization_repository.dart';
import 'sites_list_state.dart';

class SitesListCubit extends Cubit<SitesListState> {
  SitesListCubit(this._repository) : super(const SitesListInitial());
  final OrganizationRepository _repository;

  Future<void> load({int page = 1}) async {
    emit(const SitesListLoading());
    try {
      final result = await _repository.listSites(page: page);
      emit(SitesListSuccess(result));
    } on ApiException catch (e) {
      emit(SitesListFailure(e.message));
    }
  }
}
