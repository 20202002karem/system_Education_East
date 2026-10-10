import '../../../core/utils/paginated.dart';
import '../domain/entities/service_request.dart';
import '../domain/repositories/requests_repository.dart';
import 'requests_remote_datasource.dart';

class RequestsRepositoryImpl implements RequestsRepository {
  RequestsRepositoryImpl(this._ds);
  final RequestsRemoteDataSource _ds;

  Paginated<T> _page<T>(({List<dynamic> data, Map<String, dynamic> meta}) r, T Function(Map<String, dynamic>) f) => Paginated<T>(
        items: r.data.map((e) => f(e as Map<String, dynamic>)).toList(),
        page: (r.meta['page'] as num?)?.toInt() ?? 1,
        perPage: (r.meta['per_page'] as num?)?.toInt() ?? 20,
        total: (r.meta['total'] as num?)?.toInt() ?? r.data.length,
      );

  Map<String, dynamic> _v(int? version, [Map<String, dynamic> extra = const {}]) => {...extra, if (version != null) 'version': version};

  @override
  Future<Paginated<ServiceRequestEntity>> list({int page = 1, String q = '', String? status}) async => _page(
        await _ds.list({'page': page, if (q.isNotEmpty) 'q': q, if (status != null) 'status': status}),
        ServiceRequestEntity.fromJson,
      );

  @override
  Future<ServiceRequestEntity> get(int id) async => ServiceRequestEntity.fromJson(await _ds.get(id));

  @override
  Future<ServiceRequestEntity> create({required int originSiteId, required int channelId, required String description, String? suggestedPriority, String? requesterName}) async =>
      ServiceRequestEntity.fromJson(await _ds.create({
        'origin_site_id': originSiteId,
        'channel_id': channelId,
        'description': description,
        if (suggestedPriority != null) 'suggested_priority': suggestedPriority,
        if (requesterName != null && requesterName.isNotEmpty) 'requester_name': requesterName,
      }));

  Future<ServiceRequestEntity> _act(int id, String name, Map<String, dynamic> body) async =>
      ServiceRequestEntity.fromJson(await _ds.action(id, name, body));

  @override
  Future<ServiceRequestEntity> start(int id, {int? version}) => _act(id, 'start', _v(version));
  @override
  Future<ServiceRequestEntity> hold(int id, {String? reason, int? version}) => _act(id, 'hold', _v(version, {if (reason != null && reason.isNotEmpty) 'hold_reason': reason}));
  @override
  Future<ServiceRequestEntity> resume(int id, {int? version}) => _act(id, 'resume', _v(version));
  @override
  Future<ServiceRequestEntity> close(int id, {required String action, required String result, required int effortMinutes, int? version}) =>
      _act(id, 'close', _v(version, {'closure_action': action, 'closure_result': result, 'closure_effort_minutes': effortMinutes}));
  @override
  Future<ServiceRequestEntity> cancel(int id, {required String category, required String text, String? workDone, int? version}) =>
      _act(id, 'cancel', _v(version, {'reason_category': category, 'reason_text': text, if (workDone != null && workDone.isNotEmpty) 'work_done_summary': workDone}));
  @override
  Future<ServiceRequestEntity> reopen(int id, {required String reason}) => _act(id, 'reopen', {'reason': reason});

  @override
  Future<Paginated<RequestNoteEntity>> notes(int id, {int page = 1}) async => _page(await _ds.notes(id, page), RequestNoteEntity.fromJson);

  @override
  Future<RequestNoteEntity> addNote(int id, {required String visibility, required String body}) async =>
      RequestNoteEntity.fromJson(await _ds.addNote(id, {'visibility': visibility, 'body': body}));

  @override
  Future<List<LookupItem>> sites() async =>
      (await _ds.sites()).data.map((e) => LookupItem((e as Map<String, dynamic>)['id'] as int, (e['name_ar'] ?? e['name'] ?? '#${e['id']}') as String)).toList();

  @override
  Future<List<LookupItem>> channels() async =>
      (await _ds.reference('intake-channels')).map((e) => LookupItem((e as Map<String, dynamic>)['id'] as int, (e['name'] ?? '#${e['id']}') as String)).toList();
}
