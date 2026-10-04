import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_snackbar.dart';
import '../../../../core/widgets/app_states.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../../../../core/widgets/confirm_dialog.dart';
import '../../domain/entities/site.dart';
import '../../domain/entities/site_manager.dart';
import '../../domain/repositories/organization_repository.dart';
import '../cubit/site_detail_cubit.dart';
import '../cubit/site_detail_state.dart';
import 'site_form_page.dart';

const _typeLabels = {SiteType.school: 'مدرسة', SiteType.department: 'قسم', SiteType.warehouse: 'Warehouse'};

class SiteDetailPage extends StatelessWidget {
  const SiteDetailPage({super.key, required this.siteId});
  final int siteId;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) => SiteDetailCubit(context.read<OrganizationRepository>(), siteId)..load(),
      child: const _SiteDetailView(),
    );
  }
}

class _SiteDetailView extends StatelessWidget {
  const _SiteDetailView();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('تفاصيل الموقع')),
      body: BlocConsumer<SiteDetailCubit, SiteDetailState>(
        listener: (context, state) {
          if (state is SiteDetailSuccess && state.actionError != null) {
            showAppSnackBar(context, state.actionError!, tone: SnackTone.error);
          }
        },
        builder: (context, state) {
          return switch (state) {
            SiteDetailInitial() || SiteDetailLoading() => const AppLoadingState(),
            SiteDetailFailure(:final message) => AppErrorState(message: message, onRetry: () => context.read<SiteDetailCubit>().load()),
            SiteDetailSuccess() => _Body(state: state),
          };
        },
      ),
    );
  }
}

class _Body extends StatelessWidget {
  const _Body({required this.state});
  final SiteDetailSuccess state;

  @override
  Widget build(BuildContext context) {
    final site = state.site;
    final cubit = context.read<SiteDetailCubit>();

    return ListView(
      padding: const EdgeInsets.all(AppSpacing.s4),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.s4),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                Text(site.nameAr, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                Chip(label: Text(site.status == SiteStatus.active ? 'فعّال' : 'مؤرشف')),
              ]),
              Text('${site.code} · ${_typeLabels[site.type]}', style: const TextStyle(color: AppColors.textMuted)),
              const SizedBox(height: AppSpacing.s3),
              Wrap(spacing: AppSpacing.s2, children: [
                AppButton(
                  label: 'تعديل',
                  variant: AppButtonVariant.secondary,
                  onPressed: () async {
                    final saved = await Navigator.of(context).push<bool>(
                      MaterialPageRoute(builder: (_) => SiteFormPage(mode: SiteFormMode.edit, site: site)),
                    );
                    if (saved == true && context.mounted) cubit.load();
                  },
                ),
                if (site.status == SiteStatus.active)
                  AppButton(
                    label: 'أرشفة',
                    variant: AppButtonVariant.danger,
                    loading: state.actionInFlight,
                    onPressed: () async {
                      final confirmed = await showConfirmDialog(context,
                          title: 'أرشفة الموقع؟', description: 'لا يوجد حذف فعلي — الأرشفة فقط.', danger: true);
                      if (confirmed) cubit.archive();
                    },
                  ),
              ]),
            ]),
          ),
        ),
        const SizedBox(height: AppSpacing.s4),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.s4),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                const Text('مسؤولو الموقع', style: TextStyle(fontWeight: FontWeight.bold)),
                AppButton(
                  label: 'تعيين مسؤول',
                  variant: AppButtonVariant.secondary,
                  onPressed: () => _showAssignSheet(context, cubit),
                ),
              ]),
              const SizedBox(height: AppSpacing.s2),
              if (state.managers.isEmpty)
                const AppEmptyState(title: 'لا يوجد سجل لمسؤولي الموقع')
              else
                ...state.managers.map((m) => ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: Text('User #${m.userId}'),
                      subtitle: Text('${_fmtDate(m.from)} → ${m.to != null ? _fmtDate(m.to!) : 'ساري حاليًا'}'),
                      trailing: m.isActive
                          ? TextButton(
                              onPressed: () async {
                                final confirmed = await showConfirmDialog(context, title: 'إنهاء فترة التولي؟', danger: true);
                                if (confirmed) cubit.endManager(m.id);
                              },
                              child: const Text('إنهاء', style: TextStyle(color: AppColors.danger)),
                            )
                          : null,
                    )),
            ]),
          ),
        ),
      ],
    );
  }

  String _fmtDate(DateTime d) => '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  void _showAssignSheet(BuildContext context, SiteDetailCubit cubit) {
    final userIdController = TextEditingController();
    DateTime from = DateTime.now();
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(
          left: AppSpacing.s4, right: AppSpacing.s4, top: AppSpacing.s4,
          bottom: MediaQuery.of(ctx).viewInsets.bottom + AppSpacing.s4,
        ),
        child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          const Text('تعيين مسؤول موقع', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          const SizedBox(height: AppSpacing.s3),
          AppTextField(label: 'معرّف المستخدم (User ID)', controller: userIdController, keyboardType: TextInputType.number),
          const SizedBox(height: AppSpacing.s3),
          Text('سيُنهى تلقائيًا تولي المسؤول الحالي الساري، إن وُجد.', style: const TextStyle(color: AppColors.textMuted, fontSize: 12)),
          const SizedBox(height: AppSpacing.s4),
          AppButton(
            label: 'تعيين',
            onPressed: () {
              final userId = int.tryParse(userIdController.text);
              if (userId != null) {
                cubit.assignManager(userId, from);
                Navigator.of(ctx).pop();
              }
            },
          ),
        ]),
      ),
    );
  }
}
