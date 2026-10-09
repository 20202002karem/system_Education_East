import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_snackbar.dart';
import '../../../../core/widgets/app_states.dart';
import '../cubit/request_form_cubit.dart';

/// Create request (M3-API-002). Required: origin site, intake channel, description. Priority is only a suggestion.
class RequestFormPage extends StatefulWidget {
  const RequestFormPage({super.key});
  @override
  State<RequestFormPage> createState() => _RequestFormPageState();
}

class _RequestFormPageState extends State<RequestFormPage> {
  int? _site;
  int? _channel;
  String? _priority;
  final _description = TextEditingController();
  final _requesterName = TextEditingController();

  static const _priorities = {'emergency': 'طارئة', 'high': 'عالية', 'normal': 'عادية', 'low': 'منخفضة'};

  @override
  void dispose() {
    _description.dispose();
    _requesterName.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<RequestFormCubit, RequestFormState>(
      listener: (context, s) {
        if (s.created != null) {
          showAppSnackBar(context, 'تم تسجيل الطلب ${s.created!.refNo}', tone: SnackTone.success);
          Navigator.of(context).pop(s.created);
        } else if (s.error != null) {
          showAppSnackBar(context, s.error!, tone: SnackTone.error);
        }
      },
      builder: (context, s) => Scaffold(
        appBar: AppBar(title: const Text('طلب جديد')),
        body: s.loading
            ? const AppLoadingState()
            : ListView(padding: const EdgeInsets.all(AppSpacing.s4), children: [
                DropdownButtonFormField<int>(
                  value: _site,
                  decoration: InputDecoration(labelText: 'الموقع *', errorText: s.fields?['origin_site_id']?.first),
                  items: [for (final i in s.sites) DropdownMenuItem(value: i.id, child: Text(i.name))],
                  onChanged: (v) => setState(() => _site = v),
                ),
                const SizedBox(height: AppSpacing.s3),
                DropdownButtonFormField<int>(
                  value: _channel,
                  decoration: InputDecoration(labelText: 'قناة الاستلام *', errorText: s.fields?['channel_id']?.first),
                  items: [for (final i in s.channels) DropdownMenuItem(value: i.id, child: Text(i.name))],
                  onChanged: (v) => setState(() => _channel = v),
                ),
                const SizedBox(height: AppSpacing.s3),
                TextField(
                  controller: _description,
                  maxLines: 4,
                  decoration: InputDecoration(labelText: 'وصف المشكلة *', errorText: s.fields?['description']?.first),
                ),
                const SizedBox(height: AppSpacing.s3),
                DropdownButtonFormField<String>(
                  value: _priority,
                  decoration: const InputDecoration(labelText: 'الأولوية المقترحة (اختياري)'),
                  items: [for (final e in _priorities.entries) DropdownMenuItem(value: e.key, child: Text(e.value))],
                  onChanged: (v) => setState(() => _priority = v),
                ),
                const SizedBox(height: AppSpacing.s3),
                TextField(controller: _requesterName, decoration: const InputDecoration(labelText: 'اسم مقدّم الطلب (اختياري)')),
                const SizedBox(height: AppSpacing.s4),
                AppButton(
                  label: 'تسجيل الطلب',
                  loading: s.submitting,
                  onPressed: () => context.read<RequestFormCubit>().submit(
                        siteId: _site,
                        channelId: _channel,
                        description: _description.text,
                        priority: _priority,
                        requesterName: _requesterName.text.trim(),
                      ),
                ),
              ]),
      ),
    );
  }
}
