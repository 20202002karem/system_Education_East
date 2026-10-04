import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_states.dart';
import '../../domain/entities/user.dart';
import '../cubit/users_list_cubit.dart';
import '../cubit/users_list_state.dart';
import 'user_detail_page.dart';
import 'user_form_page.dart';

const _roleLabels = {
  UserRole.chairman: 'رئيس القسم',
  UserRole.secretary: 'السكرتير',
  UserRole.engineer: 'مهندس',
  UserRole.technician: 'فني',
  UserRole.schoolManager: 'مدير مدرسة',
};

/// Batch 3 §2.2 GET /users. Chairman-only route — see AppRouter's role guard.
class UsersListPage extends StatefulWidget {
  const UsersListPage({super.key});

  @override
  State<UsersListPage> createState() => _UsersListPageState();
}

class _UsersListPageState extends State<UsersListPage> {
  @override
  void initState() {
    super.initState();
    context.read<UsersListCubit>().load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('المستخدمون'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () async {
              final saved = await Navigator.of(context).push<bool>(
                MaterialPageRoute(builder: (_) => const UserFormPage(mode: UserFormMode.create)),
              );
              if (saved == true && context.mounted) context.read<UsersListCubit>().load(page: 1);
            },
          ),
        ],
      ),
      body: BlocBuilder<UsersListCubit, UsersListState>(
        builder: (context, state) {
          return switch (state) {
            UsersListInitial() || UsersListLoading() => const AppLoadingState(),
            UsersListFailure(:final message) => AppErrorState(message: message, onRetry: () => context.read<UsersListCubit>().load()),
            UsersListSuccess(:final page) when page.items.isEmpty =>
              const AppEmptyState(title: 'لا يوجد مستخدمون', hint: 'أنشئ حسابًا جديدًا من الزر أعلاه'),
            UsersListSuccess(:final page) => RefreshIndicator(
                onRefresh: () => context.read<UsersListCubit>().load(page: page.page),
                child: ListView.separated(
                  padding: const EdgeInsets.all(AppSpacing.s4),
                  itemCount: page.items.length,
                  separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.s2),
                  itemBuilder: (context, i) {
                    final user = page.items[i];
                    return Card(
                      child: ListTile(
                        title: Text(user.name),
                        subtitle: Text('${_roleLabels[user.role]} · ${user.email}'),
                        trailing: Chip(
                          label: Text(user.status == UserStatus.active ? 'فعّال' : 'معطّل'),
                          backgroundColor: user.status == UserStatus.active ? AppColors.successBg : AppColors.disabledBg,
                        ),
                        onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => UserDetailPage(userId: user.id))),
                      ),
                    );
                  },
                ),
              ),
          };
        },
      ),
    );
  }
}
