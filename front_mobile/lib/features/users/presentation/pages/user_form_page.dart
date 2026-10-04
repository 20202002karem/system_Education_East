import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_snackbar.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../../domain/entities/user.dart';
import '../../domain/repositories/users_repository.dart';

enum UserFormMode { create, edit }

/// Batch 3 §3.2 — site_scope_ids required iff view_scope=sites (422 otherwise).
class UserFormPage extends StatefulWidget {
  const UserFormPage({super.key, required this.mode, this.user});
  final UserFormMode mode;
  final UserEntity? user;

  @override
  State<UserFormPage> createState() => _UserFormPageState();
}

class _UserFormPageState extends State<UserFormPage> {
  late final _nameController = TextEditingController(text: widget.user?.name ?? '');
  late final _emailController = TextEditingController(text: widget.user?.email ?? '');
  late final _specializationController = TextEditingController(text: widget.user?.specialization ?? '');
  late final _siteScopeController = TextEditingController();

  late UserRole _role = widget.user?.role ?? UserRole.technician;
  late ViewScope _viewScope = widget.user?.viewScope ?? ViewScope.assignedOnly;
  bool _submitting = false;
  Map<String, List<String>>? _fieldErrors;
  String? _formError;

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _specializationController.dispose();
    _siteScopeController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() {
      _submitting = true;
      _fieldErrors = null;
      _formError = null;
    });
    final repository = context.read<UsersRepository>();
    final siteIds = _siteScopeController.text.split(',').map((s) => s.trim()).where((s) => s.isNotEmpty).map(int.parse).toList();

    try {
      if (widget.mode == UserFormMode.create) {
        await repository.create(
          name: _nameController.text,
          loginIdentifier: _emailController.text,
          role: _role,
          viewScope: _viewScope,
          specialization: _specializationController.text.isEmpty ? null : _specializationController.text,
          siteScopeIds: _viewScope == ViewScope.sites ? siteIds : null,
        );
      } else {
        await repository.update(
          widget.user!.id,
          name: _nameController.text,
          role: _role,
          viewScope: _viewScope,
          specialization: _specializationController.text,
          siteScopeIds: _viewScope == ViewScope.sites ? siteIds : null,
        );
      }
      if (mounted) Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (e.isValidation && e.fields != null) {
        setState(() => _fieldErrors = e.fields);
      } else {
        setState(() => _formError = e.message);
        if (mounted) showAppSnackBar(context, e.message, tone: SnackTone.error);
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isCreate = widget.mode == UserFormMode.create;
    return Scaffold(
      appBar: AppBar(title: Text(isCreate ? 'إنشاء حساب' : 'تعديل حساب')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.s4),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (_formError != null) ...[
              Container(
                padding: const EdgeInsets.all(AppSpacing.s3),
                decoration: BoxDecoration(color: AppColors.dangerBg, borderRadius: BorderRadius.circular(8)),
                child: Text(_formError!, style: const TextStyle(color: AppColors.danger)),
              ),
              const SizedBox(height: AppSpacing.s3),
            ],
            AppTextField(label: 'الاسم', controller: _nameController, errorText: _fieldErrors?['name']?.first),
            const SizedBox(height: AppSpacing.s3),
            AppTextField(
              label: 'البريد الإلكتروني',
              controller: _emailController,
              enabled: isCreate,
              keyboardType: TextInputType.emailAddress,
              errorText: _fieldErrors?['login_identifier']?.first,
            ),
            const SizedBox(height: AppSpacing.s3),
            DropdownButtonFormField<UserRole>(
              value: _role,
              decoration: const InputDecoration(labelText: 'الدور'),
              items: const [
                DropdownMenuItem(value: UserRole.chairman, child: Text('رئيس القسم')),
                DropdownMenuItem(value: UserRole.secretary, child: Text('السكرتير')),
                DropdownMenuItem(value: UserRole.engineer, child: Text('مهندس')),
                DropdownMenuItem(value: UserRole.technician, child: Text('فني')),
                DropdownMenuItem(value: UserRole.schoolManager, child: Text('مدير مدرسة')),
              ],
              onChanged: (v) => setState(() => _role = v!),
            ),
            const SizedBox(height: AppSpacing.s3),
            DropdownButtonFormField<ViewScope>(
              value: _viewScope,
              decoration: const InputDecoration(labelText: 'نطاق العرض'),
              items: const [
                DropdownMenuItem(value: ViewScope.ownSite, child: Text('موقعه فقط')),
                DropdownMenuItem(value: ViewScope.sites, child: Text('مواقع محددة')),
                DropdownMenuItem(value: ViewScope.all, child: Text('كل القسم')),
                DropdownMenuItem(value: ViewScope.assignedOnly, child: Text('ما أُسند إليه فقط')),
              ],
              onChanged: (v) => setState(() => _viewScope = v!),
            ),
            if (_viewScope == ViewScope.sites) ...[
              const SizedBox(height: AppSpacing.s3),
              AppTextField(
                label: 'مواقع النطاق (معرّفات مفصولة بفاصلة)',
                controller: _siteScopeController,
                hintText: 'مثال: 12,47',
                errorText: _fieldErrors?['site_scope_ids']?.first,
              ),
            ],
            const SizedBox(height: AppSpacing.s3),
            AppTextField(label: 'التخصص (اختياري)', controller: _specializationController),
            const SizedBox(height: AppSpacing.s5),
            AppButton(label: 'حفظ', loading: _submitting, onPressed: _submit),
          ],
        ),
      ),
    );
  }
}
