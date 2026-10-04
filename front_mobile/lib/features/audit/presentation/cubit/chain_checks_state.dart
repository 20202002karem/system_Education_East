import 'package:equatable/equatable.dart';
import '../../../../core/utils/paginated.dart';
import '../../domain/entities/audit_chain_check.dart';

sealed class ChainChecksState extends Equatable {
  const ChainChecksState();
  @override
  List<Object?> get props => [];
}

class ChainChecksInitial extends ChainChecksState { const ChainChecksInitial(); }
class ChainChecksLoading extends ChainChecksState { const ChainChecksLoading(); }

class ChainChecksSuccess extends ChainChecksState {
  const ChainChecksSuccess(this.page);
  final Paginated<AuditChainCheckEntity> page;
  @override
  List<Object?> get props => [page];
}

class ChainChecksFailure extends ChainChecksState {
  const ChainChecksFailure(this.message);
  final String message;
  @override
  List<Object?> get props => [message];
}
