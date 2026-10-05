import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../domain/entities/asset.dart';
import '../../domain/repositories/assets_repository.dart';
import 'assets_list_state.dart';

/// Filters/sort/search go to the server (Batch 3 §12) — nothing is filtered client-side.
class AssetsListCubit extends Cubit<AssetsListState> {
  AssetsListCubit(this._repository) : super(const AssetsListInitial());
  final AssetsRepository _repository;

  String query = '';
  AssetStatus? status;
  String sort = 'inventory_no';

  Future<void> load({int page = 1}) async {
    emit(const AssetsListLoading());
    try {
      emit(AssetsListSuccess(await _repository.list(page: page, q: query, status: status, sort: sort)));
    } on ApiException catch (e) {
      emit(AssetsListFailure(e.message));
    }
  }
}
