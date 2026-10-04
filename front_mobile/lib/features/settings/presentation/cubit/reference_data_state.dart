import 'package:equatable/equatable.dart';
import '../../domain/entities/reference_item.dart';

sealed class ReferenceDataState extends Equatable {
  const ReferenceDataState();
  @override
  List<Object?> get props => [];
}

class ReferenceDataInitial extends ReferenceDataState { const ReferenceDataInitial(); }
class ReferenceDataLoading extends ReferenceDataState { const ReferenceDataLoading(); }

class ReferenceDataSuccess extends ReferenceDataState {
  const ReferenceDataSuccess(this.resource, this.items);
  final ReferenceResource resource;
  final List<ReferenceItemEntity> items;
  @override
  List<Object?> get props => [resource, items];
}

class ReferenceDataFailure extends ReferenceDataState {
  const ReferenceDataFailure(this.message);
  final String message;
  @override
  List<Object?> get props => [message];
}
