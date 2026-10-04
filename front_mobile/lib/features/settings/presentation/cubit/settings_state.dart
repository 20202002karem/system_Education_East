import 'package:equatable/equatable.dart';
import '../../domain/entities/setting.dart';

sealed class SettingsState extends Equatable {
  const SettingsState();
  @override
  List<Object?> get props => [];
}

class SettingsInitial extends SettingsState { const SettingsInitial(); }
class SettingsLoading extends SettingsState { const SettingsLoading(); }

class SettingsSuccess extends SettingsState {
  const SettingsSuccess(this.settings, {this.savingKey});
  final List<SettingEntity> settings;
  final String? savingKey;

  SettingsSuccess copyWith({List<SettingEntity>? settings, String? savingKey}) =>
      SettingsSuccess(settings ?? this.settings, savingKey: savingKey);

  @override
  List<Object?> get props => [settings, savingKey];
}

class SettingsFailure extends SettingsState {
  const SettingsFailure(this.message);
  final String message;
  @override
  List<Object?> get props => [message];
}
