import 'package:flutter/material.dart';
import 'app_button.dart';

/// Multi-field prompt used by M3 transition actions (hold reason, close, cancel, reopen, notes).
/// Returns the entered values keyed by field key, or null when dismissed.
class PromptField {
  const PromptField(this.key, this.label, {this.required = true, this.numeric = false, this.multiline = false});
  final String key;
  final String label;
  final bool required;
  final bool numeric;
  final bool multiline;
}

Future<Map<String, String>?> showPromptDialog(BuildContext context, {required String title, required List<PromptField> fields, String submitLabel = 'تأكيد'}) {
  final controllers = {for (final f in fields) f.key: TextEditingController()};
  String? error;
  return showDialog<Map<String, String>>(
    context: context,
    builder: (ctx) => StatefulBuilder(
      builder: (ctx, setState) => AlertDialog(
        title: Text(title),
        content: SingleChildScrollView(
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            for (final f in fields)
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: TextField(
                  controller: controllers[f.key],
                  keyboardType: f.numeric ? TextInputType.number : (f.multiline ? TextInputType.multiline : null),
                  maxLines: f.multiline ? 3 : 1,
                  decoration: InputDecoration(labelText: f.required ? '${f.label} *' : f.label),
                ),
              ),
            if (error != null) Text(error!, style: const TextStyle(color: Colors.red)),
          ]),
        ),
        actions: [
          AppButton(label: 'إلغاء', variant: AppButtonVariant.secondary, onPressed: () => Navigator.of(ctx).pop()),
          AppButton(
            label: submitLabel,
            onPressed: () {
              final out = {for (final f in fields) f.key: controllers[f.key]!.text.trim()};
              final missing = fields.any((f) => f.required && out[f.key]!.isEmpty);
              final badNum = fields.any((f) => f.numeric && out[f.key]!.isNotEmpty && int.tryParse(out[f.key]!) == null);
              if (missing || badNum) {
                setState(() => error = 'يرجى تعبئة الحقول المطلوبة بقيم صحيحة');
                return;
              }
              Navigator.of(ctx).pop(out);
            },
          ),
        ],
      ),
    ),
  );
}
