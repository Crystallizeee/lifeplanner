import 'package:flutter/material.dart';
import '../../../core/services/api_service.dart';
import '../../../core/theme/app_theme.dart';
import 'quick_entry_screen.dart';

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
    return Scaffold(
      appBar: AppBar(
        title: const Text('Finance Journal'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _fetchTransactions,
          ),
        ],
      ),
      body: Column(
        children: [
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
          );
        },
      ),
    );
  }
}
