import '../../../../core/utils/paginated.dart';
import '../entities/site.dart';
import '../entities/site_manager.dart';

/// Batch 3 §2.3 — Sites & Site Managers, chairman-only (read AND write in M1).
abstract class OrganizationRepository {
  Future<Paginated<SiteEntity>> listSites({String? type, String? status, int page = 1, int perPage = 20});
  Future<SiteEntity> getSite(int id);
  Future<SiteEntity> createSite({required SiteType type, required String code, required String nameAr});
  Future<SiteEntity> updateSite(int id, {SiteType? type, String? code, String? nameAr});
  Future<SiteEntity> archiveSite(int id);

  Future<List<SiteManagerEntity>> listManagers(int siteId);
  Future<SiteManagerEntity> assignManager(int siteId, {required int userId, required DateTime from});
  Future<SiteManagerEntity> endManager(int siteManagerId);
}
