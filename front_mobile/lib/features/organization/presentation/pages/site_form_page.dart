import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_snackbar.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../../domain/entities/site.dart';
import '../../domain/repositories/organization_repository.dart';

enum SiteFormMode { create, edit }

class SiteFormPage extends StatefulWidget {
  const SiteFormPage({super.key, required this.mode, this.site});
  final SiteFormMode mode;
  final SiteEntity? site;

  @override
  State<SiteFormPage> createState() => _SiteFormPageState();
}

class _SiteFormPageState extends State<SiteFormPage> {
  late final _codeController = TextEditingController(text: widget.site?.code ?? '');
  late final _nameController = TextEditingController(text: widget.site?.nameAr ?? '');
  late SiteType _type = widget.site?.type ?? SiteType.school;
  bool _submitting = false;
  Map<String, List<String>>? _fieldErrors;

  @override
  void dispose() {
    _codeController.dispose();
    _nameController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() { _submitting = true; _fieldErrors = null; });
    final repository = context.read<OrganizationRepository>();
    try {
      if (widget.mode == SiteFormMode.create) {
        await repository.createSite(type: _type, code: _codeController.text, nameAr: _nameController.text);
      } else {
        await repository.updateSite(widget.site!.id, type: _type, code: _codeController.text, nameAr: _nameController.text);
      }
      if (mounted) Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if ((e.isValidation || e.isConflict) && e.fields != null) {
        setState(() => _fieldErrors = e.fields);
      } else if (e.isConflict) {
        setState(() => _fieldErrors = {'code': ['رمز الموقع مستخدم مسبقًا']});
      } else if (mounted) {
        showAppSnackBar(context, e.message, tone: SnackTone.error);
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.mode == SiteFormMode.create ? 'إنشاء موقع' : 'تعديل موقع')),
      body: Padding(
        padding: const EdgeInsets.all(AppSpacing.s4),
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          DropdownButtonFormField<SiteType>(
            value: _type,
            decoration: const InputDecoration(labelText: 'النوع'),
            items: const [
              DropdownMenuItem(value: SiteType.school, child: Text('مدرسة')),
              DropdownMenuItem(value: SiteType.department, child: Text('قسم')),
              DropdownMenuItem(value: SiteType.warehouse, child: Text('Warehouse')),
            ],
            onChanged: (v) => setState(() => _type = v!),
          ),
          const SizedBox(height: AppSpacing.s3),
          AppTextField(label: 'الرمز', controller: _codeController, errorText: _fieldErrors?['code']?.first),
          const SizedBox(height: AppSpacing.s3),
          AppTextField(label: 'الاسم', controller: _nameController, errorText: _fieldErrors?['name_ar']?.first),
          const SizedBox(height: AppSpacing.s5),
          AppButton(label: 'حفظ', loading: _submitting, onPressed: _submit),
        ]),
      ),
    );
  }
}
