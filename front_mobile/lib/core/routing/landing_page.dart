import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../features/auth/presentation/cubit/auth_cubit.dart';
import '../theme/app_theme.dart';

/// Simple M1 landing/navigation — instructions §15 explicitly say NOT to
/// invent a dashboard with M2 statistics when the docs don't call for one.
/// This is plain navigation into the four M1 areas, nothing more.
class LandingPage extends StatelessWidget {
  const LandingPage({super.key});

  @override
  Widget build(BuildContext context) {
    final tiles = [
      ('الأجهزة', Icons.computer_outlined, '/assets'),
      ('المستخدمون', Icons.people_outline, '/users'),
      ('المواقع', Icons.apartment_outlined, '/sites'),
      ('الإعدادات', Icons.tune_outlined, '/settings'),
      ('القوائم المرجعية', Icons.list_alt_outlined, '/reference'),
      ('سجل التدقيق', Icons.fact_check_outlined, '/audit'),
    ];

    return Scaffold(
      appBar: AppBar(
        title: const Text('نظام قسم الحاسوب'),
        actions: [IconButton(icon: const Icon(Icons.logout), onPressed: () => context.read<AuthCubit>().logout())],
      ),
      body: GridView.count(
        padding: const EdgeInsets.all(AppSpacing.s4),
        crossAxisCount: 2,
        mainAxisSpacing: AppSpacing.s3,
        crossAxisSpacing: AppSpacing.s3,
        childAspectRatio: 1.3,
        children: tiles.map((t) {
          return Card(
            child: InkWell(
              onTap: () => Navigator.of(context).pushNamed(t.$3),
              child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                Icon(t.$2, size: 32, color: AppColors.primary),
                const SizedBox(height: AppSpacing.s2),
                Text(t.$1),
              ]),
            ),
          );
        }).toList(),
      ),
    );
  }
}
