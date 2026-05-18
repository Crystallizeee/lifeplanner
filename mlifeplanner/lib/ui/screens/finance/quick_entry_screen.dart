import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/services/api_service.dart';
import '../../../core/theme/app_theme.dart';
import 'package:intl/intl.dart';

class QuickEntryScreen extends StatefulWidget {
  const QuickEntryScreen({super.key});

  @override
  State<QuickEntryScreen> createState() => _QuickEntryScreenState();
}

class _QuickEntryScreenState extends State<QuickEntryScreen> {
  final ApiService _apiService = ApiService();
  final _formKey = GlobalKey<FormState>();
  
  String _selectedType = 'expense';
  int? _selectedCategoryId;
  
  final _amountController = TextEditingController();
  final _descController = TextEditingController();
  final _notesController = TextEditingController();
  DateTime _transactionDate = DateTime.now();

  List<dynamic> _categories = [];
  bool _isLoadingCategories = true;
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _fetchCategories();
  }

  @override
  void dispose() {
    _amountController.dispose();
    _descController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _fetchCategories() async {
    setState(() => _isLoadingCategories = true);
    try {
      final response = await _apiService.get('/categories?type=$_selectedType');
      if (response['success'] == true) {
        setState(() {
          _categories = response['data'];
          if (_categories.isNotEmpty) {
            _selectedCategoryId = _categories.first['id'];
          } else {
            _selectedCategoryId = null;
          }
          _isLoadingCategories = false;
        });
      }
    } catch (e) {
      setState(() => _isLoadingCategories = false);
    }
  }

  Future<void> _saveTransaction() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedCategoryId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select a category')),
      );
      return;
    }

    setState(() => _isSaving = true);

    try {
      final response = await _apiService.post('/transactions', {
        'type': _selectedType,
        'category_id': _selectedCategoryId,
        'amount': _amountController.text,
        'description': _descController.text,
        'transaction_date': DateFormat('yyyy-MM-dd').format(_transactionDate),
        'notes': _notesController.text.isNotEmpty ? _notesController.text : null,
      });

      if (response['success'] == true && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Transaction saved! 🎉'),
            backgroundColor: AppTheme.primaryContainer,
          ),
        );
        Navigator.pop(context, true); // Return true to signal refresh
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(e.toString()),
            backgroundColor: AppTheme.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Quick Entry'),
      ),
      body: _isLoadingCategories && _categories.isEmpty
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryContainer))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(24.0),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // Type Selector
                    SegmentedButton<String>(
                      segments: const [
                        ButtonSegment(value: 'expense', label: Text('Expense')),
                        ButtonSegment(value: 'income', label: Text('Income')),
                        ButtonSegment(value: 'bill', label: Text('Bill')),
                      ],
                      selected: {_selectedType},
                      onSelectionChanged: (Set<String> newSelection) {
                        setState(() {
                          _selectedType = newSelection.first;
                        });
                        _fetchCategories();
                      },
                      style: ButtonStyle(
                        backgroundColor: MaterialStateProperty.resolveWith<Color>((states) {
                          if (states.contains(MaterialState.selected)) {
                            return AppTheme.primaryContainer;
                          }
                          return Colors.transparent;
                        }),
                        foregroundColor: MaterialStateProperty.resolveWith<Color>((states) {
                          if (states.contains(MaterialState.selected)) {
                            return Colors.black;
                          }
                          return AppTheme.onBackground;
                        }),
                      ),
                    ),
                    const SizedBox(height: 24),
                    
                    // Amount
                    TextFormField(
                      controller: _amountController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(
                        labelText: 'Amount (Rp)',
                        prefixIcon: Icon(Icons.attach_money),
                      ),
                      validator: (value) => (value == null || value.isEmpty) ? 'Required' : null,
                    ),
                    const SizedBox(height: 16),
                    
                    // Description
                    TextFormField(
                      controller: _descController,
                      decoration: const InputDecoration(
                        labelText: 'Description',
                        prefixIcon: Icon(Icons.description_outlined),
                      ),
                      validator: (value) => (value == null || value.isEmpty) ? 'Required' : null,
                    ),
                    const SizedBox(height: 16),

                    // Category Dropdown
                    DropdownButtonFormField<int>(
                      value: _selectedCategoryId,
                      decoration: const InputDecoration(
                        labelText: 'Category',
                        prefixIcon: Icon(Icons.category_outlined),
                      ),
                      items: _categories.map((c) {
                        return DropdownMenuItem<int>(
                          value: c['id'],
                          child: Text('${c['icon']} ${c['name']}'),
                        );
                      }).toList(),
                      onChanged: (val) {
                        setState(() {
                          _selectedCategoryId = val;
                        });
                      },
                    ),
                    const SizedBox(height: 16),
                    
                    // Date Picker
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Transaction Date'),
                      subtitle: Text(DateFormat('dd MMM yyyy').format(_transactionDate)),
                      leading: const Icon(Icons.calendar_today),
                      onTap: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: _transactionDate,
                          firstDate: DateTime(2020),
                          lastDate: DateTime(2100),
                        );
                        if (picked != null) {
                          setState(() {
                            _transactionDate = picked;
                          });
                        }
                      },
                    ),
                    const SizedBox(height: 32),
                    
                    // Submit Button
                    SizedBox(
                      height: 56,
                      child: ElevatedButton(
                        onPressed: _isSaving ? null : _saveTransaction,
                        child: _isSaving 
                            ? const SizedBox(
                                width: 24, height: 24, 
                                child: CircularProgressIndicator(color: Colors.black, strokeWidth: 2)
                              )
                            : const Text('Save Transaction'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }
}
