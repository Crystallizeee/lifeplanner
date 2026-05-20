import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';
import '../../../core/services/api_service.dart';
import '../../../core/theme/app_theme.dart';

class CashflowChartWidget extends StatefulWidget {
  const CashflowChartWidget({super.key});

  @override
  State<CashflowChartWidget> createState() => _CashflowChartWidgetState();
}

class _CashflowChartWidgetState extends State<CashflowChartWidget> {
  final ApiService _apiService = ApiService();
  bool _isLoading = true;
  List<dynamic> _cashflowData = [];
  String? _error;
  int _selectedMonths = 6;

  @override
  void initState() {
    super.initState();
    _fetchCashflow();
  }

  Future<void> _fetchCashflow() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final response = await _apiService.get('/charts/cashflow?months=$_selectedMonths');
      if (response['success'] == true) {
        setState(() {
          _cashflowData = response['data'];
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.all(16),
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    const Icon(Icons.bar_chart_rounded, size: 20, color: AppTheme.primary),
                    const SizedBox(width: 8),
                    Text(
                      'Cashflow',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
                // Period selector
                DropdownButton<int>(
                  value: _selectedMonths,
                  underline: const SizedBox(),
                  isDense: true,
                  style: const TextStyle(fontSize: 12, color: AppTheme.onBackground),
                  items: const [
                    DropdownMenuItem(value: 3, child: Text('3 Bulan')),
                    DropdownMenuItem(value: 6, child: Text('6 Bulan')),
                    DropdownMenuItem(value: 12, child: Text('1 Tahun')),
                  ],
                  onChanged: (val) {
                    if (val != null) {
                      _selectedMonths = val;
                      _fetchCashflow();
                    }
                  },
                ),
              ],
            ),
            const SizedBox(height: 8),
            // Legend
            Row(
              children: [
                _buildLegendDot(const Color(0xFF4CAF50), 'Income'),
                const SizedBox(width: 16),
                _buildLegendDot(const Color(0xFFEF5350), 'Expense'),
              ],
            ),
            const SizedBox(height: 20),
            SizedBox(
              height: 220,
              child: _buildChartContent(),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildLegendDot(Color color, String label) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 10,
          height: 10,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(2),
          ),
        ),
        const SizedBox(width: 4),
        Text(label, style: const TextStyle(fontSize: 11, color: AppTheme.onSurfaceVariant)),
      ],
    );
  }

  Widget _buildChartContent() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator(color: AppTheme.primaryContainer));
    }

    if (_error != null) {
      return Center(child: Text(_error!, style: const TextStyle(color: AppTheme.error, fontSize: 12)));
    }

    if (_cashflowData.isEmpty) {
      return const Center(child: Text('No cashflow data available.'));
    }

    final maxVal = _cashflowData.fold<double>(0.0, (prev, e) {
      final income = (e['income'] as num).toDouble();
      final expense = (e['expense'] as num).toDouble();
      return [prev, income, expense].reduce((a, b) => a > b ? a : b);
    });

    return BarChart(
      BarChartData(
        alignment: BarChartAlignment.spaceAround,
        maxY: maxVal * 1.2,
        barTouchData: BarTouchData(
          enabled: true,
          touchTooltipData: BarTouchTooltipData(
            getTooltipItem: (group, groupIndex, rod, rodIndex) {
              final item = _cashflowData[groupIndex];
              final label = rodIndex == 0 ? 'Income' : 'Expense';
              final val = rodIndex == 0 ? item['income'] : item['expense'];
              return BarTooltipItem(
                '${item['label']}\n$label: Rp ${_formatNumber(val)}',
                const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w500),
              );
            },
          ),
        ),
        titlesData: FlTitlesData(
          show: true,
          bottomTitles: AxisTitles(
            sideTitles: SideTitles(
              showTitles: true,
              reservedSize: 30,
              getTitlesWidget: (value, meta) {
                final index = value.toInt();
                if (index < 0 || index >= _cashflowData.length) return const SizedBox();
                final label = _cashflowData[index]['label'] as String;
                // Show abbreviated month
                final parts = label.split(' ');
                return SideTitleWidget(
                  meta: meta,
                  child: Text(
                    parts.isNotEmpty ? parts[0].substring(0, 3) : '',
                    style: const TextStyle(fontSize: 10, color: AppTheme.onSurfaceVariant),
                  ),
                );
              },
            ),
          ),
          leftTitles: AxisTitles(
            sideTitles: SideTitles(
              showTitles: true,
              reservedSize: 48,
              getTitlesWidget: (value, meta) {
                if (value == 0) return const SizedBox();
                return Text(
                  _formatCompact(value),
                  style: const TextStyle(fontSize: 9, color: AppTheme.onSurfaceVariant),
                );
              },
            ),
          ),
          topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
          rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
        ),
        borderData: FlBorderData(show: false),
        gridData: FlGridData(
          show: true,
          drawVerticalLine: false,
          horizontalInterval: maxVal > 0 ? maxVal / 4 : 1,
          getDrawingHorizontalLine: (value) => FlLine(
            color: AppTheme.outline.withValues(alpha: 0.15),
            strokeWidth: 1,
          ),
        ),
        barGroups: List.generate(_cashflowData.length, (index) {
          final item = _cashflowData[index];
          return BarChartGroupData(
            x: index,
            barRods: [
              BarChartRodData(
                toY: (item['income'] as num).toDouble(),
                color: const Color(0xFF4CAF50),
                width: 10,
                borderRadius: const BorderRadius.vertical(top: Radius.circular(4)),
              ),
              BarChartRodData(
                toY: (item['expense'] as num).toDouble(),
                color: const Color(0xFFEF5350),
                width: 10,
                borderRadius: const BorderRadius.vertical(top: Radius.circular(4)),
              ),
            ],
          );
        }),
      ),
    );
  }

  String _formatNumber(dynamic val) {
    final amount = (val as num).toDouble();
    final parts = amount.toStringAsFixed(0).split('.');
    final reg = RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))');
    return parts[0].replaceAllMapped(reg, (Match m) => '${m[1]}.');
  }

  String _formatCompact(double val) {
    if (val >= 1000000000) return '${(val / 1000000000).toStringAsFixed(1)}B';
    if (val >= 1000000) return '${(val / 1000000).toStringAsFixed(1)}M';
    if (val >= 1000) return '${(val / 1000).toStringAsFixed(0)}K';
    return val.toStringAsFixed(0);
  }
}
