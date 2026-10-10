import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_states.dart';
import '../../../auth/presentation/cubit/auth_cubit.dart';
import '../../../auth/presentation/cubit/auth_state.dart';
import '../../../users/domain/entities/user.dart';
import '../../domain/entities/service_request.dart';
import '../../domain/repositories/requests_repository.dart';
import '../cubit/request_detail_cubit.dart';
import '../cubit/request_form_cubit.dart';
import '../cubit/requests_list_cubit.dart';
import 'request_detail_page.dart';
import 'request_form_page.dart';

/// M3-WEB/MOB: requests list. Scope (own site / assigned / all) is decided by the server (BR-M3 visibility).
class RequestsListPage extends StatefulWidget {
  const RequestsListPage({super.key});
  @override
  State<RequestsListPage> createState() => _RequestsListPageState();
}

class _RequestsListPageState extends State<RequestsListPage> {
  final _search = TextEditingController();

  @override
  void initState() {
    super.initState();
    context.read<RequestsListCubit>().load();
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  bool get _canCreate {
    final s = context.read<AuthCubit>().state;
    return s is AuthAuthenticated && (s.user.role == UserRole.schoolManager || s.user.role == UserRole.secretary);
  }

  Future<void> _open(int id) async {
    final repo = context.read<RequestsRepository>();
    final auth = context.read<AuthCubit>();
    await Navigator.of(context).push(MaterialPageRoute(
      builder: (_) => BlocProvider.value(
        value: auth,
        child: BlocProvider(create: (_) => RequestDetailCubit(repo, id)..load(), child: const RequestDetailPage()),
      ),
    ));
    if (mounted) context.read<RequestsListCubit>().load();
  }

  Future<void> _create() async {
    final repo = context.read<RequestsRepository>();
    final created = await Navigator.of(context).push<ServiceRequestEntity>(MaterialPageRoute(
      builder: (_) => BlocProvider(create: (_) => RequestFormCubit(repo)..init(), child: const RequestFormPage()),
    ));
    if (created != null && mounted) {
      context.read<RequestsListCubit>().load();
      _open(created.id);
    }
  }

  @override
  Widget build(BuildContext context) {
    final cubit = context.read<RequestsListCubit>();
    return Scaffold(
      appBar: AppBar(title: const Text('الطلبات')),
      floatingActionButton: _canCreate ? FloatingActionButton.extended(onPressed: _create, icon: const Icon(Icons.add), label: const Text('طلب جديد')) : null,
      body: Column(children: [
        Padding(
          padding: const EdgeInsets.all(AppSpacing.s3),
          child: Row(children: [
            Expanded(
              child: TextField(
                controller: _search,
                decoration: const InputDecoration(hintText: 'بحث برقم الطلب أو الوصف'),
                onSubmitted: (v) { cubit.query = v.trim(); cubit.load(); },
              ),
            ),
            const SizedBox(width: AppSpacing.s2),
            DropdownButton<String?>(
              value: cubit.status,
              hint: const Text('الحالة'),
              items: [
                const DropdownMenuItem<String?>(value: null, child: Text('الكل')),
                for (final e in requestStatusLabels.entries) DropdownMenuItem<String?>(value: e.key, child: Text(e.value)),
              ],
              onChanged: (v) { setState(() => cubit.status = v); cubit.load(); },
            ),
          ]),
        ),
        Expanded(
          child: BlocBuilder<RequestsListCubit, RequestsListState>(
            builder: (context, state) => switch (state) {
              RequestsListInitial() || RequestsListLoading() => const AppLoadingState(),
              RequestsListFailure(:final message) => AppErrorState(message: message, onRetry: cubit.load),
              RequestsListSuccess(:final page) when page.items.isEmpty => const AppEmptyState(title: 'لا توجد طلبات'),
              RequestsListSuccess(:final page) => Column(children: [
                  Expanded(
                    child: ListView.builder(
                      itemCount: page.items.length,
                      itemBuilder: (_, i) {
                        final r = page.items[i];
                        return Card(
                          child: ListTile(
                            title: Text('${r.refNo} — ${r.statusLabel}'),
                            subtitle: Text('${r.originSiteName ?? '#${r.originSiteId}'}\n${r.description}', maxLines: 2, overflow: TextOverflow.ellipsis),
                            isThreeLine: true,
                            onTap: () => _open(r.id),
                          ),
                        );
                      },
                    ),
                  ),
                  Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                    IconButton(onPressed: page.hasPreviousPage ? () => cubit.load(page: page.page - 1) : null, icon: const Icon(Icons.chevron_right)),
                    Text('${page.page} / ${page.totalPages}'),
                    IconButton(onPressed: page.hasNextPage ? () => cubit.load(page: page.page + 1) : null, icon: const Icon(Icons.chevron_left)),
                  ]),
                ]),
            },
          ),
        ),
      ]),
    );
  }
}
