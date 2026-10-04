import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../domain/repositories/organization_repository.dart';
import 'site_detail_state.dart';

/// Batch 3 §3.5 — assigning a new manager auto-closes the previously active
/// one server-side; this cubit just reloads the managers list after either action.
class SiteDetailCubit extends Cubit<SiteDetailState> {
  SiteDetailCubit(this._repository, this.siteId) : super(const SiteDetailInitial());
  final OrganizationRepository _repository;
  final int siteId;

  Future<void> load() async {
    emit(const SiteDetailLoading());
    try {
      final site = await _repository.getSite(siteId);
      final managers = await _repository.listManagers(siteId);
      emit(SiteDetailSuccess(site, managers));
    } on ApiException catch (e) {
      emit(SiteDetailFailure(e.message));
    }
  }

  Future<void> archive() => _runAction((s) async {
        final site = await _repository.archiveSite(siteId);
        return s.copyWith(site: site);
      });

  Future<void> assignManager(int userId, DateTime from) => _runAction((s) async {
        await _repository.assignManager(siteId, userId: userId, from: from);
        final managers = await _repository.listManagers(siteId);
        return s.copyWith(managers: managers);
      });

  Future<void> endManager(int siteManagerId) => _runAction((s) async {
        await _repository.endManager(siteManagerId);
        final managers = await _repository.listManagers(siteId);
        return s.copyWith(managers: managers);
      });

  Future<void> _runAction(Future<SiteDetailSuccess> Function(SiteDetailSuccess current) action) async {
    final current = state;
    if (current is! SiteDetailSuccess) return;
    emit(current.copyWith(actionInFlight: true));
    try {
      final next = await action(current);
      emit(next.copyWith(actionInFlight: false));
    } on ApiException catch (e) {
      emit(current.copyWith(actionInFlight: false, actionError: e.message));
    }
  }
}
