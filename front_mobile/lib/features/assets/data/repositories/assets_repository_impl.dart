import '../../../../core/utils/paginated.dart';
import '../../domain/entities/asset.dart';
import '../../domain/repositories/assets_repository.dart';
import '../datasources/assets_remote_datasource.dart';
import '../models/asset_models.dart';

class AssetsRepositoryImpl implements AssetsRepository {
  AssetsRepositoryImpl(this._remote);
  final AssetsRemoteDataSource _remote;

  Paginated<T> _page<T>(({List<dynamic> data, Map<String, dynamic> meta}) r, T Function(Map<String, dynamic>) map) => Paginated(
        items: r.data.map((e) => map(e as Map<String, dynamic>)).toList(),
        page: r.meta['page'] as int,
        perPage: r.meta['per_page'] as int,
        total: r.meta['total'] as int,
      );

  @override
  Future<Paginated<AssetEntity>> list({int page = 1, int perPage = 20, String? q, AssetStatus? status, int? categoryId, int? siteId, String sort = 'inventory_no'}) async {
    final r = await _remote.list({
      'page': page,
      'per_page': perPage,
      'sort': sort,
      if (q != null && q.trim().isNotEmpty) 'q': q.trim(),
      if (status != null) 'status': assetStatusToWire(status),
      if (categoryId != null) 'category_id': categoryId,
      if (siteId != null) 'site_id': siteId,
    });
    return _page(r, AssetModel.fromJson);
  }

  @override
  Future<AssetEntity> get(int id) async => AssetModel.fromJson(await _remote.get(id));

  @override
  Future<AssetEntity> create({required String inventoryNo, String? serialNo, required int categoryId, required int currentSiteId, String? holderText, List<String> legacyNumbers = const []}) async {
    final json = await _remote.create({
      'inventory_no': inventoryNo,
      'serial_no': serialNo,
      'category_id': categoryId,
      'current_site_id': currentSiteId,
      'holder_text': holderText,
      if (legacyNumbers.isNotEmpty) 'legacy_numbers': legacyNumbers,
    });
    return AssetModel.fromJson(json);
  }

  @override
  Future<AssetEntity> update(int id, {required int version, int? categoryId, String? holderText, bool clearHolder = false}) async {
    await _remote.update(id, {
      'version': version,
      if (categoryId != null) 'category_id': categoryId,
      if (holderText != null || clearHolder) 'holder_text': holderText,
    });
    return get(id); // PATCH returns a partial card (Batch 3 API-AST-04); reload the full one
  }

  @override
  Future<AssetEntity> changeStatus(int id, {required AssetStatus to, String? reason, required int version}) async {
    await _remote.changeStatus(id, {'to_status': assetStatusToWire(to), 'reason': reason, 'version': version});
    return get(id);
  }

  @override
  Future<void> correctIdentifier(int id, {required IdentifierField field, required String newValue, required String reason}) async {
    await _remote.correctIdentifier(id, {'field_name': identifierFieldToWire(field), 'new_value': newValue, 'reason': reason});
  }

  @override
  Future<Paginated<AssetStatusEntry>> statusHistory(int id, {int page = 1}) async => _page(await _remote.statusHistory(id, page), AssetStatusEntryModel.fromJson);

  @override
  Future<Paginated<AssetCorrection>> corrections(int id, {int page = 1}) async => _page(await _remote.corrections(id, page), AssetCorrectionModel.fromJson);

  @override
  Future<Paginated<AssetLegacyNumber>> legacyNumbers(int id, {int page = 1}) async => _page(await _remote.legacyNumbers(id, page), AssetLegacyNumberModel.fromJson);
}
