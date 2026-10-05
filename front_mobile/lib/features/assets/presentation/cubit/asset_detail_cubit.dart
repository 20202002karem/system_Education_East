import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../domain/repositories/assets_repository.dart';
import 'asset_detail_state.dart';

class AssetDetailCubit extends Cubit<AssetDetailState> {
  AssetDetailCubit(this._repository, this.assetId) : super(const AssetDetailLoading());
  final AssetsRepository _repository;
  final int assetId;

  Future<void> load() async {
    emit(const AssetDetailLoading());
    try {
      final asset = await _repository.get(assetId);
      final history = await _repository.statusHistory(assetId);
      final corrections = await _repository.corrections(assetId);
      final legacy = await _repository.legacyNumbers(assetId);
      emit(AssetDetailSuccess(
        asset: asset,
        statusHistory: history.items,
        corrections: corrections.items,
        legacyNumbers: legacy.items,
      ));
    } on ApiException catch (e) {
      emit(AssetDetailFailure(e.isNotFound ? 'الجهاز غير موجود' : e.message));
    }
  }
}
