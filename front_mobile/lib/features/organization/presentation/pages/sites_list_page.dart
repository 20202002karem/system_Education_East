import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_states.dart';
import '../../domain/entities/site.dart';
import '../cubit/sites_list_cubit.dart';
import '../cubit/sites_list_state.dart';
import 'site_detail_page.dart';
import 'site_form_page.dart';

const _typeLabels = {SiteType.school: 'مدرسة', SiteType.department: 'قسم', SiteType.warehouse: 'Warehouse'};

/// Batch 3 §2.3 GET /sites — chairman-only (read AND write in M1, §4).
class SitesListPage extends StatefulWidget {
  const SitesListPage({super.key});

  @override
  State<SitesListPage> createState() => _SitesListPageState();
}

class _SitesListPageState extends State<SitesListPage> {
  @override
  void initState() {
    super.initState();
    context.read<SitesListCubit>().load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('المواقع'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () async {
              final saved = await Navigator.of(context).push<bool>(MaterialPageRoute(builder: (_) => const SiteFormPage(mode: SiteFormMode.create)));
              if (saved == true && context.mounted) context.read<SitesListCubit>().load();
            },
          ),
        ],
      ),
      body: BlocBuilder<SitesListCubit, SitesListState>(
        builder: (context, state) {
          return switch (state) {
            SitesListInitial() || SitesListLoading() => const AppLoadingState(),
            SitesListFailure(:final message) => AppErrorState(message: message, onRetry: () => context.read<SitesListCubit>().load()),
            SitesListSuccess(:final page) when page.items.isEmpty => const AppEmptyState(title: 'لا توجد مواقع'),
            SitesListSuccess(:final page) => ListView.separated(
                padding: const EdgeInsets.all(AppSpacing.s4),
                itemCount: page.items.length,
                separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.s2),
                itemBuilder: (context, i) {
                  final site = page.items[i];
                  return Card(
                    child: ListTile(
                      title: Text(site.nameAr),
                      subtitle: Text('${site.code} · ${_typeLabels[site.type]}'),
                      trailing: Chip(
                        label: Text(site.status == SiteStatus.active ? 'فعّال' : 'مؤرشف'),
                        backgroundColor: site.status == SiteStatus.active ? AppColors.successBg : AppColors.disabledBg,
                      ),
                      onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => SiteDetailPage(siteId: site.id))),
                    ),
                  );
                },
              ),
          };
        },
      ),
    );
  }
}
