import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:intl/intl.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_snackbar.dart';
import '../../../../core/widgets/app_states.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../../../../core/widgets/confirm_dialog.dart';
import '../../../users/domain/entities/user.dart';
import '../../domain/entities/asset.dart';
import '../../domain/repositories/assets_repository.dart';
import '../cubit/asset_detail_cubit.dart';
import '../cubit/asset_detail_state.dart';
import 'asset_form_page.dart';

String _fmt(DateTime? d) => d == null ? '—' : DateFormat.yMMMd('ar').add_jm().format(d);

/// API-AST-03/06/08/09 + actions 04/05/07. Correction is shown to the chairman only (DD-2).
class AssetDetailPage extends StatelessWidget {
  const AssetDetailPage({super.key, required this.assetId, required this.role});
  final int assetId;
  final UserRole role;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (ctx) => AssetDetailCubit(ctx.read<AssetsRepository>(), assetId)..load(),
      child: _View(role: role),
    );
  }
}

class _View extends StatelessWidget {
  const _View({required this.role});
  final UserRole role;

  Future<void> _statusDialog(BuildContext context, AssetEntity asset) async {
    var to = manualAssetStatuses.firstWhere((s) => s != asset.status);
    final reason = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setState) => AlertDialog(
          title: const Text('تحديث حالة الجهاز'),
          content: Column(mainAxisSize: MainAxisSize.min, children: [
            DropdownButtonFormField<AssetStatus>(
              value: to,
              decoration: const InputDecoration(labelText: 'الحالة الجديدة'),
              items: [for (final s in manualAssetStatuses) DropdownMenuItem(value: s, child: Text(assetStatusLabel(s)))],
              onChanged: (v) => setState(() => to = v ?? to),
            ),
            const SizedBox(height: AppSpacing.s3),
            AppTextField(label: 'السبب (اختياري)', controller: reason),
          ]),
          actions: [
            AppButton(label: 'إلغاء', variant: AppButtonVariant.secondary, onPressed: () => Navigator.of(ctx).pop(false)),
            AppButton(label: 'تحديث', onPressed: to == asset.status ? null : () => Navigator.of(ctx).pop(true)),
          ],
        ),
      ),
    );
    if (ok != true || !context.mounted) return;
    try {
      await context.read<AssetsRepository>().changeStatus(asset.id, to: to, reason: reason.text.trim().isEmpty ? null : reason.text.trim(), version: asset.version);
      if (context.mounted) showAppSnackBar(context, 'تم تحديث الحالة', tone: SnackTone.success);
    } on ApiException catch (e) {
      if (context.mounted) showAppSnackBar(context, e.message, tone: SnackTone.error);
    }
    if (context.mounted) context.read<AssetDetailCubit>().load();
  }

  Future<void> _correctionDialog(BuildContext context, AssetEntity asset) async {
    var field = IdentifierField.inventoryNo;
    final value = TextEditingController();
    final reason = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setState) => AlertDialog(
          title: const Text('تصحيح رقم حساس'),
          content: SingleChildScrollView(
            child: Column(mainAxisSize: MainAxisSize.min, children: [
              DropdownButtonFormField<IdentifierField>(
                value: field,
                decoration: const InputDecoration(labelText: 'الحقل'),
                items: [for (final f in IdentifierField.values) DropdownMenuItem(value: f, child: Text(identifierFieldLabel(f)))],
                onChanged: (v) => setState(() => field = v ?? field),
              ),
              const SizedBox(height: AppSpacing.s3),
              AppTextField(label: 'القيمة الجديدة', controller: value, onChanged: (_) => setState(() {})),
              const SizedBox(height: AppSpacing.s3),
              AppTextField(label: 'سبب التصحيح', controller: reason, onChanged: (_) => setState(() {})),
            ]),
          ),
          actions: [
            AppButton(label: 'إلغاء', variant: AppButtonVariant.secondary, onPressed: () => Navigator.of(ctx).pop(false)),
            AppButton(
              label: 'متابعة',
              variant: AppButtonVariant.danger,
              onPressed: value.text.trim().isEmpty || reason.text.trim().isEmpty ? null : () => Navigator.of(ctx).pop(true),
            ),
          ],
        ),
      ),
    );
    if (ok != true || !context.mounted) return;
    final confirmed = await showConfirmDialog(
      context,
      title: 'تأكيد تصحيح الرقم',
      description: 'سيتم تغيير ${identifierFieldLabel(field)} إلى «${value.text.trim()}». تبقى القيمة القديمة في السجل.',
      confirmLabel: 'تأكيد التصحيح',
      danger: true,
    );
    if (!confirmed || !context.mounted) return;
    try {
      await context.read<AssetsRepository>().correctIdentifier(asset.id, field: field, newValue: value.text.trim(), reason: reason.text.trim());
      if (context.mounted) showAppSnackBar(context, 'تم تصحيح الرقم وتسجيله في السجل', tone: SnackTone.success);
    } on ApiException catch (e) {
      if (context.mounted) showAppSnackBar(context, e.message, tone: SnackTone.error);
    }
    if (context.mounted) context.read<AssetDetailCubit>().load();
  }

  Widget _section(String title, List<Widget> children, {String emptyLabel = 'لا توجد سجلات'}) => Card(
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.s4),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            const SizedBox(height: AppSpacing.s2),
            if (children.isEmpty) Text(emptyLabel, style: const TextStyle(color: AppColors.textMuted)) else ...children,
          ]),
        ),
      );

  @override
  Widget build(BuildContext context) {
    final canWrite = role != UserRole.schoolManager; // UX only; server enforces
    final isChairman = role == UserRole.chairman;
    return Scaffold(
      appBar: AppBar(title: const Text('بطاقة الجهاز')),
      body: BlocBuilder<AssetDetailCubit, AssetDetailState>(
        builder: (context, state) => switch (state) {
          AssetDetailLoading() => const AppLoadingState(),
          AssetDetailFailure(:final message) => AppErrorState(message: message, onRetry: () => context.read<AssetDetailCubit>().load()),
          AssetDetailSuccess(:final asset, :final statusHistory, :final corrections, :final legacyNumbers) => ListView(
              padding: const EdgeInsets.all(AppSpacing.s4),
              children: [
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(AppSpacing.s4),
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(asset.inventoryNo, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                      const SizedBox(height: AppSpacing.s2),
                      Text('الرقم التسلسلي: ${asset.serialNo ?? '—'}'),
                      Text('الحالة: ${assetStatusLabel(asset.status)}'),
                      Text('معرّف التصنيف: ${asset.categoryId}'),
                      Text('معرّف الموقع: ${asset.currentSiteId}'),
                      Text('الحائز: ${asset.holderText ?? '—'}'),
                      Text('آخر تحديث: ${_fmt(asset.updatedAt)}'),
                      const SizedBox(height: AppSpacing.s3),
                      Wrap(spacing: AppSpacing.s2, runSpacing: AppSpacing.s2, children: [
                        if (canWrite)
                          AppButton(
                            label: 'تعديل',
                            variant: AppButtonVariant.secondary,
                            onPressed: () async {
                              final saved = await Navigator.of(context).push<bool>(MaterialPageRoute(builder: (_) => AssetFormPage(asset: asset)));
                              if (saved == true && context.mounted) context.read<AssetDetailCubit>().load();
                            },
                          ),
                        if (canWrite) AppButton(label: 'تحديث الحالة', variant: AppButtonVariant.secondary, onPressed: () => _statusDialog(context, asset)),
                        if (isChairman) AppButton(label: 'تصحيح الأرقام', variant: AppButtonVariant.danger, onPressed: () => _correctionDialog(context, asset)),
                      ]),
                    ]),
                  ),
                ),
                _section('سجل الحالات', [
                  for (final e in statusHistory)
                    ListTile(
                      dense: true,
                      title: Text('${assetStatusLabel(e.fromStatus)} ← ${assetStatusLabel(e.toStatus)}'),
                      subtitle: Text('${e.reason ?? '—'} · ${_fmt(e.changedAt)}'),
                    ),
                ]),
                _section('سجل تصحيح الأرقام', [
                  for (final c in corrections)
                    ListTile(
                      dense: true,
                      title: Text('${identifierFieldLabel(c.field)}: ${c.oldValue} ← ${c.newValue}'),
                      subtitle: Text('${c.reason} · ${_fmt(c.correctedAt)}'),
                    ),
                ]),
                _section('أرقام الجرد القديمة', [
                  for (final l in legacyNumbers)
                    ListTile(dense: true, title: Text(l.legacyNumber), subtitle: Text('${l.source ?? '—'} · ${_fmt(l.addedAt)}')),
                ]),
              ],
            ),
        },
      ),
    );
  }
}
