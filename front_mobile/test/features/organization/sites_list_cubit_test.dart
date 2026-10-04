import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:moehe_eastgaza_m1/core/network/api_exception.dart';
import 'package:moehe_eastgaza_m1/core/utils/paginated.dart';
import 'package:moehe_eastgaza_m1/features/organization/domain/entities/site.dart';
import 'package:moehe_eastgaza_m1/features/organization/domain/repositories/organization_repository.dart';
import 'package:moehe_eastgaza_m1/features/organization/presentation/cubit/sites_list_cubit.dart';
import 'package:moehe_eastgaza_m1/features/organization/presentation/cubit/sites_list_state.dart';

class MockOrganizationRepository extends Mock implements OrganizationRepository {}

void main() {
  late MockOrganizationRepository repository;

  const site = SiteEntity(id: 12, type: SiteType.school, code: 'SCH-012', nameAr: 'مدرسة الأمل', status: SiteStatus.active);

  setUp(() => repository = MockOrganizationRepository());

  blocTest<SitesListCubit, SitesListState>(
    'emits [Loading, Success] on load()',
    build: () {
      when(() => repository.listSites(page: any(named: 'page'))).thenAnswer(
        (_) async => const Paginated(items: [site], page: 1, perPage: 20, total: 1),
      );
      return SitesListCubit(repository);
    },
    act: (cubit) => cubit.load(),
    expect: () => [
      const SitesListLoading(),
      const SitesListSuccess(Paginated(items: [site], page: 1, perPage: 20, total: 1)),
    ],
  );

  blocTest<SitesListCubit, SitesListState>(
    'emits [Loading, Failure] when a non-chairman role gets 403 (Sites read is chairman-only, Batch 3 §4)',
    build: () {
      when(() => repository.listSites(page: any(named: 'page')))
          .thenThrow(const ApiException(statusCode: 403, code: 'forbidden', message: 'لا صلاحية لتنفيذ هذا الإجراء'));
      return SitesListCubit(repository);
    },
    act: (cubit) => cubit.load(),
    expect: () => [const SitesListLoading(), const SitesListFailure('لا صلاحية لتنفيذ هذا الإجراء')],
  );
}
