import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';
import '../../../core/theme/app_theme.dart';

/// Renders an interactive donut chart showing asset allocation
/// by investment type. Expects a list of investments from the API.
class InvestmentPieChart extends StatefulWidget {
  final List<dynamic> investments;

  const InvestmentPieChart({super.key, required this.investments});

  @override
  State<InvestmentPieChart> createState() => _InvestmentPieChartState();
}

class _InvestmentPieChartState extends State<InvestmentPieChart> {
  int _touchedIndex = -1;

  static const List<Color> _sliceColors = [
    Color(0xFF42A5F5), // Saham
    Color(0xFF66BB6A), // Reksa Dana
    Color(0xFFFFA726), // Crypto
    Color(0xFFFFD54F), // Emas
    Color(0xFF7E57C2), // Deposito
    Color(0xFFEF5350), // Properti
    Color(0xFF78909C), // Lainnya
  ];

  static const Map<String, String> _typeLabels = {
    'saham': 'Saham',
    'reksadana': 'Reksa Dana',
    'crypto': 'Crypto',
    'emas': 'Emas',
    'deposito': 'Deposito',
    'properti': 'Properti',
    'lainnya': 'Lainnya',
  };

  @override
  Widget build(BuildContext context) {
    // Group by asset_type and sum current_value (active holdings only)
    final Map<String, double> grouped = {};
    for (var inv in widget.investments) {
      if (inv['is_sold'] == true) continue;
      final type = inv['asset_type'] ?? 'lainnya';
      grouped[type] = (grouped[type] ?? 0.0) + (inv['current_value'] as num).toDouble();
    }

    if (grouped.isEmpty) {
      return const SizedBox(
        height: 200,
        child: Center(child: Text('No active investments to visualize.')),
      );
    }

    final total = grouped.values.fold(0.0, (a, b) => a + b);
    final entries = grouped.entries.toList()..sort((a, b) => b.value.compareTo(a.value));

    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.donut_large, size: 20, color: AppTheme.primary),
                const SizedBox(width: 8),
                Text(
                  'Asset Allocation',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const SizedBox(height: 20),
            SizedBox(
              height: 200,
              child: Row(
                children: [
                  Expanded(
                    flex: 3,
                    child: PieChart(
                      PieChartData(
                        pieTouchData: PieTouchData(
                          touchCallback: (FlTouchEvent event, pieTouchResponse) {
                            setState(() {
                              if (!event.isInterestedForInteractions ||
                                  pieTouchResponse == null ||
                                  pieTouchResponse.touchedSection == null) {
                                _touchedIndex = -1;
                                return;
                              }
                              _touchedIndex = pieTouchResponse.touchedSection!.touchedSectionIndex;
                            });
                          },
                        ),
                        borderData: FlBorderData(show: false),
                        sectionsSpace: 2,
                        centerSpaceRadius: 36,
                        sections: List.generate(entries.length, (i) {
                          final isTouched = i == _touchedIndex;
                          final pct = (entries[i].value / total) * 100;
                          return PieChartSectionData(
                            color: _sliceColors[i % _sliceColors.length],
                            value: entries[i].value,
                            title: '${pct.toStringAsFixed(1)}%',
                            titleStyle: TextStyle(
                              fontSize: isTouched ? 13 : 10,
                              fontWeight: FontWeight.bold,
                              color: Colors.white,
                            ),
                            radius: isTouched ? 56 : 48,
                            titlePositionPercentageOffset: 0.6,
                          );
                        }),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    flex: 2,
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: List.generate(entries.length, (i) {
                        final label = _typeLabels[entries[i].key] ?? entries[i].key;
                        final pct = (entries[i].value / total) * 100;
                        return Padding(
                          padding: const EdgeInsets.symmetric(vertical: 3.0),
                          child: Row(
                            children: [
                              Container(
                                width: 10,
                                height: 10,
                                decoration: BoxDecoration(
                                  color: _sliceColors[i % _sliceColors.length],
                                  borderRadius: BorderRadius.circular(2),
                                ),
                              ),
                              const SizedBox(width: 6),
                              Expanded(
                                child: Text(
                                  '$label (${pct.toStringAsFixed(1)}%)',
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: i == _touchedIndex ? FontWeight.bold : FontWeight.normal,
                                    color: AppTheme.onBackground,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        );
                      }),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
