import '../../../../core/utils/paginated.dart';
import '../entities/service_request.dart';

/// Mobile scope for M3 (Batch 1 §25 / Batch 3 §26): create/list/detail, assignee
/// work transitions, requester cancel/reopen, notes. Desk actions (triage/assign)
/// stay on the web.
abstract class RequestsRepository {
  Future<Paginated<ServiceRequestEntity>> list({int page = 1, String q = '', String? status});
  Future<ServiceRequestEntity> get(int id);
  Future<ServiceRequestEntity> create({required int originSiteId, required int channelId, required String description, String? suggestedPriority, String? requesterName});
  Future<ServiceRequestEntity> start(int id, {int? version});
  Future<ServiceRequestEntity> hold(int id, {String? reason, int? version});
  Future<ServiceRequestEntity> resume(int id, {int? version});
  Future<ServiceRequestEntity> close(int id, {required String action, required String result, required int effortMinutes, int? version});
  Future<ServiceRequestEntity> cancel(int id, {required String category, required String text, String? workDone, int? version});
  Future<ServiceRequestEntity> reopen(int id, {required String reason});
  Future<Paginated<RequestNoteEntity>> notes(int id, {int page = 1});
  Future<RequestNoteEntity> addNote(int id, {required String visibility, required String body});
  Future<List<LookupItem>> sites();
  Future<List<LookupItem>> channels();
}
