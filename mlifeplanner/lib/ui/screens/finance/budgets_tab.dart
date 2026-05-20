import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/services/api_service.dart';
import '../../../core/theme/app_theme.dart';

class BudgetsTab extends StatefulWidget {
  const BudgetsTab({super.key});

  @override
  State<BudgetsTab> createState() => _BudgetsTabState();
}

class _BudgetsTabState extends State<BudgetsTab> {
  final ApiService _apiService = ApiService();
  bool _isLoading = true;
  List<dynamic> _budgets = [];
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _fetchBudgets();
  }

  Future<void> _fetchBudgets() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final response = await _apiService.get('/budgets');
      if (response['success'] == true) {
        setState(() {
          _budgets = response['data'] ?? [];
          _isLoading = false;
        });
      } else {
        setState(() {
          _errorMessage = response['message'] ?? 'Failed to load budgets.';
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

  // ── Action Dialogs ──

  void _showAddBudgetSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppTheme.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => _BudgetFormSheet(
        apiService: _apiService,
        onSuccess: () {
          Navigator.pop(context);
          _fetchBudgets();
          _showToast('New budget period activated! 💼', isError: false);
        },
      ),
    );
  }

  void _showUpdateBudgetDialog(dynamic budget) {
    final controller = TextEditingController(text: budget['starting_balance'].toString());
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: AppTheme.surface,
        title: Text(
          'Edit Budget Starting Balance',
          style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.bold),
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Period: ${_formatDate(budget['period_start'])} - ${_formatDate(budget['period_end'])}',
              style: const TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 13),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: controller,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: 'Starting Balance (Rp)',
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
              final newBal = double.tryParse(controller.text);
              if (newBal == null || newBal < 0) {
                _showToast('Please enter a valid balance', isError: true);
                return;
              }

              Navigator.pop(context);
              setState(() => _isLoading = true);

              try {
                final response = await _apiService.put(
                  '/budgets/${budget['id']}',
                  {'starting_balance': newBal},
                );

                if (response['success'] == true) {
                  _fetchBudgets();
                  _showToast('Budget balance updated successfully!', isError: false);
                } else {
                  setState(() => _isLoading = false);
                  _showToast(response['message'] ?? 'Failed to update budget', isError: true);
                }
              } catch (e) {
                setState(() => _isLoading = false);
                _showToast(e.toString(), isError: true);
              }
            },
            child: const Text('Update'),
          ),
        ],
      ),
    );
  }

  void _activateBudget(int id) async {
    setState(() => _isLoading = true);
    try {
      final response = await _apiService.put('/budgets/$id', {'is_active': true});
      if (response['success'] == true) {
        _fetchBudgets();
        _showToast('Budget period activated!', isError: false);
      } else {
        setState(() => _isLoading = false);
        _showToast(response['message'] ?? 'Failed to activate budget', isError: true);
      }
    } catch (e) {
      setState(() => _isLoading = false);
      _showToast(e.toString(), isError: true);
    }
  }

  void _showDeleteBudgetDialog(dynamic budget) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: AppTheme.surface,
        title: Text(
          'Delete Budget Period',
          style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.bold),
        ),
        content: Text(
          'Are you sure you want to delete the budget period ${_formatDate(budget['period_start'])} to ${_formatDate(budget['period_end'])}? This will un-link transactions from this period.',
        ),
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
                final response = await _apiService.delete('/budgets/${budget['id']}');

                if (response['success'] == true) {
                  _fetchBudgets();
                  _showToast('Budget deleted successfully', isError: false);
                } else {
                  setState(() => _isLoading = false);
                  _showToast(response['message'] ?? 'Failed to delete budget', isError: true);
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

  // ── UI Layout ──

  @override
  Widget build(BuildContext context) {
    if (_isLoading && _budgets.isEmpty) {
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
                onPressed: _fetchBudgets,
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    final activeBudget = _budgets.firstWhere((b) => b['is_active'] == true, orElse: () => null);
    final inactiveBudgets = _budgets.where((b) => b['is_active'] == false).toList();

    return Scaffold(
      floatingActionButton: FloatingActionButton(
        onPressed: _showAddBudgetSheet,
        backgroundColor: AppTheme.primary,
        child: const Icon(Icons.add, color: Colors.black),
      ),
      body: RefreshIndicator(
        onRefresh: _fetchBudgets,
        color: AppTheme.primaryContainer,
        child: ListView(
          padding: const EdgeInsets.all(16.0),
          physics: const AlwaysScrollableScrollPhysics(),
          children: [
            // Active Budget Overview Card
            if (activeBudget != null) ...[
              _buildActiveBudgetCard(activeBudget),
              const SizedBox(height: 24),
            ] else ...[
              _buildNoActiveBudgetCard(),
              const SizedBox(height: 24),
            ],

            // Section Header: History
            Text(
              'Budget Periods History',
              style: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.bold,
                fontSize: 18,
              ),
            ),
            const SizedBox(height: 12),

            if (inactiveBudgets.isEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 48.0),
                child: Center(
                  child: Column(
                    children: [
                      const Icon(Icons.folder_open_outlined, size: 48, color: AppTheme.surfaceVariant),
                      const SizedBox(height: 16),
                      const Text(
                        'No past budget periods recorded.',
                        style: TextStyle(color: AppTheme.onSurfaceVariant),
                      ),
                    ],
                  ),
                ),
              )
            else
              ...inactiveBudgets.map((b) => _buildBudgetHistoryRow(b)),

            const SizedBox(height: 80),
          ],
        ),
      ),
    );
  }

  Widget _buildActiveBudgetCard(dynamic budget) {
    final double balance = (budget['starting_balance'] as num).toDouble();
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppTheme.primaryContainer.withOpacity(0.3)),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            Color(0xFF10271E), // Premium deep green/black
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
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: AppTheme.primary.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(30),
                ),
                child: Row(
                  children: [
                    const CircleAvatar(
                      radius: 4,
                      backgroundColor: AppTheme.primary,
                    ),
                    const SizedBox(width: 6),
                    Text(
                      'ACTIVE PERIOD',
                      style: GoogleFonts.dmMono(
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                        color: AppTheme.primary,
                      ),
                    ),
                  ],
                ),
              ),
              IconButton(
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
                icon: const Icon(Icons.more_vert, color: Colors.white),
                onPressed: () => _showBudgetActionMenu(budget),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Text(
            'PERIOD STARTING BALANCE',
            style: GoogleFonts.dmMono(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: AppTheme.onSurfaceVariant,
              letterSpacing: 1.0,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Rp ${_formatCurrency(balance)}',
            style: GoogleFonts.dmSerifDisplay(
              fontSize: 32,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Starts', style: TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 11)),
                  const SizedBox(height: 4),
                  Text(
                    _formatDate(budget['period_start']),
                    style: GoogleFonts.dmMono(
                      color: Colors.white,
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                    ),
                  ),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  const Text('Ends', style: TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 11)),
                  const SizedBox(height: 4),
                  Text(
                    _formatDate(budget['period_end']),
                    style: GoogleFonts.dmMono(
                      color: Colors.white,
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildNoActiveBudgetCard() {
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppTheme.outline),
        color: AppTheme.surfaceVariant.withOpacity(0.3),
      ),
      padding: const EdgeInsets.all(24.0),
      child: Column(
        children: [
          const Icon(Icons.folder_special_outlined, size: 48, color: AppTheme.secondary),
          const SizedBox(height: 16),
          Text(
            'No Active Budget Period',
            style: GoogleFonts.plusJakartaSans(
              fontWeight: FontWeight.bold,
              fontSize: 16,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Create or activate a budget period to begin categorizing cash flow logs and tracking savings health ratios.',
            textAlign: TextAlign.center,
            style: TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 13),
          ),
          const SizedBox(height: 20),
          ElevatedButton.icon(
            onPressed: _showAddBudgetSheet,
            icon: const Icon(Icons.add, color: Colors.black),
            label: const Text('Create New Budget', style: TextStyle(color: Colors.black, fontWeight: FontWeight.bold)),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.secondary,
              minimumSize: const Size(200, 48),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBudgetHistoryRow(dynamic budget) {
    final double balance = (budget['starting_balance'] as num).toDouble();
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: const BorderSide(color: AppTheme.outline),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () => _showBudgetActionMenu(budget),
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: AppTheme.surfaceVariant,
                  borderRadius: BorderRadius.circular(10),
                ),
                alignment: Alignment.center,
                child: const Icon(Icons.history_toggle_off, color: AppTheme.onSurfaceVariant),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '${_formatDate(budget['period_start'])} - ${_formatDate(budget['period_end'])}',
                      style: GoogleFonts.plusJakartaSans(
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Starting: Rp ${_formatCurrency(balance)}',
                      style: const TextStyle(fontSize: 12, color: AppTheme.onSurfaceVariant),
                    ),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right, color: AppTheme.onSurfaceVariant),
            ],
          ),
        ),
      ),
    );
  }

  void _showBudgetActionMenu(dynamic budget) {
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
                'Budget: ${_formatDate(budget['period_start'])} - ${_formatDate(budget['period_end'])}',
                style: GoogleFonts.plusJakartaSans(
                  fontWeight: FontWeight.bold,
                  fontSize: 14,
                ),
              ),
            ),
            const Divider(height: 1, color: AppTheme.outline),
            if (budget['is_active'] == false)
              ListTile(
                leading: const Icon(Icons.play_circle_outline, color: AppTheme.primary),
                title: const Text('Set as Active Period'),
                onTap: () {
                  Navigator.pop(context);
                  _activateBudget(budget['id']);
                },
              ),
            ListTile(
              leading: const Icon(Icons.edit_outlined, color: AppTheme.secondary),
              title: const Text('Edit Starting Balance'),
              onTap: () {
                Navigator.pop(context);
                _showUpdateBudgetDialog(budget);
              },
            ),
            ListTile(
              leading: const Icon(Icons.delete_outline, color: AppTheme.error),
              title: const Text('Delete Budget Period'),
              onTap: () {
                Navigator.pop(context);
                _showDeleteBudgetDialog(budget);
              },
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }
}

