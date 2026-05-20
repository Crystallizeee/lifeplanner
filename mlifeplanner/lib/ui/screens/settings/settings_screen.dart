import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/services/biometric_service.dart';
import '../../../core/services/notification_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../providers/auth_provider.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final BiometricService _biometricService = BiometricService();
  final NotificationService _notificationService = NotificationService();

  bool _biometricAvailable = false;
  bool _biometricEnabled = false;
  bool _notificationsEnabled = false;
  TimeOfDay _reminderTime = const TimeOfDay(hour: 8, minute: 0);
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadSettings();
  }

  Future<void> _loadSettings() async {
    final canBiometric = await _biometricService.canCheckBiometrics();
    final deviceSupported = await _biometricService.isDeviceSupported();
    final biometricEnabled = await _biometricService.isBiometricEnabled();
    final notifEnabled = await _notificationService.isNotificationsEnabled();
    final reminderTime = await _notificationService.getReminderTime();

    if (mounted) {
      setState(() {
        _biometricAvailable = canBiometric && deviceSupported;
        _biometricEnabled = biometricEnabled;
        _notificationsEnabled = notifEnabled;
        _reminderTime = reminderTime;
        _isLoading = false;
      });
    }
  }

  Future<void> _toggleBiometric(bool value) async {
    if (value) {
      // Verify biometric before enabling
      final authenticated = await _biometricService.authenticate(
        reason: 'Authenticate to enable biometric lock',
      );
      if (!authenticated) return;
    }

    await _biometricService.setBiometricEnabled(value);
    setState(() => _biometricEnabled = value);

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(value ? 'Biometric lock enabled' : 'Biometric lock disabled'),
          backgroundColor: AppTheme.primaryContainer,
        ),
      );
    }
  }

  Future<void> _toggleNotifications(bool value) async {
    await _notificationService.setNotificationsEnabled(value);
    setState(() => _notificationsEnabled = value);

    if (value) {
      await _notificationService.scheduleDailyHabitReminder(time: _reminderTime);
    }

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(value ? 'Reminders enabled' : 'Reminders disabled'),
          backgroundColor: AppTheme.primaryContainer,
        ),
      );
    }
  }

  Future<void> _pickReminderTime() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: _reminderTime,
      builder: (context, child) {
        return Theme(
          data: ThemeData.dark().copyWith(
            colorScheme: const ColorScheme.dark(
              primary: AppTheme.primaryContainer,
              onPrimary: Colors.black,
              surface: AppTheme.surface,
              onSurface: AppTheme.onBackground,
            ),
          ),
          child: child!,
        );
      },
    );

    if (picked != null && picked != _reminderTime) {
      await _notificationService.setReminderTime(picked);
      setState(() => _reminderTime = picked);

      if (_notificationsEnabled) {
        await _notificationService.cancelNotification(100);
        await _notificationService.scheduleDailyHabitReminder(time: picked);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Settings'),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryContainer))
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                // ── Profile Card ──
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(20.0),
                    child: Row(
                      children: [
                        CircleAvatar(
                          radius: 28,
                          backgroundColor: AppTheme.primaryContainer.withValues(alpha: 0.15),
                          child: Text(
                            (user?.name ?? 'U')[0].toUpperCase(),
                            style: const TextStyle(
                              fontSize: 22,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.primaryContainer,
                            ),
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                user?.name ?? 'User',
                                style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                user?.email ?? '',
                                style: const TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 13),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

                const SizedBox(height: 24),

                // ── Security Section ──
                _buildSectionHeader(Icons.security_outlined, 'Security'),
                const SizedBox(height: 8),
                Card(
                  child: Column(
                    children: [
                      SwitchListTile(
                        title: const Text('Biometric Lock'),
                        subtitle: Text(
                          _biometricAvailable
                              ? 'Require fingerprint or face to open the app'
                              : 'Biometric not available on this device',
                          style: const TextStyle(fontSize: 12, color: AppTheme.onSurfaceVariant),
                        ),
                        value: _biometricEnabled,
                        onChanged: _biometricAvailable ? _toggleBiometric : null,
                        activeColor: AppTheme.primaryContainer,
                        secondary: const Icon(Icons.fingerprint, color: AppTheme.primary),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 24),

                // ── Notifications Section ──
                _buildSectionHeader(Icons.notifications_outlined, 'Notifications'),
                const SizedBox(height: 8),
                Card(
                  child: Column(
                    children: [
                      SwitchListTile(
                        title: const Text('Daily Reminders'),
                        subtitle: const Text(
                          'Get daily habit & task check-in reminders',
                          style: TextStyle(fontSize: 12, color: AppTheme.onSurfaceVariant),
                        ),
                        value: _notificationsEnabled,
                        onChanged: _toggleNotifications,
                        activeColor: AppTheme.primaryContainer,
                        secondary: const Icon(Icons.alarm, color: AppTheme.primary),
                      ),
                      if (_notificationsEnabled) ...[
                        const Divider(height: 1, indent: 16, endIndent: 16),
                        ListTile(
                          leading: const Icon(Icons.access_time, color: AppTheme.primary),
                          title: const Text('Reminder Time'),
                          subtitle: Text(
                            _reminderTime.format(context),
                            style: const TextStyle(fontSize: 12, color: AppTheme.onSurfaceVariant),
                          ),
                          trailing: const Icon(Icons.chevron_right, color: AppTheme.onSurfaceVariant),
                          onTap: _pickReminderTime,
                        ),
                      ],
                    ],
                  ),
                ),

                const SizedBox(height: 24),

                // ── Account Section ──
                _buildSectionHeader(Icons.person_outline, 'Account'),
                const SizedBox(height: 8),
                Card(
                  child: ListTile(
                    leading: const Icon(Icons.logout, color: AppTheme.error),
                    title: const Text('Logout'),
                    subtitle: const Text(
                      'Sign out of your account',
                      style: TextStyle(fontSize: 12, color: AppTheme.onSurfaceVariant),
                    ),
                    onTap: () {
                      showDialog(
                        context: context,
                        builder: (ctx) => AlertDialog(
                          backgroundColor: AppTheme.surface,
                          title: const Text('Logout'),
                          content: const Text('Are you sure you want to sign out?'),
                          actions: [
                            TextButton(
                              onPressed: () => Navigator.pop(ctx),
                              child: const Text('Cancel'),
                            ),
                            ElevatedButton(
                              onPressed: () {
                                Navigator.pop(ctx);
                                context.read<AuthProvider>().logout();
                                Navigator.of(context).popUntil((route) => route.isFirst);
                              },
                              style: ElevatedButton.styleFrom(backgroundColor: AppTheme.error),
                              child: const Text('Logout', style: TextStyle(color: Colors.black)),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
                ),

                const SizedBox(height: 32),

                // ── App info ──
                Center(
                  child: Text(
                    'LifePlanner SIM v1.0',
                    style: TextStyle(
                      color: AppTheme.onSurfaceVariant.withValues(alpha: 0.5),
                      fontSize: 12,
                    ),
                  ),
                ),
              ],
            ),
    );
  }

  Widget _buildSectionHeader(IconData icon, String title) {
    return Row(
      children: [
        Icon(icon, size: 18, color: AppTheme.onSurfaceVariant),
        const SizedBox(width: 8),
        Text(
          title,
          style: Theme.of(context).textTheme.titleSmall?.copyWith(
            color: AppTheme.onSurfaceVariant,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}
