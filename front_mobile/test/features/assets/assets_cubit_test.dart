import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:moehe_eastgaza_m1/core/network/api_exception.dart';
import 'package:moehe_eastgaza_m1/core/utils/paginated.dart';
import 'package:moehe_eastgaza_m1/features/assets/domain/entities/asset.dart';
import 'package:moehe_eastgaza_m1/features/assets/domain/repositories/assets_repository.dart';
import 'package:moehe_eastgaza_m1/features/assets/presentation/cubit/asset_detail_cubit.dart';
import 'package:moehe_eastgaza_m1/features/assets/presentation/cubit/asset_detail_state.dart';
import 'package:moehe_eastgaza_m1/features/assets/presentation/cubit/assets_list_cubit.dart';
import 'package:moehe_eastgaza_m1/features/assets/presentation/cubit/assets_list_state.dart';

class MockRepo extends Mock implements AssetsRepository {}

const _asset = AssetEntity(
  id: 5, inventoryNo: 'PC-1', serialNo: null, categoryId: 3, currentSiteId: 12,
  status: AssetStatus.working, holderText: null, version: 1, updatedAt: null,
);

void main() {
  late MockRepo repo;
  setUp(() => repo = MockRepo());

  test('status wire mapping covers the six documented values and rejects unknown', () {
    for (final s in AssetStatus.values) {
      expect(assetStatusFromWire(assetStatusToWire(s)), s);
    }
    expect(() => assetStatusFromWire('archived'), throwsArgumentError);
    expect(manualAssetStatuses, isNot(contains(AssetStatus.inTransfer)));
    expect(manualAssetStatuses, isNot(contains(AssetStatus.decommissioned)));
  });

  blocTest<AssetsListCubit, AssetsListState>(
    'list emits loading then success',
    build: () {
      when(() => repo.list(page: any(named: 'page'), q: any(named: 'q'), status: any(named: 'status'), sort: any(named: 'sort')))
          .thenAnswer((_) async => const Paginated(items: [_asset], page: 1, perPage: 20, total: 1));
      return AssetsListCubit(repo);
    },
    act: (c) => c.load(),
    expect: () => [isA<AssetsListLoading>(), isA<AssetsListSuccess>()],
  );

  blocTest<AssetsListCubit, AssetsListState>(
    'list emits failure with the API message',
    build: () {
      when(() => repo.list(page: any(named: 'page'), q: any(named: 'q'), status: any(named: 'status'), sort: any(named: 'sort')))
          .thenThrow(const ApiException(statusCode: 500, code: 'http_500', message: 'خطأ'));
      return AssetsListCubit(repo);
    },
    act: (c) => c.load(),
    expect: () => [isA<AssetsListLoading>(), isA<AssetsListFailure>()],
  );

  blocTest<AssetDetailCubit, AssetDetailState>(
    'detail maps 404 to a not-found message',
    build: () {
      when(() => repo.get(5)).thenThrow(const ApiException(statusCode: 404, code: 'not_found', message: 'x'));
      return AssetDetailCubit(repo, 5);
    },
    act: (c) => c.load(),
    expect: () => [isA<AssetDetailLoading>(), const AssetDetailFailure('الجهاز غير موجود')],
  );
}
