import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_states.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../../domain/entities/reference_item.dart';
import '../cubit/reference_data_cubit.dart';
import '../cubit/reference_data_state.dart';

const _tabs = [
  (ReferenceResource.deviceCategories, 'تصنيفات الأجهزة'),
  (ReferenceResource.requestTypes, 'أنواع الطلبات'),
  (ReferenceResource.taskTypes, 'أنواع المهام'),
  (ReferenceResource.intakeChannels, 'قنوات الاستقبال'),
];

/// Batch 2 §B12 / Batch 3 §2.4 — same pattern repeated for all four lists.
class ReferenceDataPage extends StatefulWidget {
  const ReferenceDataPage({super.key});

  @override
  State<ReferenceDataPage> createState() => _ReferenceDataPageState();
}

class _ReferenceDataPageState extends State<ReferenceDataPage> {
  final _newNameController = TextEditingController();

  @override
  void initState() {
    super.initState();
    context.read<ReferenceDataCubit>().load(ReferenceResource.deviceCategories);
  }

  @override
  void dispose() {
    _newNameController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('القوائم المرجعية')),
      body: Column(
        children: [
          SizedBox(
            height: 48,
            child: BlocBuilder<ReferenceDataCubit, ReferenceDataState>(
              builder: (context, state) {
                final current = state is ReferenceDataSuccess ? state.resource : ReferenceResource.deviceCategories;
                return ListView(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: AppSpacing.s3),
                  children: _tabs.map((t) {
                    final selected = t.$1 == current;
                    return Padding(
                      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.s1),
                      child: ChoiceChip(
                        label: Text(t.$2),
                        selected: selected,
                        onSelected: (_) => context.read<ReferenceDataCubit>().load(t.$1),
                      ),
                    );
                  }).toList(),
                );
              },
            ),
          ),
          const Divider(height: 1),
          Padding(
            padding: const EdgeInsets.all(AppSpacing.s3),
            child: Row(children: [
              Expanded(child: AppTextField(label: 'اسم جديد', controller: _newNameController)),
              const SizedBox(width: AppSpacing.s2),
              AppButton(
                label: 'إضافة',
                onPressed: () {
                  if (_newNameController.text.trim().isEmpty) return;
                  context.read<ReferenceDataCubit>().create(_newNameController.text.trim());
                  _newNameController.clear();
                },
              ),
            ]),
          ),
          Expanded(
            child: BlocBuilder<ReferenceDataCubit, ReferenceDataState>(
              builder: (context, state) {
                return switch (state) {
                  ReferenceDataInitial() || ReferenceDataLoading() => const AppLoadingState(),
                  ReferenceDataFailure(:final message) =>
                    AppErrorState(message: message, onRetry: () => context.read<ReferenceDataCubit>().load(ReferenceResource.deviceCategories)),
                  ReferenceDataSuccess(:final items) when items.isEmpty => const AppEmptyState(title: 'لا توجد عناصر بعد'),
                  ReferenceDataSuccess(:final items, :final resource) => ListView.builder(
                      itemCount: items.length,
                      itemBuilder: (context, i) {
                        final item = items[i];
                        return ListTile(
                          title: Text(item.name),
                          subtitle: resource == ReferenceResource.taskTypes
                              ? Text('إداري: ${item.isAdministrative == true ? 'نعم' : 'لا'} · يُسند من السكرتير: ${item.secretaryAssignable == true ? 'نعم' : 'لا'}')
                              : null,
                          trailing: item.isActive != null
                              ? Switch(value: item.isActive!, onChanged: (_) => context.read<ReferenceDataCubit>().toggleActive(item))
                              : null,
                        );
                      },
                    ),
                };
              },
            ),
          ),
        ],
      ),
    );
  }
}