// ── ADD BUDGET PERIOD FORM BOTTOM SHEET ──

class _BudgetFormSheet extends StatefulWidget {
  final ApiService apiService;
  final VoidCallback onSuccess;

  const _BudgetFormSheet({
    required this.apiService,
    required this.onSuccess,
  });

  @override
  State<_BudgetFormSheet> createState() => _BudgetFormSheetState();
}

class _BudgetFormSheetState extends State<_BudgetFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _balanceController = TextEditingController(text: '0');

  DateTime _startDate = DateTime.now().subtract(Duration(days: DateTime.now().day - 1)); // Start of month
  DateTime _endDate = DateTime(DateTime.now().year, DateTime.now().month + 1, 0); // End of month

  bool _submitting = false;

  @override
  void dispose() {
    _balanceController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_formKey.currentState!.validate()) {
      if (_endDate.isBefore(_startDate)) {
        _showToast('End Date must be after or equal to Start Date');
        return;
      }

      setState(() => _submitting = true);

      try {
        final bal = double.tryParse(_balanceController.text) ?? 0.0;
        final response = await widget.apiService.post(
          '/budgets',
          {
            'period_start': _startDate.toString().split(' ')[0],
            'period_end': _endDate.toString().split(' ')[0],
            'starting_balance': bal,
          },
        );

        if (response['success'] == true) {
          widget.onSuccess();
        } else {
          setState(() => _submitting = false);
          _showToast(response['message'] ?? 'Error creating budget period');
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
                'Create Budget Period',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 24),

              // Starting Balance
              TextFormField(
                controller: _balanceController,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: const InputDecoration(
                  labelText: 'Starting Balance (Rp)',
                  hintText: 'e.g. 5000000',
                  border: OutlineInputBorder(),
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) return 'Required';
                  if (double.tryParse(value) == null || double.parse(value) < 0) return 'Invalid';
                  return null;
                },
              ),
              const SizedBox(height: 24),

              // Period Date Pickers
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: _startDate,
                          firstDate: DateTime(2020),
                          lastDate: DateTime(2100),
                        );
                        if (picked != null) {
                          setState(() => _startDate = picked);
                        }
                      },
                      icon: const Icon(Icons.calendar_today_outlined, size: 16),
                      label: Text(
                        'Start: ${_startDate.day}/${_startDate.month}/${_startDate.year}',
                        style: const TextStyle(fontSize: 12),
                      ),
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size(double.infinity, 56),
                        side: const BorderSide(color: AppTheme.outline),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: _endDate,
                          firstDate: DateTime(2020),
                          lastDate: DateTime(2100),
                        );
                        if (picked != null) {
                          setState(() => _endDate = picked);
                        }
                      },
                      icon: const Icon(Icons.calendar_today_outlined, size: 16),
                      label: Text(
                        'End: ${_endDate.day}/${_endDate.month}/${_endDate.year}',
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
                          'Activate New Budget Period',
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
