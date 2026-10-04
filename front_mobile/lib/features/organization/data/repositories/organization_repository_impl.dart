import '../../../../core/utils/paginated.dart';
import '../../domain/entities/site.dart';
import '../../domain/entities/site_manager.dart';
import '../../domain/repositories/organization_repository.dart';
import '../datasources/organization_remote_datasource.dart';
import '../models/site_manager_model.dart';
import '../models/site_model.dart';

class OrganizationRepositoryImpl implements OrganizationRepository {
  OrganizationRepositoryImpl(this._remote);
  final OrganizationRemoteDataSource _remote;

  @override
  Future<Paginated<SiteEntity>> listSites({String? type, String? status, int page = 1, int perPage = 20}) async {
    final result = await _remote.listSites(type: type, status: status, page: page, perPage: perPage);
    return Paginated(
      items: result.data.map((e) => SiteModel.fromJson(e as Map<String, dynamic>)).toList(),
      page: result.meta['page'] as int,
      perPage: result.meta['per_page'] as int,
      total: result.meta['total'] as int,
    );
  }

  @override
  Future<SiteEntity> getSite(int id) async => SiteModel.fromJson(await _remote.getSite(id));

  @override
  Future<SiteEntity> createSite({required SiteType type, required String code, required String nameAr}) async {
    final json = await _remote.createSite({'type': siteTypeToWire(type), 'code': code, 'name_ar': nameAr});
    return SiteModel.fromJson(json);
  }

  @override
  Future<SiteEntity> updateSite(int id, {SiteType? type, String? code, String? nameAr}) async {
    final json = await _remote.updateSite(id, {
      if (type != null) 'type': siteTypeToWire(type),
      if (code != null) 'code': code,
      if (nameAr != null) 'name_ar': nameAr,
    });
    return SiteModel.fromJson(json);
  }

  @override
  Future<SiteEntity> archiveSite(int id) async => SiteModel.fromJson(await _remote.archiveSite(id));

  @override
  Future<List<SiteManagerEntity>> listManagers(int siteId) async {
    final list = await _remote.listManagers(siteId);
    return list.map((e) => SiteManagerModel.fromJson(e as Map<String, dynamic>)).toList();
  }

  @override
  Future<SiteManagerEntity> assignManager(int siteId, {required int userId, required DateTime from}) async {
    final json = await _remote.assignManager(siteId, {
      'user_id': userId,
      'from': from.toIso8601String().split('T').first,
    });
    return SiteManagerModel.fromJson(json);
  }

  @override
  Future<SiteManagerEntity> endManager(int siteManagerId) async => SiteManagerModel.fromJson(await _remote.endManager(siteManagerId));
}
