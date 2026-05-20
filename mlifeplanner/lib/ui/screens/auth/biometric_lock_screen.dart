import 'package:flutter/material.dart';
import '../../../core/services/biometric_service.dart';
import '../../../core/theme/app_theme.dart';

/// Shows a biometric unlock screen when the app starts and
/// biometric lock is enabled. Falls back to the regular app if
/// authentication succeeds or biometric isn't available.
class BiometricLockScreen extends StatefulWidget {
  final Widget child;

  const BiometricLockScreen({super.key, required this.child});

  @override
  State<BiometricLockScreen> createState() => _BiometricLockScreenState();
}

class _BiometricLockScreenState extends State<BiometricLockScreen> with WidgetsBindingObserver {
  final BiometricService _biometricService = BiometricService();
  bool _isLocked = true;
  bool _isAuthenticating = false;
  bool _checkedSettings = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _checkAndAuthenticate();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    // Re-lock when app is paused/resumed from background
    if (state == AppLifecycleState.paused) {
      _lockAgain();
    } else if (state == AppLifecycleState.resumed && _isLocked) {
      _checkAndAuthenticate();
    }
  }

  void _lockAgain() async {
    final enabled = await _biometricService.isBiometricEnabled();
    if (enabled && mounted) {
      setState(() => _isLocked = true);
    }
  }

  Future<void> _checkAndAuthenticate() async {
    final enabled = await _biometricService.isBiometricEnabled();
    if (!enabled) {
      setState(() {
        _isLocked = false;
        _checkedSettings = true;
      });
      return;
    }

    setState(() => _checkedSettings = true);
    await _authenticate();
  }

  Future<void> _authenticate() async {
    if (_isAuthenticating) return;
    setState(() => _isAuthenticating = true);

    final success = await _biometricService.authenticate(
      reason: 'Authenticate to unlock LifePlanner',
    );

    if (mounted) {
      setState(() {
        _isAuthenticating = false;
        if (success) _isLocked = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!_checkedSettings) {
      // Still checking — show blank
      return const Scaffold(
        backgroundColor: AppTheme.background,
        body: Center(
          child: CircularProgressIndicator(color: AppTheme.primaryContainer),
        ),
      );
    }

    if (!_isLocked) {
      return widget.child;
    }

    return Scaffold(
      backgroundColor: AppTheme.background,
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(32.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: AppTheme.primaryContainer.withValues(alpha: 0.15),
                ),
                child: const Icon(
                  Icons.lock_outline_rounded,
                  size: 40,
                  color: AppTheme.primaryContainer,
                ),
              ),
              const SizedBox(height: 24),
              Text(
                'LifePlanner Locked',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              const Text(
                'Use biometrics to unlock the app',
                style: TextStyle(color: AppTheme.onSurfaceVariant),
              ),
              const SizedBox(height: 40),
              ElevatedButton.icon(
                onPressed: _isAuthenticating ? null : _authenticate,
                icon: const Icon(Icons.fingerprint, size: 24),
                label: Text(_isAuthenticating ? 'Authenticating...' : 'Unlock'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppTheme.primaryContainer,
                  foregroundColor: Colors.black,
                  padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 14),
                  textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
