import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../cubit/auth_cubit.dart';
import '../cubit/auth_state.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final _identifierController = TextEditingController();
  final _passwordController = TextEditingController();

  @override
  void dispose() {
    _identifierController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: BlocConsumer<AuthCubit, AuthState>(
        listener: (context, state) {
          if (state is AuthAuthenticated) {
            Navigator.of(context).popUntil((route) => route.isFirst);
          }
        },
        builder: (context, state) {
          final loading = state is AuthLoading;
          final error = state is AuthUnauthenticated ? state.error : null;

          return Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(AppSpacing.s5),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 380),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const Text('تسجيل الدخول',
                        style: TextStyle(
                            fontSize: 24, fontWeight: FontWeight.bold)),
                    const SizedBox(height: AppSpacing.s1),
                    const Text('نظام قسم الحاسوب — مديرية شرق غزة',
                        style: TextStyle(color: AppColors.textMuted)),
                    const SizedBox(height: AppSpacing.s5),
                    if (error != null) ...[
                      Container(
                        padding: const EdgeInsets.all(AppSpacing.s3),
                        decoration: BoxDecoration(
                            color: AppColors.dangerBg,
                            borderRadius: BorderRadius.circular(8)),
                        child: Text(error,
                            style: const TextStyle(color: AppColors.danger)),
                      ),
                      const SizedBox(height: AppSpacing.s4),
                    ],
                    AppTextField(
                        label: 'البريد الإلكتروني',
                        controller: _identifierController,
                        keyboardType: TextInputType.emailAddress),
                    const SizedBox(height: AppSpacing.s3),
                    AppTextField(
                        label: 'كلمة المرور',
                        controller: _passwordController,
                        obscureText: true),
                    const SizedBox(height: AppSpacing.s5),
                    AppButton(
                      label: 'دخول',
                      loading: loading,
                      onPressed: () => context.read<AuthCubit>().login(
                          _identifierController.text.trim(),
                          _passwordController.text),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
