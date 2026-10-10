import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../domain/entities/service_request.dart';
import '../../domain/repositories/requests_repository.dart';

class RequestFormState extends Equatable {
  const RequestFormState({this.sites = const [], this.channels = const [], this.loading = true, this.submitting = false, this.error, this.fields, this.created});
  final List<LookupItem> sites;
  final List<LookupItem> channels;
  final bool loading;
  final bool submitting;
  final String? error;
  final Map<String, List<String>>? fields;
  final ServiceRequestEntity? created;

  RequestFormState copyWith({List<LookupItem>? sites, List<LookupItem>? channels, bool? loading, bool? submitting, String? error, Map<String, List<String>>? fields, ServiceRequestEntity? created}) =>
      RequestFormState(
        sites: sites ?? this.sites,
        channels: channels ?? this.channels,
        loading: loading ?? this.loading,
        submitting: submitting ?? this.submitting,
        error: error,
        fields: fields,
        created: created ?? this.created,
      );

  @override
  List<Object?> get props => [sites, channels, loading, submitting, error, fields, created];
}

class RequestFormCubit extends Cubit<RequestFormState> {
  RequestFormCubit(this._repository) : super(const RequestFormState());
  final RequestsRepository _repository;

  Future<void> init() async {
    try {
      final s = await _repository.sites();
      final c = await _repository.channels();
      emit(state.copyWith(sites: s, channels: c, loading: false));
    } on ApiException catch (e) {
      emit(state.copyWith(loading: false, error: e.message));
    }
  }

  Future<void> submit({required int? siteId, required int? channelId, required String description, String? priority, String? requesterName}) async {
    if (siteId == null || channelId == null || description.trim().isEmpty) {
      emit(state.copyWith(error: 'الموقع وقناة الاستلام والوصف مطلوبة'));
      return;
    }
    emit(state.copyWith(submitting: true));
    try {
      final r = await _repository.create(originSiteId: siteId, channelId: channelId, description: description.trim(), suggestedPriority: priority, requesterName: requesterName);
      emit(state.copyWith(submitting: false, created: r));
    } on ApiException catch (e) {
      emit(state.copyWith(submitting: false, error: e.message, fields: e.fields));
    }
  }
}
