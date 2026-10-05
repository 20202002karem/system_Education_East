import '../../../../core/utils/paginated.dart';
import '../entities/asset.dart';

/// M2 Batch 3 — Assets. No delete, no transfer, no decommission (M4).
abstract class AssetsRepository {
  Future<Paginated<AssetEntity>> list({int page = 1, int perPage = 20, String? q, AssetStatus? status, int? categoryId, int? siteId, String sort = 'inventory_no'});
  Future<AssetEntity> get(int id);
  Future<AssetEntity> create({required String inventoryNo, String? serialNo, required int categoryId, required int currentSiteId, String? holderText, List<String> legacyNumbers = const []});
  Future<AssetEntity> update(int id, {required int version, int? categoryId, String? holderText, bool clearHolder = false});
  Future<AssetEntity> changeStatus(int id, {required AssetStatus to, String? reason, required int version});
  Future<void> correctIdentifier(int id, {required IdentifierField field, required String newValue, required String reason});
  Future<Paginated<AssetStatusEntry>> statusHistory(int id, {int page = 1});
  Future<Paginated<AssetCorrection>> corrections(int id, {int page = 1});
  Future<Paginated<AssetLegacyNumber>> legacyNumbers(int id, {int page = 1});
}
