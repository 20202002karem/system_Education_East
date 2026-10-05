import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_snackbar.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../../domain/entities/asset.dart';
import '../../domain/repositories/assets_repository.dart';

/// Create (API-AST-01) or edit descriptive data (API-AST-04: category + holder only).
/// Category/site are entered by id: M1 exposes those lists only to the chairman
/// (recorded as an OPEN DECISION in the M2 report).
class AssetFormPage extends StatefulWidget {
  const AssetFormPage({super.key, this.asset});
  final AssetEntity? asset;

  @override
  State<AssetFormPage> createState() => _AssetFormPageState();
}

class _AssetFormPageState extends State<AssetFormPage> {
  late final _inventory = TextEditingController();
  late final _serial = TextEditingController();
  late final _category = TextEditingController(text: widget.asset?.categoryId.toString() ?? '');
  late final _site = TextEditingController();
  late final _holder = TextEditingController(text: widget.asset?.holderText ?? '');
  late final _legacy = TextEditingController();
  bool _submitting = false;
  Map<String, List<String>>? _errors;

  bool get _isEdit => widget.asset != null;

  @override
  void dispose() {
    for (final c in [_inventory, _serial, _category, _site, _holder, _legacy]) {
      c.dispose();
    }
    super.dispose();
  }

  String? _err(String key) => _errors?[key]?.first;

  Future<void> _submit() async {
    setState(() { _submitting = true; _errors = null; });
    final repo = context.read<AssetsRepository>();
    try {
      final holder = _holder.text.trim();
      if (_isEdit) {
        await repo.update(widget.asset!.id,
            version: widget.asset!.version,
            categoryId: int.tryParse(_category.text.trim()),
            holderText: holder.isEmpty ? null : holder,
            clearHolder: holder.isEmpty);
      } else {
        await repo.create(
          inventoryNo: _inventory.text.trim(),
          serialNo: _serial.text.trim().isEmpty ? null : _serial.text.trim(),
          categoryId: int.tryParse(_category.text.trim()) ?? 0,
          currentSiteId: int.tryParse(_site.text.trim()) ?? 0,
          holderText: holder.isEmpty ? null : holder,
          legacyNumbers: _legacy.text.split('\n').map((e) => e.trim()).where((e) => e.isNotEmpty).toList(),
        );
      }
      if (!mounted) return;
      showAppSnackBar(context, _isEdit ? 'تم حفظ التعديلات' : 'تمت إضافة الجهاز', tone: SnackTone.success);
      Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _errors = e.fields);
      showAppSnackBar(context, e.message, tone: SnackTone.error);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(_isEdit ? 'تعديل بيانات الجهاز' : 'إضافة جهاز')),
      body: ListView(padding: const EdgeInsets.all(AppSpacing.s4), children: [
        if (!_isEdit) ...[
          AppTextField(label: 'رقم الجرد', controller: _inventory, errorText: _err('inventory_no')),
          const SizedBox(height: AppSpacing.s3),
          AppTextField(label: 'الرقم التسلسلي (اختياري)', controller: _serial, errorText: _err('serial_no')),
          const SizedBox(height: AppSpacing.s3),
        ],
        AppTextField(label: 'معرّف التصنيف', controller: _category, keyboardType: TextInputType.number, errorText: _err('category_id')),
        const SizedBox(height: AppSpacing.s3),
        if (!_isEdit) ...[
          AppTextField(label: 'معرّف الموقع', controller: _site, keyboardType: TextInputType.number, errorText: _err('current_site_id')),
          const SizedBox(height: AppSpacing.s3),
        ],
        AppTextField(label: 'الحائز', controller: _holder, errorText: _err('holder_text')),
        if (!_isEdit) ...[
          const SizedBox(height: AppSpacing.s3),
          TextField(
            controller: _legacy,
            maxLines: 3,
            decoration: InputDecoration(labelText: 'أرقام الجرد القديمة (رقم في كل سطر)', errorText: _err('legacy_numbers')),
          ),
        ],
        const SizedBox(height: AppSpacing.s5),
        AppButton(label: 'حفظ', loading: _submitting, onPressed: _submit),
      ]),
    );
  }
}
