import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/services/api_service.dart';
import '../../../core/theme/app_theme.dart';
import 'quick_entry_screen.dart';
import 'investments_tab.dart';
import 'budgets_tab.dart';
import 'savings_goals_tab.dart';
import '../../widgets/cashflow_chart_widget.dart';

class FinanceScreen extends StatefulWidget {
  const FinanceScreen({super.key});

  @override
  State<FinanceScreen> createState() => _FinanceScreenState();
}

class _FinanceScreenState extends State<FinanceScreen> {
  final ApiService _apiService = ApiService();
  bool _isLoading = true;
  List<dynamic> _transactions = [];
  String? _errorMessage;
  String _filterType = 'all'; // 'all', 'income', 'expense', 'bill'

  @override
  void initState() {
    super.initState();
    _fetchTransactions();
  }

  Future<void> _fetchTransactions() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final response = await _apiService.get('/transactions');
      if (response['success'] == true) {
        setState(() {
          _transactions = response['data'];
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

  List<dynamic> get _filteredTransactions {
    if (_filterType == 'all') return _transactions;
    return _transactions.where((tx) => tx['type'] == _filterType).toList();
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 4,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Finance Hub'),
          bottom: const TabBar(
            isScrollable: true,
            tabAlignment: TabAlignment.start,
            tabs: [
              Tab(text: 'Journal Logs', icon: Icon(Icons.receipt_long_outlined)),
              Tab(text: 'Budgets', icon: Icon(Icons.account_balance_outlined)),
              Tab(text: 'Savings Goals', icon: Icon(Icons.savings_outlined)),
              Tab(text: 'Investments', icon: Icon(Icons.pie_chart_outline)),
            ],
            indicatorColor: AppTheme.primary,
            labelColor: AppTheme.primary,
            unselectedLabelColor: AppTheme.onSurfaceVariant,
          ),
          actions: [
            IconButton(
              icon: const Icon(Icons.refresh),
              onPressed: _fetchTransactions,
            ),
          ],
        ),
        body: TabBarView(
          children: [
            // Tab 1: Journal Logs (with cashflow chart)
            Column(
              children: [
                // Cashflow Chart (collapsible)
                const CashflowChartWidget(),

                // Filter Tabs
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
                  child: SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        _buildFilterChip('all', 'All'),
                        const SizedBox(width: 8),
                        _buildFilterChip('income', 'Income', color: AppTheme.primaryContainer),
                        const SizedBox(width: 8),
                        _buildFilterChip('expense', 'Expense', color: AppTheme.error),
                        const SizedBox(width: 8),
                        _buildFilterChip('bill', 'Bill', color: AppTheme.secondary),
                      ],
                    ),
                  ),
                ),
                
                // Transaction List
                Expanded(child: _buildBody()),
              ],
            ),
            
            // Tab 2: Budgets
            const BudgetsTab(),

            // Tab 3: Savings Goals
            const SavingsGoalsTab(),

            // Tab 4: Investments Portfolio
            const InvestmentsTab(),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterChip(String value, String label, {Color? color}) {
    final isSelected = _filterType == value;
    return ChoiceChip(
      label: Text(label),
      selected: isSelected,
      selectedColor: color?.withOpacity(0.2) ?? AppTheme.primaryContainer.withOpacity(0.2),
      checkmarkColor: color ?? AppTheme.primaryContainer,
      labelStyle: TextStyle(
        color: isSelected ? (color ?? AppTheme.primaryContainer) : AppTheme.onBackground,
        fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
      ),
      onSelected: (selected) {
        if (selected) {
          setState(() {
            _filterType = value;
          });
        }
      },
    );
  }

  Future<void> _deleteTransaction(int id) async {
    setState(() => _isLoading = true);
    try {
      final response = await _apiService.delete('/transactions/$id');
      if (response['success'] == true) {
        _fetchTransactions();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Transaction deleted successfully!'), backgroundColor: AppTheme.primaryContainer),
        );
      } else {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(response['message'] ?? 'Failed to delete transaction'), backgroundColor: AppTheme.error),
        );
      }
    } catch (e) {
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: AppTheme.error),
      );
    }
  }

  void _showTransactionActionMenu(dynamic tx) {
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
                tx['description'] ?? 'Transaction Actions',
                style: GoogleFonts.plusJakartaSans(
                  fontWeight: FontWeight.bold,
                  fontSize: 15,
                ),
              ),
            ),
            const Divider(height: 1, color: AppTheme.outline),
            ListTile(
              leading: const Icon(Icons.delete_outline, color: AppTheme.error),
              title: const Text('Delete Transaction Log'),
              onTap: () {
                Navigator.pop(context);
                _showDeleteConfirmDialog(tx);
              },
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }

  void _showDeleteConfirmDialog(dynamic tx) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: AppTheme.surface,
        title: Text(
          'Delete Transaction Log',
          style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.bold),
        ),
        content: Text(
          'Are you sure you want to delete the transaction log for "${tx['description']}"? This action cannot be undone.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              _deleteTransaction(tx['id']);
            },
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.error),
            child: const Text('Delete', style: TextStyle(color: Colors.black)),
          ),
        ],
      ),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator(color: AppTheme.primaryContainer));
    }

    if (_errorMessage != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, color: AppTheme.error, size: 48),
            const SizedBox(height: 16),
            Text(_errorMessage!, style: const TextStyle(color: AppTheme.error)),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: _fetchTransactions,
              child: const Text('Retry'),
            ),
          ],
        ),
      );
    }

    final list = _filteredTransactions;

    if (list.isEmpty) {
      return const Center(
        child: Text('No transactions recorded.'),
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchTransactions,
      color: AppTheme.primaryContainer,
      child: ListView.builder(
        padding: const EdgeInsets.all(16.0),
        itemCount: list.length,
        itemBuilder: (context, index) {
          final tx = list[index];
          final isIncome = tx['type'] == 'income';
          final txColor = tx['type'] == 'income' 
              ? AppTheme.primaryContainer 
              : (tx['type'] == 'bill' ? AppTheme.secondary : AppTheme.error);

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            clipBehavior: Clip.antiAlias,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            child: InkWell(
              onTap: () => _showTransactionActionMenu(tx),
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 8.0),
                child: ListTile(
                  leading: Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      color: AppTheme.surfaceVariant,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    alignment: Alignment.center,
                    child: Text(
                      tx['category_icon'] ?? '📦', 
                      style: const TextStyle(fontSize: 22)
                    ),
                  ),
                  title: Text(
                    tx['description'] ?? '',
                    style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16),
                  ),
                  subtitle: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const SizedBox(height: 4),
                      Text(
                        tx['category_name'] ?? 'Uncategorized',
                        style: const TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 13),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        tx['transaction_date'] ?? '',
                        style: const TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 12),
                      ),
                    ],
                  ),
                  trailing: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        '${isIncome ? '+' : '-'} Rp ${tx['amount']}',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                          color: txColor,
                        ),
                      ),
                      if (tx['notes'] != null && tx['notes'].toString().isNotEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: Text(
                            tx['notes'],
                            style: const TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 11, fontStyle: FontStyle.italic),
                          ),
                        ),
                    ],
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
