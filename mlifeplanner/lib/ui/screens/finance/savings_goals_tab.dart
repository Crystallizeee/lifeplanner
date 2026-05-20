import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/services/api_service.dart';
import '../../../core/theme/app_theme.dart';

class SavingsGoalsTab extends StatefulWidget {
  const SavingsGoalsTab({super.key});

  @override
  State<SavingsGoalsTab> createState() => _SavingsGoalsTabState();
}

class _SavingsGoalsTabState extends State<SavingsGoalsTab> {
  final ApiService _apiService = ApiService();
  bool _isLoading = true;
  List<dynamic> _goals = [];
  String? _errorMessage;

  // Totals
  double _totalSaved = 0.0;
  double _totalTarget = 0.0;
  double _totalProgress = 0.0;

  final List<String> _goalIcons = ['🎯', '💰', '🏠', '🚗', '✈️', '🎓', '💻', '💍', '🛡️', '🎁'];

  @override
  void initState() {
    super.initState();
    _fetchGoals();
  }

  Future<void> _fetchGoals() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final response = await _apiService.get('/savings-goals');
      if (response['success'] == true) {
        final List<dynamic> data = response['data'] ?? [];
        setState(() {
          _goals = data;
          _calculateTotals();
          _isLoading = false;
        });
      } else {
        setState(() {
          _errorMessage = response['message'] ?? 'Failed to load savings goals.';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
        _isLoading = false;
      });
    }
  }

  void _calculateTotals() {
    double savedSum = 0.0;
    double targetSum = 0.0;

    for (var g in _goals) {
      savedSum += (g['current_saved'] as num).toDouble();
      targetSum += (g['target_amount'] as num).toDouble();
    }

    _totalSaved = savedSum;
    _totalTarget = targetSum;
    _totalProgress = targetSum > 0 ? (savedSum / targetSum) : 0.0;
  }

  String _formatCurrency(double amount) {
    final parts = amount.toStringAsFixed(0).split('.');
    final reg = RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))');
    final formatted = parts[0].replaceAllMapped(reg, (Match m) => '${m[1]}.');
    return formatted;
  }

  String _formatDate(String rawDate) {
    try {
      final dt = DateTime.parse(rawDate);
      final months = [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
      ];
      return '${dt.day} ${months[dt.month - 1]} ${dt.year}';
    } catch (_) {
      return rawDate;
    }
  }

  // ── Bottom Sheets & Dialogs ──

  void _showAddGoalSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppTheme.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => _GoalFormSheet(
        apiService: _apiService,
        goalIcons: _goalIcons,
        onSuccess: () {
          Navigator.pop(context);
          _fetchGoals();
          _showToast('New savings goal created successfully!', isError: false);
        },
      ),
    );
  }

  void _showQuickTopUpDialog(dynamic goal) {
    final controller = TextEditingController();
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: AppTheme.surface,
        title: Text(
          'Quick Top Up / Adjust',
          style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.bold),
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Goal: ${goal['goal_name']}',
              style: const TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 13),
            ),
            Text(
              'Current: Rp ${_formatCurrency((goal['current_saved'] as num).toDouble())}',
              style: const TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 12),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: controller,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: 'New Cumulative Saved (Rp)',
                hintText: 'Enter total balance saved',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              final newSaved = double.tryParse(controller.text);
              if (newSaved == null || newSaved < 0) {
                _showToast('Please enter a valid amount', isError: true);
                return;
              }

              Navigator.pop(context);
              setState(() => _isLoading = true);

              try {
                final response = await _apiService.put(
                  '/savings-goals/${goal['id']}',
                  {'current_saved': newSaved},
                );

                if (response['success'] == true) {
                  _fetchGoals();
                  _showToast('Savings updated successfully!', isError: false);
                } else {
                  setState(() => _isLoading = false);
                  _showToast(response['message'] ?? 'Failed to update goal', isError: true);
                }
              } catch (e) {
                setState(() => _isLoading = false);
                _showToast(e.toString(), isError: true);
              }
            },
            child: const Text('Save'),
          ),
        ],
      ),
    );
  }

  void _showDeleteGoalDialog(dynamic goal) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: AppTheme.surface,
        title: Text(
          'Delete Savings Goal',
          style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.bold),
        ),
        content: Text('Are you sure you want to delete "${goal['goal_name']}"? This action cannot be undone.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.pop(context);
              setState(() => _isLoading = true);

              try {
                final response = await _apiService.delete('/savings-goals/${goal['id']}');

                if (response['success'] == true) {
                  _fetchGoals();
                  _showToast('Savings goal deleted.', isError: false);
                } else {
                  setState(() => _isLoading = false);
                  _showToast(response['message'] ?? 'Failed to delete goal', isError: true);
                }
              } catch (e) {
                setState(() => _isLoading = false);
                _showToast(e.toString(), isError: true);
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.error),
            child: const Text('Delete', style: TextStyle(color: Colors.black)),
          ),
        ],
      ),
    );
  }

  void _showToast(String message, {required bool isError}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? AppTheme.error : AppTheme.primaryContainer,
        duration: const Duration(seconds: 3),
      ),
    );
  }

  // ── UI Design ──

  @override
  Widget build(BuildContext context) {
    if (_isLoading && _goals.isEmpty) {
      return const Center(child: CircularProgressIndicator(color: AppTheme.primaryContainer));
    }

    if (_errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.broken_image_outlined, color: AppTheme.error, size: 48),
              const SizedBox(height: 16),
              Text(_errorMessage!, style: const TextStyle(color: AppTheme.error), textAlign: TextAlign.center),
              const SizedBox(height: 24),
              ElevatedButton(
                onPressed: _fetchGoals,
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      floatingActionButton: FloatingActionButton(
        heroTag: 'finance_savings_goals_tab_fab',
        onPressed: _showAddGoalSheet,
        backgroundColor: AppTheme.primary,
        child: const Icon(Icons.add, color: Colors.black),
      ),
      body: RefreshIndicator(
        onRefresh: _fetchGoals,
        color: AppTheme.primaryContainer,
        child: ListView(
          padding: const EdgeInsets.all(16.0),
          physics: const AlwaysScrollableScrollPhysics(),
          children: [
            // Master progress summary card
            _buildTotalSavingsCard(),
            const SizedBox(height: 24),

            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Active Targets',
                  style: GoogleFonts.plusJakartaSans(
                    fontWeight: FontWeight.bold,
                    fontSize: 18,
                  ),
                ),
                Text(
                  '${_goals.length} Goals',
                  style: const TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 13),
                ),
              ],
            ),
            const SizedBox(height: 12),

            if (_goals.isEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 48.0),
                child: Center(
                  child: Column(
                    children: [
                      const Icon(Icons.savings_outlined, size: 48, color: AppTheme.surfaceVariant),
                      const SizedBox(height: 16),
                      const Text(
                        'No savings goals set yet.',
                        style: TextStyle(color: AppTheme.onSurfaceVariant),
                      ),
                    ],
                  ),
                ),
              )
            else
              ..._goals.map((goal) => _buildGoalCard(goal)),

            const SizedBox(height: 80),
          ],
        ),
      ),
    );
  }

  Widget _buildTotalSavingsCard() {
    final isPnlPositive = _totalSaved >= 0;
    final progressVal = _totalProgress.clamp(0.0, 1.0);

    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppTheme.outline),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            Color(0xFF212529), // Charcoal/slate
            Color(0xFF131315),
          ],
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.3),
            blurRadius: 15,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      padding: const EdgeInsets.all(24.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'CUMULATIVE SAVINGS POOL',
                style: GoogleFonts.dmMono(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: AppTheme.onSurfaceVariant,
                  letterSpacing: 1.0,
                ),
              ),
              const Icon(Icons.savings, color: AppTheme.secondary, size: 20),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            'Rp ${_formatCurrency(_totalSaved)}',
            style: GoogleFonts.dmSerifDisplay(
              fontSize: 32,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Target: Rp ${_formatCurrency(_totalTarget)}',
            style: const TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 13),
          ),
          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(
                child: LinearProgressIndicator(
                  value: progressVal,
                  backgroundColor: AppTheme.surfaceVariant,
                  color: AppTheme.secondary,
                  minHeight: 10,
                  borderRadius: BorderRadius.circular(5),
                ),
              ),
              const SizedBox(width: 16),
              Text(
                '${(progressVal * 100).toStringAsFixed(1)}%',
                style: GoogleFonts.dmMono(
                  color: AppTheme.secondary,
                  fontWeight: FontWeight.bold,
                  fontSize: 14,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildGoalCard(dynamic goal) {
    final double target = (goal['target_amount'] as num).toDouble();
    final double saved = (goal['current_saved'] as num).toDouble();
    final double progressPct = (goal['progress_pct'] as num).toDouble();
    final bool isAchieved = goal['is_achieved'] == true;

    final progressVal = (progressPct / 100.0).clamp(0.0, 1.0);
    final themeColor = isAchieved ? AppTheme.primary : AppTheme.secondary;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: isAchieved ? AppTheme.primary.withOpacity(0.3) : AppTheme.outline),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () => _showGoalActionMenu(goal),
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: AppTheme.surfaceVariant,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    alignment: Alignment.center,
                    child: Text(
                      goal['icon'] ?? '🎯',
                      style: const TextStyle(fontSize: 20),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          goal['goal_name'],
                          style: GoogleFonts.plusJakartaSans(
                            fontWeight: FontWeight.bold,
                            fontSize: 15,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 2),
                        if (goal['target_date'] != null)
                          Text(
                            'Target Date: ${_formatDate(goal['target_date'])}',
                            style: const TextStyle(fontSize: 11, color: AppTheme.onSurfaceVariant),
                          )
                        else
                          const Text(
                            'No target date set',
                            style: TextStyle(fontSize: 11, color: AppTheme.onSurfaceVariant),
                          ),
                      ],
                    ),
                  ),
                  if (isAchieved)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppTheme.primary.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.check, color: AppTheme.primary, size: 12),
                          SizedBox(width: 4),
                          Text(
                            'ACHIEVED',
                            style: TextStyle(color: AppTheme.primary, fontWeight: FontWeight.bold, fontSize: 9),
                          ),
                        ],
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Rp ${_formatCurrency(saved)} / Rp ${_formatCurrency(target)}',
                    style: GoogleFonts.dmMono(
                      color: AppTheme.onSurfaceVariant,
                      fontSize: 13,
                    ),
                  ),
                  Text(
                    '${progressPct.toStringAsFixed(0)}%',
                    style: GoogleFonts.dmMono(
                      fontWeight: FontWeight.bold,
                      color: themeColor,
                      fontSize: 13,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              LinearProgressIndicator(
                value: progressVal,
                backgroundColor: AppTheme.surfaceVariant,
                color: themeColor,
                minHeight: 6,
                borderRadius: BorderRadius.circular(3),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showGoalActionMenu(dynamic goal) {
    showModalBottomSheet(
      context: context,
      backgroundColor: AppTheme.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Padding(
              padding: const EdgeInsets.all(16.0),
              child: Text(
                goal['goal_name'],
                style: GoogleFonts.plusJakartaSans(
                  fontWeight: FontWeight.bold,
                  fontSize: 15,
                ),
              ),
            ),
            const Divider(height: 1, color: AppTheme.outline),
            ListTile(
              leading: const Icon(Icons.add_circle_outline, color: AppTheme.secondary),
              title: const Text('Top Up / Adjust Savings'),
              onTap: () {
                Navigator.pop(context);
                _showQuickTopUpDialog(goal);
              },
            ),
            ListTile(
              leading: const Icon(Icons.delete_outline, color: AppTheme.error),
              title: const Text('Delete Goal Record'),
              onTap: () {
                Navigator.pop(context);
                _showDeleteGoalDialog(goal);
              },
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }
}

// ── ADD SAVINGS GOAL BOTTOM SHEET ──

class _GoalFormSheet extends StatefulWidget {
  final ApiService apiService;
  final List<String> goalIcons;
  final VoidCallback onSuccess;

  const _GoalFormSheet({
    required this.apiService,
    required this.goalIcons,
    required this.onSuccess,
  });

  @override
  State<_GoalFormSheet> createState() => _GoalFormSheetState();
}

class _GoalFormSheetState extends State<_GoalFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _targetController = TextEditingController();
  final _savedController = TextEditingController(text: '0');

  String _selectedIcon = '🎯';
  DateTime? _targetDate;
  bool _submitting = false;

  @override
  void dispose() {
    _nameController.dispose();
    _targetController.dispose();
    _savedController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_formKey.currentState!.validate()) {
      setState(() => _submitting = true);

      try {
        final t = double.parse(_targetController.text);
        final s = double.tryParse(_savedController.text) ?? 0.0;

        final response = await widget.apiService.post(
          '/savings-goals',
          {
            'goal_name': _nameController.text.trim(),
            'target_amount': t,
            'current_saved': s,
            'icon': _selectedIcon,
            'target_date': _targetDate != null ? _targetDate.toString().split(' ')[0] : null,
          },
        );

        if (response['success'] == true) {
          widget.onSuccess();
        } else {
          setState(() => _submitting = false);
          _showToast(response['message'] ?? 'Error creating savings target');
        }
      } catch (e) {
        setState(() => _submitting = false);
        _showToast(e.toString());
      }
    }
  }

  void _showToast(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: AppTheme.error,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        top: 24,
        left: 24,
        right: 24,
        bottom: MediaQuery.of(context).viewInsets.bottom + 24,
      ),
      child: Form(
        key: _formKey,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'Set New Savings Goal',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 24),

              // Goal Name
              TextFormField(
                controller: _nameController,
                decoration: const InputDecoration(
                  labelText: 'Savings Target Name',
                  hintText: 'e.g. Emergency Fund, Laptop, Euro Trip',
                  border: OutlineInputBorder(),
                ),
                validator: (value) => value == null || value.isEmpty ? 'Required' : null,
              ),
              const SizedBox(height: 16),

              // Target Amount & Initial Saved
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _targetController,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: const InputDecoration(
                        labelText: 'Target Amount (Rp)',
                        hintText: 'e.g. 15000000',
                        border: OutlineInputBorder(),
                      ),
                      validator: (value) {
                        if (value == null || value.isEmpty) return 'Required';
                        if (double.tryParse(value) == null || double.parse(value) <= 0) return 'Invalid';
                        return null;
                      },
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: TextFormField(
                      controller: _savedController,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: const InputDecoration(
                        labelText: 'Initial Saved (Rp)',
                        border: OutlineInputBorder(),
                      ),
                      validator: (value) {
                        if (value == null || value.isEmpty) return 'Required';
                        if (double.tryParse(value) == null || double.parse(value) < 0) return 'Invalid';
                        return null;
                      },
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),

              // Icon Selector & Target Date
              Row(
                children: [
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      value: _selectedIcon,
                      decoration: const InputDecoration(
                        labelText: 'Icon Emoji',
                        border: OutlineInputBorder(),
                      ),
                      dropdownColor: AppTheme.surface,
                      items: widget.goalIcons
                          .map((emoji) => DropdownMenuItem(
                                value: emoji,
                                child: Text(emoji, style: const TextStyle(fontSize: 18)),
                              ))
                          .toList(),
                      onChanged: (val) {
                        if (val != null) {
                          setState(() => _selectedIcon = val);
                        }
                      },
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: DateTime.now().add(const Duration(days: 30)),
                          firstDate: DateTime.now(),
                          lastDate: DateTime(2100),
                        );
                        if (picked != null) {
                          setState(() => _targetDate = picked);
                        }
                      },
                      icon: const Icon(Icons.calendar_today_outlined, size: 16),
                      label: Text(
                        _targetDate == null
                            ? 'Target Date'
                            : '${_targetDate!.day}/${_targetDate!.month}/${_targetDate!.year}',
                        style: const TextStyle(fontSize: 12),
                      ),
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size(double.infinity, 56),
                        side: const BorderSide(color: AppTheme.outline),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4)),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 32),

              // Submit Button
              SizedBox(
                height: 56,
                child: ElevatedButton(
                  onPressed: _submitting ? null : _submit,
                  style: ElevatedButton.styleFrom(backgroundColor: AppTheme.primary),
                  child: _submitting
                      ? const CircularProgressIndicator(color: Colors.black)
                      : const Text(
                          'Initiate Target',
                          style: TextStyle(fontWeight: FontWeight.bold, color: Colors.black),
                        ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
