import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/services/api_service.dart';
import '../../../core/theme/app_theme.dart';

class InvestmentsTab extends StatefulWidget {
  const InvestmentsTab({super.key});

  @override
  State<InvestmentsTab> createState() => _InvestmentsTabState();
}

class _InvestmentsTabState extends State<InvestmentsTab> {
  final ApiService _apiService = ApiService();
  bool _isLoading = true;
  List<dynamic> _investments = [];
  String? _errorMessage;
  String _selectedFilter = 'all';

  // Aggregate stats
  double _totalValue = 0.0;
  double _totalCost = 0.0;
  double _totalPnl = 0.0;
  double _totalPnlPercent = 0.0;

  final List<Map<String, String>> _assetTypes = [
    {'value': 'saham', 'label': 'Saham', 'icon': '📊'},
    {'value': 'reksadana', 'label': 'Reksa Dana', 'icon': '📈'},
    {'value': 'crypto', 'label': 'Crypto', 'icon': '₿'},
    {'value': 'emas', 'label': 'Emas', 'icon': '🥇'},
    {'value': 'deposito', 'label': 'Deposito', 'icon': '🏦'},
    {'value': 'properti', 'label': 'Properti', 'icon': '🏠'},
    {'value': 'lainnya', 'label': 'Lainnya', 'icon': '💼'},
  ];

  @override
  void initState() {
    super.initState();
    _fetchInvestments();
  }

