import 'package:equatable/equatable.dart';
import '../../../../core/utils/paginated.dart';
import '../../domain/entities/site.dart';

sealed class SitesListState extends Equatable {
  const SitesListState();
  @override
  List<Object?> get props => [];
}

class SitesListInitial extends SitesListState { const SitesListInitial(); }
class SitesListLoading extends SitesListState { const SitesListLoading(); }

class SitesListSuccess extends SitesListState {
  const SitesListSuccess(this.page);
  final Paginated<SiteEntity> page;
  @override
  List<Object?> get props => [page];
}

class SitesListFailure extends SitesListState {
  const SitesListFailure(this.message);
  final String message;
  @override
  List<Object?> get props => [message];
}
