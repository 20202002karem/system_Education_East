import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_snackbar.dart';
import '../../../../core/widgets/app_states.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../../domain/entities/setting.dart';
import '../cubit/settings_cubit.dart';
import '../cubit/settings_state.dart';

const _settingLabels = {
  'reopen_window_days': 'مدة إعادة فتح الطلب (أيام) — D-21',
  'approval_validity_days': 'مدة صلاحية الاعتماد (أيام) — D-21',
  'transfer_reminder_days': 'مدة تذكير النقل (أيام) — D-21',
  'late_approval_hours': 'مهلة الاعتماد اللاحق (ساعات) — D-21',
  'session_inactivity_minutes': 'مهلة الخمول (دقائق) — MD-01',
  'max_failed_login_attempts': 'عدد المحاولات قبل القفل — MD-01',
  'lockout_minutes': 'مدة القفل (دقائق) — MD-01',
  'attachment_size_limit': 'حد حجم المرفقات — D-34 (Pending)',
};

class SettingsPage extends StatefulWidget {
  const SettingsPage({super.key});

  @override
  State<SettingsPage> createState() => _SettingsPageState();
}

class _SettingsPageState extends State<SettingsPage> {
  final Map<String, TextEditingController> _controllers = {};

  @override
  void initState() {
    super.initState();
    context.read<SettingsCubit>().load();
  }

  @override
  void dispose() {
    for (final c in _controllers.values) {
      c.dispose();
    }
    super.dispose();
  }

  TextEditingController _controllerFor(SettingEntity s) {
    return _controllers.putIfAbsent(s.key, () => TextEditingController(text: s.value ?? ''));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('الإعدادات')),
      body: BlocConsumer<SettingsCubit, SettingsState>(
        listener: (context, state) {},
        builder: (context, state) {
          return switch (state) {
            SettingsInitial() || SettingsLoading() => const AppLoadingState(),
            SettingsFailure(:final message) => AppErrorState(message: message, onRetry: () => context.read<SettingsCubit>().load()),
            SettingsSuccess(:final settings, :final savingKey) => ListView.builder(
                padding: const EdgeInsets.all(AppSpacing.s4),
                itemCount: settings.length,
                itemBuilder: (context, i) {
                  final s = settings[i];
                  final controller = _controllerFor(s);
                  return Padding(
                    padding: const EdgeInsets.only(bottom: AppSpacing.s3),
                    child: Row(children: [
                      Expanded(
                        child: AppTextField(
                          label: _settingLabels[s.key] ?? s.key,
                          controller: controller,
                          hintText: s.value == null ? 'لا قيمة محددة بعد' : null,
                        ),
                      ),
                      const SizedBox(width: AppSpacing.s2),
                      AppButton(
                        label: 'حفظ',
                        loading: savingKey == s.key,
                        onPressed: () async {
                          try {
                            await context.read<SettingsCubit>().update(s.key, controller.text);
                            if (context.mounted) showAppSnackBar(context, 'تم حفظ الإعداد وتسجيله في السجل التاريخي', tone: SnackTone.success);
                          } catch (_) {
                            if (context.mounted) showAppSnackBar(context, 'تعذّر الحفظ', tone: SnackTone.error);
                          }
                        },
                      ),
                    ]),
                  );
                },
              ),
          };
        },
      ),
    );
  }
}