  Future<void> _fetchInvestments() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final response = await _apiService.get('/investments');
      if (response['success'] == true) {
        final List<dynamic> data = response['data'] ?? [];
        setState(() {
          _investments = data;
          _calculateStats();
          _isLoading = false;
        });
      } else {
        setState(() {
          _errorMessage = response['message'] ?? 'Failed to load investments.';
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

  void _calculateStats() {
    double valueSum = 0.0;
    double costSum = 0.0;

    for (var i in _investments) {
      if (i['is_sold'] == false) {
        valueSum += (i['current_value'] as num).toDouble();
        costSum += (i['total_invested'] as num).toDouble();
      }
    }

    _totalValue = valueSum;
    _totalCost = costSum;
    _totalPnl = valueSum - costSum;
    _totalPnlPercent = costSum > 0 ? (_totalPnl / costSum) * 100 : 0.0;
  }

  List<dynamic> get _filteredInvestments {
    if (_selectedFilter == 'all') return _investments;
    return _investments.where((i) => i['asset_type'] == _selectedFilter).toList();
  }

  String _formatCurrency(double amount) {
    // Basic Indonesian Rupiah formatting
    final parts = amount.toStringAsFixed(0).split('.');
    final reg = RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))');
    final formatted = parts[0].replaceAllMapped(reg, (Match m) => '${m[1]}.');
    return formatted;
  }

  // ── Bottom Sheets & Dialogs ──

  void _showAddInvestmentSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppTheme.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => _InvestmentFormSheet(
        apiService: _apiService,
        assetTypes: _assetTypes,
        onSuccess: () {
          Navigator.pop(context);
          _fetchInvestments();
          _showToast('Asset added successfully!', isError: false);
        },
      ),
    );
  }

  void _showUpdatePriceDialog(dynamic investment) {
    final controller = TextEditingController(text: investment['current_price'].toString());
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: AppTheme.surface,
        title: Text(
          'Update Current Price',
          style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.bold),
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              investment['asset_name'],
              style: const TextStyle(color: AppTheme.onSurfaceVariant),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: controller,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: 'Current Price (Rp)',
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
              final newPrice = double.tryParse(controller.text);
              if (newPrice == null || newPrice < 0) {
                _showToast('Please enter a valid price', isError: true);
                return;
              }

              Navigator.pop(context);
              setState(() => _isLoading = true);

              try {
                final response = await _apiService.put(
                  '/investments/${investment['id']}',
                  {'current_price': newPrice},
                );

                if (response['success'] == true) {
                  _fetchInvestments();
                  _showToast('Price updated successfully!', isError: false);
                } else {
                  setState(() => _isLoading = false);
                  _showToast(response['message'] ?? 'Failed to update price', isError: true);
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

  void _showSellAssetDialog(dynamic investment) {
    final controller = TextEditingController(text: investment['current_price'].toString());
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: AppTheme.surface,
        title: Text(
          'Sell Asset Entirely',
          style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.bold),
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Sell all units of ${investment['asset_name']}?',
              style: const TextStyle(color: AppTheme.onSurfaceVariant),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: controller,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: 'Selling Price per Unit (Rp)',
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
              final sellPrice = double.tryParse(controller.text);
              if (sellPrice == null || sellPrice <= 0) {
                _showToast('Please enter a valid price', isError: true);
                return;
              }

              Navigator.pop(context);
              setState(() => _isLoading = true);

              try {
                final response = await _apiService.put(
                  '/investments/${investment['id']}',
                  {
                    'is_sold': true,
                    'sold_price': sellPrice,
                    'sold_date': DateTime.now().toString().split(' ')[0],
                  },
                );

                if (response['success'] == true) {
                  _fetchInvestments();
                  _showToast('Asset marked as Sold!', isError: false);
                } else {
                  setState(() => _isLoading = false);
                  _showToast(response['message'] ?? 'Failed to sell asset', isError: true);
                }
              } catch (e) {
                setState(() => _isLoading = false);
                _showToast(e.toString(), isError: true);
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.secondary),
            child: const Text('Sell Entirely', style: TextStyle(color: Colors.black)),
          ),
        ],
      ),
    );
  }

  void _showDeleteConfirmDialog(dynamic investment) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: AppTheme.surface,
        title: Text(
          'Delete Investment',
          style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.bold),
        ),
        content: Text('Are you sure you want to delete ${investment['asset_name']}? This action is irreversible.'),
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
                final response = await _apiService.delete('/investments/${investment['id']}');

                if (response['success'] == true) {
                  _fetchInvestments();
                  _showToast('Asset deleted successfully', isError: false);
                } else {
                  setState(() => _isLoading = false);
                  _showToast(response['message'] ?? 'Failed to delete asset', isError: true);
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

  // ── UI Components ──

  @override
  Widget build(BuildContext context) {
    if (_isLoading && _investments.isEmpty) {
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
                onPressed: _fetchInvestments,
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    final list = _filteredInvestments;

    return Scaffold(
      floatingActionButton: FloatingActionButton(
        onPressed: _showAddInvestmentSheet,
        backgroundColor: AppTheme.primary,
        child: const Icon(Icons.add, color: Colors.black),
      ),
      body: RefreshIndicator(
        onRefresh: _fetchInvestments,
        color: AppTheme.primaryContainer,
        child: ListView(
          padding: const EdgeInsets.all(16.0),
          physics: const AlwaysScrollableScrollPhysics(),
          children: [
            // 1. Premium Glassy Portfolio Card
            _buildPortfolioCard(),
            const SizedBox(height: 24),

            // 2. Section Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'My Portfolio Assets',
                  style: GoogleFonts.plusJakartaSans(
                    fontWeight: FontWeight.bold,
                    fontSize: 18,
                  ),
                ),
                Text(
                  '${_investments.where((i) => i['is_sold'] == false).length} Active',
                  style: const TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 13),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // 3. Asset Type Filter Chips
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  _buildFilterChip('all', 'All Portfolio'),
                  ..._assetTypes.map((type) => Padding(
                        padding: const EdgeInsets.only(left: 8.0),
                        child: _buildFilterChip(type['value']!, '${type['icon']} ${type['label']}'),
                      )),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // 4. Asset List
            if (list.isEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 48.0),
                child: Center(
                  child: Column(
                    children: [
                      const Icon(Icons.pie_chart_outline, size: 48, color: AppTheme.surfaceVariant),
                      const SizedBox(height: 16),
                      Text(
                        _selectedFilter == 'all' 
                            ? 'No investments logged yet.' 
                            : 'No assets found in this category.',
                        style: const TextStyle(color: AppTheme.onSurfaceVariant),
                      ),
                    ],
                  ),
                ),
              )
            else
              ...list.map((inv) => _buildAssetCard(inv)),
            
            const SizedBox(height: 80), // bottom spacing for FAB
          ],
        ),
      ),
    );
  }

  Widget _buildPortfolioCard() {
    final isPnlPositive = _totalPnl >= 0;
    final pnlColor = isPnlPositive ? AppTheme.primary : AppTheme.error;
    final pnlSign = isPnlPositive ? '+' : '';

    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppTheme.outline),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            Color(0xFF1E2621), // Slate dark emerald
            Color(0xFF141416), // Dark neutral
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
                'NET PORTFOLIO VALUE',
                style: GoogleFonts.dmMono(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: AppTheme.onSurfaceVariant,
                  letterSpacing: 1.0,
                ),
              ),
              const Icon(Icons.account_balance_wallet_outlined, color: AppTheme.primary, size: 20),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            'Rp ${_formatCurrency(_totalValue)}',
            style: GoogleFonts.dmSerifDisplay(
              fontSize: 32,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: pnlColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: [
                    Icon(
                      isPnlPositive ? Icons.arrow_upward : Icons.arrow_downward,
                      color: pnlColor,
                      size: 14,
                    ),
                    const SizedBox(width: 4),
                    Text(
                      '$pnlSign${_totalPnlPercent.toStringAsFixed(2)}%',
                      style: GoogleFonts.dmMono(
                        fontWeight: FontWeight.bold,
                        color: pnlColor,
                        fontSize: 13,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    '$pnlSign Rp ${_formatCurrency(_totalPnl)}',
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      color: pnlColor,
                      fontSize: 14,
                    ),
                  ),
                  const Text(
                    'Total Return (Unrealized)',
                    style: TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 11),
                  ),
                ],
              ),
            ],
          ),
          const Divider(height: 24, color: AppTheme.outline),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Total Cost Basis', style: TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 11)),
                  const SizedBox(height: 4),
                  Text(
                    'Rp ${_formatCurrency(_totalCost)}',
                    style: GoogleFonts.dmMono(
                      color: Colors.white,
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  const Text('Status', style: TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 11)),
                  const SizedBox(height: 4),
                  Text(
                    'Live Synced',
                    style: GoogleFonts.dmMono(
                      color: AppTheme.primary,
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
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

  Widget _buildFilterChip(String value, String label) {
    final isSelected = _selectedFilter == value;
    return ChoiceChip(
      label: Text(label),
      selected: isSelected,
      selectedColor: AppTheme.primaryContainer.withOpacity(0.2),
      checkmarkColor: AppTheme.primary,
      labelStyle: TextStyle(
        color: isSelected ? AppTheme.primary : AppTheme.onBackground,
        fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
      ),
      onSelected: (selected) {
        if (selected) {
          setState(() {
            _selectedFilter = value;
          });
        }
      },
    );
  }

  Widget _buildAssetCard(dynamic inv) {
    final double buyPrice = (inv['buy_price'] as num).toDouble();
    final double quantity = (inv['quantity'] as num).toDouble();
    final double currentPrice = (inv['current_price'] as num).toDouble();
    final double currentValue = (inv['current_value'] as num).toDouble();
    final double pnl = (inv['pnl'] as num).toDouble();
    final double pnlPct = (inv['pnl_pct'] as num).toDouble();
    final bool isSold = inv['is_sold'] == true;

    final isPnlPositive = pnl >= 0;
    final pnlColor = isSold 
        ? Colors.grey 
        : (isPnlPositive ? AppTheme.primary : AppTheme.error);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: isSold ? AppTheme.outline.withOpacity(0.5) : AppTheme.outline),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () => _showAssetActionMenu(inv),
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Row(
            children: [
              // Asset Icon Badge
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: isSold ? AppTheme.surfaceVariant.withOpacity(0.3) : AppTheme.surfaceVariant,
                  borderRadius: BorderRadius.circular(12),
                ),
                alignment: Alignment.center,
                child: Text(
                  inv['asset_type_icon'] ?? '💼',
                  style: TextStyle(fontSize: 22, color: isSold ? Colors.grey : Colors.white),
                ),
              ),
              const SizedBox(width: 16),

              // Title and metrics
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            inv['asset_name'],
                            style: GoogleFonts.plusJakartaSans(
                              fontWeight: FontWeight.bold,
                              fontSize: 16,
                              color: isSold ? Colors.grey : Colors.white,
                              decoration: isSold ? TextDecoration.lineThrough : null,
                            ),
                          ),
                        ),
                        if (isSold)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: Colors.grey.withOpacity(0.2),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: const Text(
                              'SOLD',
                              style: TextStyle(color: Colors.grey, fontSize: 10, fontWeight: FontWeight.bold),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      '$quantity units @ Rp ${_formatCurrency(buyPrice)}',
                      style: const TextStyle(fontSize: 13, color: AppTheme.onSurfaceVariant),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'Market: Rp ${_formatCurrency(currentPrice)}',
                      style: TextStyle(
                        fontSize: 12, 
                        color: isSold ? Colors.grey : AppTheme.onSurfaceVariant.withOpacity(0.8),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),

              // Trailing Values (Current portfolio value & PnL)
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    isSold ? 'Sold Out' : 'Rp ${_formatCurrency(currentValue)}',
                    style: GoogleFonts.dmMono(
                      fontWeight: FontWeight.bold,
                      fontSize: 15,
                      color: isSold ? Colors.grey : Colors.white,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: pnlColor.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      '${pnl >= 0 ? '+' : ''}${pnlPct.toStringAsFixed(1)}%',
                      style: GoogleFonts.dmMono(
                        fontWeight: FontWeight.bold,
                        fontSize: 11,
                        color: pnlColor,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showAssetActionMenu(dynamic inv) {
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
                inv['asset_name'],
                style: GoogleFonts.plusJakartaSans(
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
            ),
            const Divider(height: 1, color: AppTheme.outline),
            if (inv['is_sold'] == false) ...[
              ListTile(
                leading: const Icon(Icons.edit_road_outlined, color: AppTheme.primary),
                title: const Text('Update Current Price'),
                onTap: () {
                  Navigator.pop(context);
                  _showUpdatePriceDialog(inv);
                },
              ),
              ListTile(
                leading: const Icon(Icons.sell_outlined, color: AppTheme.secondary),
                title: const Text('Sell Asset Entirely'),
                onTap: () {
                  Navigator.pop(context);
                  _showSellAssetDialog(inv);
                },
              ),
            ],
            ListTile(
              leading: const Icon(Icons.delete_outline, color: AppTheme.error),
              title: const Text('Delete Asset Record'),
              onTap: () {
                Navigator.pop(context);
                _showDeleteConfirmDialog(inv);
              },
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }
}

// ── ADD INVESTMENT FORM BOTTOM SHEET ──

class _InvestmentFormSheet extends StatefulWidget {
  final ApiService apiService;
  final List<Map<String, String>> assetTypes;
  final VoidCallback onSuccess;

  const _InvestmentFormSheet({
    required this.apiService,
    required this.assetTypes,
    required this.onSuccess,
  });

  @override
  State<_InvestmentFormSheet> createState() => _InvestmentFormSheetState();
}

class _InvestmentFormSheetState extends State<_InvestmentFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _quantityController = TextEditingController();
  final _buyPriceController = TextEditingController();
  final _currentPriceController = TextEditingController();
  final _notesController = TextEditingController();
  
  String _selectedType = 'saham';
  DateTime _buyDate = DateTime.now();
  bool _submitting = false;

  @override
  void dispose() {
    _nameController.dispose();
    _quantityController.dispose();
    _buyPriceController.dispose();
    _currentPriceController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_formKey.currentState!.validate()) {
      setState(() => _submitting = true);

      try {
        final q = double.parse(_quantityController.text);
        final bp = double.parse(_buyPriceController.text);
        final cp = _currentPriceController.text.isNotEmpty 
            ? double.parse(_currentPriceController.text) 
            : bp;

        final response = await widget.apiService.post(
          '/investments',
          {
            'asset_name': _nameController.text.trim(),
            'asset_type': _selectedType,
            'quantity': q,
            'buy_price': bp,
            'current_price': cp,
            'buy_date': _buyDate.toString().split(' ')[0],
            'notes': _notesController.text.trim(),
          },
        );

        if (response['success'] == true) {
          widget.onSuccess();
        } else {
          setState(() => _submitting = false);
          _showToast(response['message'] ?? 'Error creating asset');
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
                'Log New Investment',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 24),

              // Asset Name
              TextFormField(
                controller: _nameController,
                decoration: const InputDecoration(
                  labelText: 'Asset Name',
                  hintText: 'e.g. Bank BCA (BBCA), Bitcoin, Antam Gold',
                  border: OutlineInputBorder(),
                ),
                validator: (value) => value == null || value.isEmpty ? 'Required' : null,
              ),
              const SizedBox(height: 16),

              // Asset Type Dropdown
              DropdownButtonFormField<String>(
                value: _selectedType,
                decoration: const InputDecoration(
                  labelText: 'Asset Category',
                  border: OutlineInputBorder(),
                ),
                dropdownColor: AppTheme.surface,
                items: widget.assetTypes
                    .map((t) => DropdownMenuItem(
                          value: t['value'],
                          child: Text('${t['icon']}  ${t['label']}'),
                        ))
                    .toList(),
                onChanged: (val) {
                  if (val != null) {
                    setState(() => _selectedType = val);
                  }
                },
              ),
              const SizedBox(height: 16),

              // Quantity & Buy Price in a Row
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _quantityController,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: const InputDecoration(
                        labelText: 'Quantity',
                        hintText: 'e.g. 100, 0.045',
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
                      controller: _buyPriceController,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: const InputDecoration(
                        labelText: 'Buy Price / Unit (Rp)',
                        hintText: 'e.g. 9500, 1050000',
                        border: OutlineInputBorder(),
                      ),
                      validator: (value) {
                        if (value == null || value.isEmpty) return 'Required';
                        if (double.tryParse(value) == null || double.parse(value) < 0) return 'Invalid';
                        return null;
                      },
                      onChanged: (val) {
                        // Prefill current price with buy price initially for convenience
                        if (_currentPriceController.text.isEmpty) {
                          _currentPriceController.text = val;
                        }
                      },
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),

              // Current Price & Buy Date
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _currentPriceController,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: const InputDecoration(
                        labelText: 'Current Price / Unit (Rp)',
                        hintText: 'Defaults to Buy Price',
                        border: OutlineInputBorder(),
                      ),
                      validator: (value) {
                        if (value == null || value.isEmpty) return null;
                        if (double.tryParse(value) == null || double.parse(value) < 0) return 'Invalid';
                        return null;
                      },
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: _buyDate,
                          firstDate: DateTime(2020),
                          lastDate: DateTime.now(),
                        );
                        if (picked != null) {
                          setState(() => _buyDate = picked);
                        }
                      },
                      icon: const Icon(Icons.calendar_today_outlined, size: 16),
                      label: Text(
                        '${_buyDate.day}/${_buyDate.month}/${_buyDate.year}',
                        style: const TextStyle(fontSize: 13),
                      ),
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size(double.infinity, 56),
                        side: BorderSide(color: AppTheme.outline),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(4),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),

              // Notes
              TextFormField(
                controller: _notesController,
                maxLines: 2,
                decoration: const InputDecoration(
                  labelText: 'Notes (Optional)',
                  hintText: 'e.g. Bought via Bibit, long term asset',
                  border: OutlineInputBorder(),
                ),
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
                          'Add to Portfolio',
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
