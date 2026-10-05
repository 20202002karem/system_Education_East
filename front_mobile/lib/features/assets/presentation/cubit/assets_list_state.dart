import 'package:equatable/equatable.dart';
import '../../../../core/utils/paginated.dart';
import '../../domain/entities/asset.dart';

sealed class AssetsListState extends Equatable {
  const AssetsListState();
  @override
  List<Object?> get props => [];
}

class AssetsListInitial extends AssetsListState { const AssetsListInitial(); }
class AssetsListLoading extends AssetsListState { const AssetsListLoading(); }

class AssetsListSuccess extends AssetsListState {
  const AssetsListSuccess(this.page);
  final Paginated<AssetEntity> page;
  @override
  List<Object?> get props => [page.items, page.page, page.total];
}

class AssetsListFailure extends AssetsListState {
  const AssetsListFailure(this.message);
  final String message;
  @override
  List<Object?> get props => [message];
}
