import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_states.dart';
import '../../../users/domain/entities/user.dart';
import '../../domain/entities/asset.dart';
import '../cubit/assets_list_cubit.dart';
import '../cubit/assets_list_state.dart';
import 'asset_detail_page.dart';
import 'asset_form_page.dart';

const _sortOptions = {
  'inventory_no': 'رقم الجرد (تصاعدي)',
  '-inventory_no': 'رقم الجرد (تنازلي)',
  '-created_at': 'الأحدث',
  'created_at': 'الأقدم',
};

/// API-AST-02 GET /assets — scope is applied by the server; filters are server-side.
class AssetsListPage extends StatefulWidget {
  const AssetsListPage({super.key, required this.role, this.onLogout});
  final UserRole role;
  final VoidCallback? onLogout; // set when this list is the landing screen (non-chairman roles)

  @override
  State<AssetsListPage> createState() => _AssetsListPageState();
}

class _AssetsListPageState extends State<AssetsListPage> {
  final _search = TextEditingController();

  @override
  void initState() {
    super.initState();
    context.read<AssetsListCubit>().load();
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  Color _bg(AssetStatus s) => switch (s) {
        AssetStatus.working => AppColors.successBg,
        AssetStatus.underMaintenance => AppColors.warningBg,
        AssetStatus.broken => AppColors.dangerBg,
        _ => AppColors.disabledBg,
      };

  @override
  Widget build(BuildContext context) {
    final cubit = context.read<AssetsListCubit>();
    final canCreate = widget.role != UserRole.schoolManager; // UX only; server enforces
    return Scaffold(
      appBar: AppBar(
        title: const Text('الأجهزة'),
        actions: [
          if (widget.onLogout != null) IconButton(icon: const Icon(Icons.logout), tooltip: 'تسجيل الخروج', onPressed: widget.onLogout),
          if (canCreate)
            IconButton(
              icon: const Icon(Icons.add),
              tooltip: 'إضافة جهاز',
              onPressed: () async {
                final saved = await Navigator.of(context).push<bool>(MaterialPageRoute(builder: (_) => const AssetFormPage()));
                if (saved == true && context.mounted) cubit.load();
              },
            ),
        ],
      ),
      body: Column(children: [
        Padding(
          padding: const EdgeInsets.all(AppSpacing.s3),
          child: Column(children: [
            TextField(
              controller: _search,
              textInputAction: TextInputAction.search,
              decoration: const InputDecoration(labelText: 'بحث (رقم الجرد / التسلسلي)', prefixIcon: Icon(Icons.search)),
              onSubmitted: (v) { cubit.query = v; cubit.load(); },
            ),
            const SizedBox(height: AppSpacing.s2),
            Row(children: [
              Expanded(
                child: DropdownButtonFormField<AssetStatus?>(
                  value: cubit.status,
                  decoration: const InputDecoration(labelText: 'الحالة'),
                  items: [
                    const DropdownMenuItem(value: null, child: Text('الكل')),
                    for (final s in manualAssetStatuses) DropdownMenuItem(value: s, child: Text(assetStatusLabel(s))),
                  ],
                  onChanged: (v) { cubit.status = v; cubit.load(); },
                ),
              ),
              const SizedBox(width: AppSpacing.s2),
              Expanded(
                child: DropdownButtonFormField<String>(
                  value: cubit.sort,
                  decoration: const InputDecoration(labelText: 'الترتيب'),
                  items: [for (final e in _sortOptions.entries) DropdownMenuItem(value: e.key, child: Text(e.value))],
                  onChanged: (v) { if (v != null) { cubit.sort = v; cubit.load(); } },
                ),
              ),
            ]),
          ]),
        ),
        Expanded(
          child: BlocBuilder<AssetsListCubit, AssetsListState>(
            builder: (context, state) => switch (state) {
              AssetsListInitial() || AssetsListLoading() => const AppLoadingState(),
              AssetsListFailure(:final message) => AppErrorState(message: message, onRetry: () => cubit.load()),
              AssetsListSuccess(:final page) when page.items.isEmpty => const AppEmptyState(title: 'لا توجد أجهزة'),
              AssetsListSuccess(:final page) => Column(children: [
                  Expanded(
                    child: ListView.separated(
                      padding: const EdgeInsets.all(AppSpacing.s4),
                      itemCount: page.items.length,
                      separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.s2),
                      itemBuilder: (context, i) {
                        final a = page.items[i];
                        return Card(
                          child: ListTile(
                            title: Text(a.inventoryNo),
                            subtitle: Text(a.serialNo ?? '—'),
                            trailing: Chip(label: Text(assetStatusLabel(a.status)), backgroundColor: _bg(a.status)),
                            onTap: () async {
                              await Navigator.of(context).push(MaterialPageRoute(builder: (_) => AssetDetailPage(assetId: a.id, role: widget.role)));
                              if (context.mounted) cubit.load(page: page.page);
                            },
                          ),
                        );
                      },
                    ),
                  ),
                  if (page.totalPages > 1)
                    Padding(
                      padding: const EdgeInsets.all(AppSpacing.s2),
                      child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                        IconButton(icon: const Icon(Icons.chevron_right), onPressed: page.hasPreviousPage ? () => cubit.load(page: page.page - 1) : null),
                        Text('${page.page} / ${page.totalPages}'),
                        IconButton(icon: const Icon(Icons.chevron_left), onPressed: page.hasNextPage ? () => cubit.load(page: page.page + 1) : null),
                      ]),
                    ),
                ]),
            },
          ),
        ),
      ]),
    );
  }
}
