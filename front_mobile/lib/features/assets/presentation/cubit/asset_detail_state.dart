import 'package:equatable/equatable.dart';
import '../../domain/entities/asset.dart';

sealed class AssetDetailState extends Equatable {
  const AssetDetailState();
  @override
  List<Object?> get props => [];
}

class AssetDetailLoading extends AssetDetailState { const AssetDetailLoading(); }

class AssetDetailFailure extends AssetDetailState {
  const AssetDetailFailure(this.message);
  final String message;
  @override
  List<Object?> get props => [message];
}

class AssetDetailSuccess extends AssetDetailState {
  const AssetDetailSuccess({required this.asset, required this.statusHistory, required this.corrections, required this.legacyNumbers});
  final AssetEntity asset;
  final List<AssetStatusEntry> statusHistory;
  final List<AssetCorrection> corrections;
  final List<AssetLegacyNumber> legacyNumbers;
  @override
  List<Object?> get props => [asset, statusHistory, corrections, legacyNumbers];
}
