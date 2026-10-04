import 'package:equatable/equatable.dart';
import '../../domain/entities/site.dart';
import '../../domain/entities/site_manager.dart';

sealed class SiteDetailState extends Equatable {
  const SiteDetailState();
  @override
  List<Object?> get props => [];
}

class SiteDetailInitial extends SiteDetailState { const SiteDetailInitial(); }
class SiteDetailLoading extends SiteDetailState { const SiteDetailLoading(); }

class SiteDetailSuccess extends SiteDetailState {
  const SiteDetailSuccess(this.site, this.managers, {this.actionInFlight = false, this.actionError});
  final SiteEntity site;
  final List<SiteManagerEntity> managers;
  final bool actionInFlight;
  final String? actionError;

  SiteDetailSuccess copyWith({SiteEntity? site, List<SiteManagerEntity>? managers, bool? actionInFlight, String? actionError}) {
    return SiteDetailSuccess(site ?? this.site, managers ?? this.managers, actionInFlight: actionInFlight ?? false, actionError: actionError);
  }

  @override
  List<Object?> get props => [site, managers, actionInFlight, actionError];
}

class SiteDetailFailure extends SiteDetailState {
  const SiteDetailFailure(this.message);
  final String message;
  @override
  List<Object?> get props => [message];
}
