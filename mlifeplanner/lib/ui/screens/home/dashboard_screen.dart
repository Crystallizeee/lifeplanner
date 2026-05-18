import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/services/api_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../providers/auth_provider.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  final ApiService _apiService = ApiService();
  bool _isLoading = true;
  Map<String, dynamic>? _dashboardData;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _fetchDashboardData();
  }

  Future<void> _fetchDashboardData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final response = await _apiService.get('/dashboard');
      if (response['success'] == true) {
        setState(() {
          _dashboardData = response['data'];
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

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user;

    return Scaffold(
      appBar: AppBar(
        title: Text('Welcome, ${user?.name ?? 'User'}'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () {
              context.read<AuthProvider>().logout();
            },
          ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: AppTheme.primaryContainer),
      );
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
              onPressed: _fetchDashboardData,
              child: const Text('Retry'),
            ),
          ],
        ),
      );
    }

    if (_dashboardData == null) {
      return const Center(child: Text('No data available'));
    }

    return RefreshIndicator(
      onRefresh: _fetchDashboardData,
      color: AppTheme.primaryContainer,
      child: ListView(
        padding: const EdgeInsets.all(16.0),
        children: [
          _buildFinanceSummaryCard(),
          const SizedBox(height: 16),
          _buildProductivitySummaryCard(),
          const SizedBox(height: 16),
          _buildRecentActivityList(),
        ],
      ),
    );
  }

  Widget _buildFinanceSummaryCard() {
    final finance = _dashboardData!['finance'];
    final netBalance = finance['net_balance'] ?? 0;
    
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.account_balance_wallet_outlined, size: 20, color: AppTheme.onSurfaceVariant),
                const SizedBox(width: 8),
                Text(
                  'Finance Overview',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontSize: 16,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),
            Text(
              'Net Balance',
              style: Theme.of(context).textTheme.labelSmall,
            ),
            const SizedBox(height: 4),
            Text(
              'Rp ${netBalance.toStringAsFixed(0)}',
              style: Theme.of(context).textTheme.displayMedium?.copyWith(
                color: netBalance >= 0 ? AppTheme.primaryContainer : AppTheme.error,
              ),
            ),
            const SizedBox(height: 24),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                _buildFinanceStat('Income', finance['total_income'] ?? 0, AppTheme.primaryContainer),
                _buildFinanceStat('Expense', finance['total_expense'] ?? 0, AppTheme.error),
                _buildFinanceStat('Bills', finance['total_bills'] ?? 0, AppTheme.secondary),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFinanceStat(String label, num amount, Color color) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: Theme.of(context).textTheme.labelSmall,
        ),
        const SizedBox(height: 4),
        Text(
          'Rp ${amount.toStringAsFixed(0)}',
          style: Theme.of(context).textTheme.bodyLarge?.copyWith(
            fontWeight: FontWeight.w600,
            color: color,
          ),
        ),
      ],
    );
  }

  Widget _buildProductivitySummaryCard() {
    final prod = _dashboardData!['productivity'];
    
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.check_circle_outline, size: 20, color: AppTheme.onSurfaceVariant),
                const SizedBox(width: 8),
                Text(
                  'Tasks & Goals',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontSize: 16,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildCircularStat(
                  'To Do', 
                  prod['todays_tasks'].toString(),
                  AppTheme.secondary,
                ),
                _buildCircularStat(
                  'Done', 
                  prod['completed_tasks'].toString(),
                  AppTheme.primaryContainer,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCircularStat(String label, String value, Color color) {
    return Column(
      children: [
        Container(
          width: 64,
          height: 64,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            border: Border.all(color: color.withOpacity(0.3), width: 4),
          ),
          alignment: Alignment.center,
          child: Text(
            value,
            style: Theme.of(context).textTheme.headlineMedium?.copyWith(
              color: color,
            ),
          ),
        ),
        const SizedBox(height: 12),
        Text(label, style: Theme.of(context).textTheme.labelSmall),
      ],
    );
  }

  Widget _buildRecentActivityList() {
    final recent = _dashboardData!['recent_activity'] as List<dynamic>? ?? [];
    
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.history, size: 20, color: AppTheme.onSurfaceVariant),
                const SizedBox(width: 8),
                Text(
                  'Recent Activity',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontSize: 16,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            if (recent.isEmpty)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 24),
                child: Center(child: Text('No recent activity')),
              )
            else
              ...recent.map((tx) {
                final isIncome = tx['type'] == 'income';
                return ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: AppTheme.surfaceVariant,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    alignment: Alignment.center,
                    child: Text(tx['category_icon'] ?? '📦', style: const TextStyle(fontSize: 18)),
                  ),
                  title: Text(
                    tx['description'] ?? '',
                    style: Theme.of(context).textTheme.bodyLarge,
                  ),
                  subtitle: Text(
                    tx['transaction_date'] ?? '',
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                  trailing: Text(
                    '${isIncome ? '+' : '-'} Rp ${tx['amount']}',
                    style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      fontWeight: FontWeight.w600,
                      color: isIncome ? AppTheme.primaryContainer : AppTheme.error,
                    ),
                  ),
                );
              }),
          ],
        ),
      ),
    );
  }
}
