import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_snackbar.dart';
import '../../../../core/widgets/app_states.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../../../../core/widgets/confirm_dialog.dart';
import '../../domain/entities/permission_grant.dart';
import '../../domain/entities/user.dart';
import '../../domain/repositories/users_repository.dart';
import '../cubit/user_detail_cubit.dart';
import '../cubit/user_detail_state.dart';
import 'user_form_page.dart';

const _permissionLabels = {
  PermissionKey.initiateTransfer: 'بدء نقل',
  PermissionKey.initiateDecommission: 'بدء إخراج',
  PermissionKey.editAssets: 'تعديل أجهزة',
};

class UserDetailPage extends StatelessWidget {
  const UserDetailPage({super.key, required this.userId});
  final int userId;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) => UserDetailCubit(context.read<UsersRepository>(), userId)..load(),
      child: const _UserDetailView(),
    );
  }
}

class _UserDetailView extends StatelessWidget {
  const _UserDetailView();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('تفاصيل المستخدم')),
      body: BlocConsumer<UserDetailCubit, UserDetailState>(
        listener: (context, state) {
          if (state is UserDetailSuccess && state.actionError != null) {
            showAppSnackBar(context, state.actionError!, tone: SnackTone.error);
          }
        },
        builder: (context, state) {
          return switch (state) {
            UserDetailInitial() || UserDetailLoading() => const AppLoadingState(),
            UserDetailFailure(:final message) => AppErrorState(message: message, onRetry: () => context.read<UserDetailCubit>().load()),
            UserDetailSuccess() => _DetailBody(state: state),
          };
        },
      ),
    );
  }
}

class _DetailBody extends StatefulWidget {
  const _DetailBody({required this.state});
  final UserDetailSuccess state;

  @override
  State<_DetailBody> createState() => _DetailBodyState();
}

class _DetailBodyState extends State<_DetailBody> {
  PermissionKey _grantKey = PermissionKey.initiateTransfer;
  final _reasonController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _showResetField = false;

  @override
  void dispose() {
    _reasonController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final user = widget.state.user;
    final cubit = context.read<UserDetailCubit>();

    return ListView(
      padding: const EdgeInsets.all(AppSpacing.s4),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.s4),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                Text(user.name, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                Chip(label: Text(user.status == UserStatus.active ? 'فعّال' : 'معطّل')),
              ]),
              Text(user.email, style: const TextStyle(color: AppColors.textMuted)),
              const SizedBox(height: AppSpacing.s3),
              Wrap(spacing: AppSpacing.s2, children: [
                AppButton(
                  label: 'تعديل',
                  variant: AppButtonVariant.secondary,
                  onPressed: () async {
                    final saved = await Navigator.of(context).push<bool>(
                      MaterialPageRoute(builder: (_) => UserFormPage(mode: UserFormMode.edit, user: user)),
                    );
                    if (saved == true && context.mounted) cubit.load();
                  },
                ),
                if (user.status == UserStatus.active)
                  AppButton(
                    label: 'تعطيل',
                    variant: AppButtonVariant.danger,
                    loading: widget.state.actionInFlight,
                    onPressed: () async {
                      final confirmed = await showConfirmDialog(context, title: 'تعطيل الحساب؟', danger: true);
                      if (confirmed) cubit.disable();
                    },
                  )
                else
                  AppButton(label: 'تفعيل', loading: widget.state.actionInFlight, onPressed: cubit.enable),
              ]),
            ]),
          ),
        ),

        const SizedBox(height: AppSpacing.s4),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.s4),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              const Text('الجلسات', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: AppSpacing.s2),
              AppButton(
                label: 'إنهاء كل الجلسات',
                variant: AppButtonVariant.danger,
                loading: widget.state.actionInFlight,
                onPressed: () async {
                  final confirmed = await showConfirmDialog(context, title: 'إنهاء كل الجلسات؟', danger: true);
                  if (confirmed) cubit.terminateSessions();
                },
              ),
            ]),
          ),
        ),

        const SizedBox(height: AppSpacing.s4),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.s4),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              const Text('إعادة تعيين كلمة المرور', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: AppSpacing.s2),
              if (!_showResetField)
                AppButton(label: 'إعادة تعيين', variant: AppButtonVariant.secondary, onPressed: () => setState(() => _showResetField = true))
              else ...[
                AppTextField(label: 'كلمة المرور الجديدة', controller: _passwordController, obscureText: true),
                const SizedBox(height: AppSpacing.s2),
                AppButton(
                  label: 'تأكيد',
                  loading: widget.state.actionInFlight,
                  onPressed: () {
                    cubit.resetPassword(_passwordController.text);
                    setState(() => _showResetField = false);
                  },
                ),
              ],
            ]),
          ),
        ),

        const SizedBox(height: AppSpacing.s4),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.s4),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              const Text('الصلاحيات الفردية', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: AppSpacing.s2),
              DropdownButtonFormField<PermissionKey>(
                value: _grantKey,
                decoration: const InputDecoration(labelText: 'الصلاحية'),
                items: _permissionLabels.entries.map((e) => DropdownMenuItem(value: e.key, child: Text(e.value))).toList(),
                onChanged: (v) => setState(() => _grantKey = v!),
              ),
              const SizedBox(height: AppSpacing.s2),
              AppTextField(label: 'السبب (اختياري)', controller: _reasonController),
              const SizedBox(height: AppSpacing.s2),
              AppButton(
                label: 'منح',
                loading: widget.state.actionInFlight,
                onPressed: () => cubit.grantPermission(_grantKey, reason: _reasonController.text.isEmpty ? null : _reasonController.text),
              ),
              const SizedBox(height: AppSpacing.s3),
              if (widget.state.grants.isEmpty)
                const AppEmptyState(title: 'لا توجد صلاحيات فردية')
              else
                ...widget.state.grants.map((g) => ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: Text(_permissionLabels[g.permissionKey]!),
                      subtitle: Text(g.isActive ? 'فعّالة' : 'مسحوبة'),
                      trailing: g.isActive
                          ? TextButton(onPressed: () => cubit.revokePermission(g.id), child: const Text('سحب', style: TextStyle(color: AppColors.danger)))
                          : null,
                    )),
            ]),
          ),
        ),
      ],
    );
  }
}
