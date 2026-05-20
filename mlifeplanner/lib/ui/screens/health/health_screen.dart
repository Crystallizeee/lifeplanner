import 'package:flutter/material.dart';
import '../../../core/services/api_service.dart';
import '../../../core/theme/app_theme.dart';
import 'package:intl/intl.dart';

class HealthScreen extends StatefulWidget {
  const HealthScreen({super.key});

  @override
  State<HealthScreen> createState() => _HealthScreenState();
}

class _HealthScreenState extends State<HealthScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final ApiService _apiService = ApiService();

  // Habit States
  bool _isLoadingHabits = true;
  List<dynamic> _habits = [];
  String? _habitError;

  // Weight States
  bool _isLoadingWeight = true;
  List<dynamic> _weightLogs = [];
  String? _weightError;

  // Meal Planner States
  bool _isLoadingMeals = true;
  List<dynamic> _meals = [];
  String? _mealsError;
  late DateTime _currentWeekStart;

  // Grocery List States
  bool _isLoadingGrocery = true;
  List<dynamic> _groceryItems = [];
  String? _groceryError;
  late DateTime _groceryWeekStart;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    
    final now = DateTime.now();
    _currentWeekStart = DateTime(now.year, now.month, now.day).subtract(Duration(days: now.weekday - 1));
    _groceryWeekStart = DateTime(now.year, now.month, now.day).subtract(Duration(days: now.weekday - 1));

    _fetchHabits();
    _fetchWeightLogs();
    _fetchMeals();
    _fetchGroceryItems();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _fetchMeals() async {
    setState(() {
      _isLoadingMeals = true;
      _mealsError = null;
    });
    try {
      final formattedDate = DateFormat('yyyy-MM-dd').format(_currentWeekStart);
      final response = await _apiService.get('/meals?week_start=$formattedDate');
      if (response['success'] == true) {
        setState(() {
          _meals = response['data'];
          _isLoadingMeals = false;
        });
      }
    } catch (e) {
      setState(() {
        _mealsError = e.toString();
        _isLoadingMeals = false;
      });
    }
  }

  Future<void> _fetchGroceryItems() async {
    setState(() {
      _isLoadingGrocery = true;
      _groceryError = null;
    });
    try {
      final formattedDate = DateFormat('yyyy-MM-dd').format(_groceryWeekStart);
      final response = await _apiService.get('/grocery-lists?week_start=$formattedDate');
      if (response['success'] == true) {
        setState(() {
          _groceryItems = response['data'];
          _isLoadingGrocery = false;
        });
      }
    } catch (e) {
      setState(() {
        _groceryError = e.toString();
        _isLoadingGrocery = false;
      });
    }
  }

  Future<void> _saveMeal(String date, String mealTime, String mealName, int? calories, String? recipeNotes) async {
    try {
      final response = await _apiService.post('/meals', {
        'date': date,
        'meal_time': mealTime,
        'meal_name': mealName,
        'calories': calories,
        'recipe_notes': recipeNotes,
      });
      if (response['success'] == true) {
        _fetchMeals();
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to save meal: $e'), backgroundColor: AppTheme.error),
      );
    }
  }

  Future<void> _deleteMeal(int id) async {
    try {
      final response = await _apiService.delete('/meals/$id');
      if (response['success'] == true) {
        _fetchMeals();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Meal plan deleted'), backgroundColor: AppTheme.primaryContainer),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to delete meal: $e'), backgroundColor: AppTheme.error),
      );
    }
  }

  Future<void> _copyPreviousWeek() async {
    try {
      final formattedDate = DateFormat('yyyy-MM-dd').format(_currentWeekStart);
      final response = await _apiService.post('/meals/copy-week', {
        'target_week_start': formattedDate,
      });
      if (response['success'] == true) {
        _fetchMeals();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(response['message'] ?? 'Successfully copied meals'), backgroundColor: AppTheme.primaryContainer),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to copy meals: $e'), backgroundColor: AppTheme.error),
      );
    }
  }

  Future<void> _addGroceryItem(String itemName, double? qty, String? unit, String? category) async {
    try {
      final formattedDate = DateFormat('yyyy-MM-dd').format(_groceryWeekStart);
      final response = await _apiService.post('/grocery-lists', {
        'week_start': formattedDate,
        'item_name': itemName,
        'qty': qty,
        'unit': unit,
        'category': category,
        'is_checked': false,
      });
      if (response['success'] == true) {
        _fetchGroceryItems();
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to add item: $e'), backgroundColor: AppTheme.error),
      );
    }
  }

  Future<void> _toggleGroceryItem(int id, bool isChecked) async {
    try {
      final response = await _apiService.put('/grocery-lists/$id', {
        'is_checked': isChecked,
      });
      if (response['success'] == true) {
        setState(() {
          final index = _groceryItems.indexWhere((item) => item['id'] == id);
          if (index != -1) {
            _groceryItems[index] = response['data'];
          }
        });
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to update item: $e'), backgroundColor: AppTheme.error),
      );
    }
  }

  Future<void> _deleteGroceryItem(int id) async {
    try {
      final response = await _apiService.delete('/grocery-lists/$id');
      if (response['success'] == true) {
        setState(() {
          _groceryItems.removeWhere((item) => item['id'] == id);
        });
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to delete item: $e'), backgroundColor: AppTheme.error),
      );
    }
  }

  Future<void> _resetGroceryList() async {
    try {
      final formattedDate = DateFormat('yyyy-MM-dd').format(_groceryWeekStart);
      final response = await _apiService.delete('/grocery-lists/reset?week_start=$formattedDate');
      if (response['success'] == true) {
        _fetchGroceryItems();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Grocery list reset'), backgroundColor: AppTheme.primaryContainer),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to reset list: $e'), backgroundColor: AppTheme.error),
      );
    }
  }

  Future<void> _generateGroceryList() async {
    try {
      final formattedDate = DateFormat('yyyy-MM-dd').format(_groceryWeekStart);
      final response = await _apiService.post('/grocery-lists/generate', {
        'week_start': formattedDate,
      });
      if (response['success'] == true) {
        _fetchGroceryItems();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(response['message'] ?? 'Successfully generated items'), backgroundColor: AppTheme.primaryContainer),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to generate list: $e'), backgroundColor: AppTheme.error),
      );
    }
  }

  Future<void> _fetchHabits() async {
    setState(() {
      _isLoadingHabits = true;
      _habitError = null;
    });

    try {
      final response = await _apiService.get('/habits');
      if (response['success'] == true) {
        setState(() {
          _habits = response['data'];
          _isLoadingHabits = false;
        });
      }
    } catch (e) {
      setState(() {
        _habitError = e.toString();
        _isLoadingHabits = false;
      });
    }
  }

  Future<void> _fetchWeightLogs() async {
    setState(() {
      _isLoadingWeight = true;
      _weightError = null;
    });

    try {
      final response = await _apiService.get('/weight-logs');
      if (response['success'] == true) {
        setState(() {
          _weightLogs = response['data'];
          _isLoadingWeight = false;
        });
      }
    } catch (e) {
      setState(() {
        _weightError = e.toString();
        _isLoadingWeight = false;
      });
    }
  }

  Future<void> _toggleHabit(int id) async {
    try {
      final response = await _apiService.post('/habits/$id/log', {});
      if (response['success'] == true) {
        setState(() {
          final index = _habits.indexWhere((h) => h['id'] == id);
          if (index != -1) {
            _habits[index] = response['data'];
          }
        });
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to update habit: $e'), backgroundColor: AppTheme.error),
      );
    }
  }

  Future<void> _deleteHabit(int id) async {
    try {
      final response = await _apiService.delete('/habits/$id');
      if (response['success'] == true) {
        setState(() {
          _habits.removeWhere((h) => h['id'] == id);
        });
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Habit deleted'), backgroundColor: AppTheme.primaryContainer),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to delete habit: $e'), backgroundColor: AppTheme.error),
      );
    }
  }

  Future<void> _deleteWeightLog(int id) async {
    try {
      final response = await _apiService.delete('/weight-logs/$id');
      if (response['success'] == true) {
        setState(() {
          _weightLogs.removeWhere((l) => l['id'] == id);
        });
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Weight log deleted'), backgroundColor: AppTheme.primaryContainer),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to delete weight log: $e'), backgroundColor: AppTheme.error),
      );
    }
  }

  void _showAddHabitSheet() {
    final nameController = TextEditingController();
    final iconController = TextEditingController(text: '💪');
    final descController = TextEditingController();
    String frequency = 'daily';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppTheme.background,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(context).viewInsets.bottom,
                left: 24,
                right: 24,
                top: 24,
              ),
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text('Create New Habit', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold)),
                    const SizedBox(height: 20),
                    TextFormField(
                      controller: nameController,
                      decoration: const InputDecoration(
                        labelText: 'Habit Name',
                        prefixIcon: Icon(Icons.fitness_center),
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: iconController,
                      decoration: const InputDecoration(
                        labelText: 'Icon Emoji',
                        prefixIcon: Icon(Icons.emoji_emotions_outlined),
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: descController,
                      decoration: const InputDecoration(
                        labelText: 'Description (Optional)',
                        prefixIcon: Icon(Icons.description_outlined),
                      ),
                    ),
                    const SizedBox(height: 16),
                    DropdownButtonFormField<String>(
                      value: frequency,
                      decoration: const InputDecoration(
                        labelText: 'Frequency',
                        prefixIcon: Icon(Icons.repeat),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'daily', child: Text('Daily')),
                        DropdownMenuItem(value: 'weekly', child: Text('Weekly')),
                      ],
                      onChanged: (val) {
                        if (val != null) setModalState(() => frequency = val);
                      },
                    ),
                    const SizedBox(height: 24),
                    ElevatedButton(
                      onPressed: () async {
                        if (nameController.text.trim().isEmpty) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Name is required'), backgroundColor: AppTheme.error),
                          );
                          return;
                        }
                        Navigator.pop(context);

                        setState(() => _isLoadingHabits = true);
                        try {
                          final response = await _apiService.post('/habits', {
                            'name': nameController.text.trim(),
                            'icon': iconController.text.trim(),
                            'description': descController.text.trim().isNotEmpty ? descController.text.trim() : null,
                            'frequency': frequency,
                          });
                          if (response['success'] == true) _fetchHabits();
                        } catch (e) {
                          setState(() => _isLoadingHabits = false);
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text('Failed to create habit: $e'), backgroundColor: AppTheme.error),
                          );
                        }
                      },
                      child: const Text('Create Habit'),
                    ),
                    const SizedBox(height: 24),
                  ],
                ),
              ),
            );
          }
        );
      },
    );
  }

  void _showAddWeightSheet() {
    final weightController = TextEditingController();
    final notesController = TextEditingController();
    DateTime logDate = DateTime.now();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppTheme.background,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(context).viewInsets.bottom,
                left: 24,
                right: 24,
                top: 24,
              ),
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text('Log Weight', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold)),
                    const SizedBox(height: 20),
                    TextFormField(
                      controller: weightController,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: const InputDecoration(
                        labelText: 'Weight (kg)',
                        prefixIcon: Icon(Icons.monitor_weight_outlined),
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: notesController,
                      decoration: const InputDecoration(
                        labelText: 'Notes (Optional)',
                        prefixIcon: Icon(Icons.edit_note),
                      ),
                    ),
                    const SizedBox(height: 16),
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Log Date'),
                      subtitle: Text(DateFormat('dd MMM yyyy').format(logDate)),
                      leading: const Icon(Icons.calendar_today),
                      onTap: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: logDate,
                          firstDate: DateTime(2020),
                          lastDate: DateTime.now(),
                        );
                        if (picked != null) setModalState(() => logDate = picked);
                      },
                    ),
                    const SizedBox(height: 24),
                    ElevatedButton(
                      onPressed: () async {
                        if (weightController.text.trim().isEmpty) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Weight is required'), backgroundColor: AppTheme.error),
                          );
                          return;
                        }
                        Navigator.pop(context);

                        setState(() => _isLoadingWeight = true);
                        try {
                          final response = await _apiService.post('/weight-logs', {
                            'weight_kg': double.tryParse(weightController.text) ?? 0.0,
                            'notes': notesController.text.trim().isNotEmpty ? notesController.text.trim() : null,
                            'date': DateFormat('yyyy-MM-dd').format(logDate),
                          });
                          if (response['success'] == true) _fetchWeightLogs();
                        } catch (e) {
                          setState(() => _isLoadingWeight = false);
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text('Failed to log weight: $e'), backgroundColor: AppTheme.error),
                          );
                        }
                      },
                      child: const Text('Save Log'),
                    ),
                    const SizedBox(height: 24),
                  ],
                ),
              ),
            );
          }
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Health & Life'),
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          indicatorColor: AppTheme.primaryContainer,
          labelColor: AppTheme.primaryContainer,
          unselectedLabelColor: AppTheme.onBackground,
          tabs: const [
            Tab(icon: Icon(Icons.flash_on), text: 'Habits'),
            Tab(icon: Icon(Icons.monitor_weight), text: 'Weight Log'),
            Tab(icon: Icon(Icons.restaurant_menu), text: 'Meal Planner'),
            Tab(icon: Icon(Icons.shopping_basket), text: 'Grocery List'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildHabitsTab(),
          _buildWeightTab(),
          _buildMealPlannerTab(),
          _buildGroceryListTab(),
        ],
      ),
    );
  }

  Widget _buildHabitsTab() {
    if (_isLoadingHabits) {
      return const Center(child: CircularProgressIndicator(color: AppTheme.primaryContainer));
    }

    if (_habitError != null) {
      return Center(child: Text(_habitError!, style: const TextStyle(color: AppTheme.error)));
    }

    return Scaffold(
      body: RefreshIndicator(
        onRefresh: _fetchHabits,
        color: AppTheme.primaryContainer,
        child: _habits.isEmpty
            ? const Center(child: Text('No habits created. Build positive routines today!'))
            : ListView.builder(
                padding: const EdgeInsets.all(16.0),
                itemCount: _habits.length,
                itemBuilder: (context, index) {
                  final habit = _habits[index];
                  final isChecked = habit['checked_today'] ?? false;
                  
                  return Card(
                    margin: const EdgeInsets.only(bottom: 12),
                    child: ListTile(
                      leading: Text(
                        habit['icon'] ?? '💪',
                        style: const TextStyle(fontSize: 28),
                      ),
                      title: Text(
                        habit['name'] ?? '',
                        style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16),
                      ),
                      subtitle: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          if (habit['description'] != null)
                            Text(habit['description'], style: const TextStyle(color: AppTheme.onSurfaceVariant)),
                          const SizedBox(height: 4),
                          Row(
                            children: [
                              const Icon(Icons.local_fire_department, color: Colors.orange, size: 16),
                              const SizedBox(width: 4),
                              Text(
                                '${habit['current_streak']} days streak (Best: ${habit['longest_streak']})',
                                style: const TextStyle(color: Colors.orange, fontSize: 12, fontWeight: FontWeight.bold),
                              ),
                            ],
                          ),
                        ],
                      ),
                      trailing: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          IconButton(
                            icon: Icon(
                              isChecked ? Icons.check_circle : Icons.radio_button_unchecked,
                              color: isChecked ? AppTheme.primaryContainer : AppTheme.onSurfaceVariant,
                              size: 28,
                            ),
                            onPressed: () => _toggleHabit(habit['id']),
                          ),
                          IconButton(
                            icon: const Icon(Icons.delete_outline, color: AppTheme.error, size: 20),
                            onPressed: () {
                              showDialog(
                                context: context,
                                builder: (context) => AlertDialog(
                                  title: const Text('Delete Habit'),
                                  content: const Text('Are you sure you want to delete this habit?'),
                                  actions: [
                                    TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
                                    TextButton(
                                      onPressed: () {
                                        Navigator.pop(context);
                                        _deleteHabit(habit['id']);
                                      },
                                      child: const Text('Delete', style: TextStyle(color: AppTheme.error)),
                                    ),
                                  ],
                                ),
                              );
                            },
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
      ),
      floatingActionButton: FloatingActionButton(
        heroTag: 'health_add_habit_fab',
        onPressed: _showAddHabitSheet,
        backgroundColor: AppTheme.primaryContainer,
        foregroundColor: Colors.black,
        child: const Icon(Icons.add),
      ),
    );
  }

  Widget _buildWeightTab() {
    if (_isLoadingWeight) {
      return const Center(child: CircularProgressIndicator(color: AppTheme.primaryContainer));
    }

    if (_weightError != null) {
      return Center(child: Text(_weightError!, style: const TextStyle(color: AppTheme.error)));
    }

    final latestWeight = _weightLogs.isNotEmpty ? _weightLogs.first['weight_kg'] : null;

    return Scaffold(
      body: RefreshIndicator(
        onRefresh: _fetchWeightLogs,
        color: AppTheme.primaryContainer,
        child: Column(
          children: [
            if (latestWeight != null) ...[
              Container(
                margin: const EdgeInsets.all(16),
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: AppTheme.surfaceVariant,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppTheme.outline.withOpacity(0.5)),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('LATEST WEIGHT LOGGED', style: TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 12, fontWeight: FontWeight.bold)),
                        const SizedBox(height: 6),
                        Text('$latestWeight kg', style: const TextStyle(color: AppTheme.primaryContainer, fontSize: 32, fontWeight: FontWeight.bold)),
                      ],
                    ),
                    const Icon(Icons.monitor_weight, color: AppTheme.primaryContainer, size: 48),
                  ],
                ),
              ),
            ],
            Expanded(
              child: _weightLogs.isEmpty
                  ? const Center(child: Text('No weight entries logged yet.'))
                  : ListView.builder(
                      padding: const EdgeInsets.symmetric(horizontal: 16.0),
                      itemCount: _weightLogs.length,
                      itemBuilder: (context, index) {
                        final log = _weightLogs[index];
                        return Card(
                          margin: const EdgeInsets.only(bottom: 12),
                          child: ListTile(
                            leading: const CircleAvatar(
                              backgroundColor: AppTheme.surfaceVariant,
                              child: Icon(Icons.scale, color: AppTheme.onSurfaceVariant),
                            ),
                            title: Text(
                              '${log['weight_kg']} kg',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                            ),
                            subtitle: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(log['date'], style: const TextStyle(color: AppTheme.onSurfaceVariant)),
                                if (log['notes'] != null && log['notes'].toString().isNotEmpty)
                                  Padding(
                                    padding: const EdgeInsets.only(top: 4),
                                    child: Text(log['notes'], style: const TextStyle(color: AppTheme.onSurfaceVariant, fontStyle: FontStyle.italic)),
                                  ),
                              ],
                            ),
                            trailing: IconButton(
                              icon: const Icon(Icons.delete_outline, color: AppTheme.error),
                              onPressed: () {
                                showDialog(
                                  context: context,
                                  builder: (context) => AlertDialog(
                                    title: const Text('Delete Log'),
                                    content: const Text('Delete this weight entry?'),
                                    actions: [
                                      TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
                                      TextButton(
                                        onPressed: () {
                                          Navigator.pop(context);
                                          _deleteWeightLog(log['id']);
                                        },
                                        child: const Text('Delete', style: TextStyle(color: AppTheme.error)),
                                      ),
                                    ],
                                  ),
                                );
                              },
                            ),
                          ),
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton(
        heroTag: 'health_add_weight_fab',
        onPressed: _showAddWeightSheet,
        backgroundColor: AppTheme.primaryContainer,
        foregroundColor: Colors.black,
        child: const Icon(Icons.add),
      ),
    );
  }

  Widget _buildMealPlannerTab() {
    final daysOfWeek = List.generate(7, (i) => _currentWeekStart.add(Duration(days: i)));
    final weekStartStr = DateFormat('dd MMM').format(_currentWeekStart);
    final weekEndStr = DateFormat('dd MMM yyyy').format(_currentWeekStart.add(const Duration(days: 6)));

    return Scaffold(
      body: RefreshIndicator(
        onRefresh: _fetchMeals,
        color: AppTheme.primaryContainer,
        child: Column(
          children: [
            // Week navigation header
            Padding(
              padding: const EdgeInsets.all(12.0),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  IconButton(
                    icon: const Icon(Icons.chevron_left),
                    onPressed: () => _changeMealWeek(-7),
                  ),
                  Text(
                    '$weekStartStr - $weekEndStr',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                  IconButton(
                    icon: const Icon(Icons.chevron_right),
                    onPressed: () => _changeMealWeek(7),
                  ),
                ],
              ),
            ),
            
            // Actions row
            Padding(
              padding: const EdgeInsets.only(left: 16.0, right: 16.0, bottom: 8.0),
              child: OutlinedButton.icon(
                icon: const Icon(Icons.copy_all),
                label: const Text('Copy Previous Week'),
                style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(40)),
                onPressed: _copyPreviousWeek,
              ),
            ),

            Expanded(
              child: _isLoadingMeals
                  ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryContainer))
                  : _mealsError != null
                      ? Center(child: Text(_mealsError!, style: const TextStyle(color: AppTheme.error)))
                      : ListView.builder(
                          padding: const EdgeInsets.all(16.0),
                          itemCount: 7,
                          itemBuilder: (context, index) {
                            final day = daysOfWeek[index];
                            final dayName = DateFormat('EEEE, dd MMM').format(day);
                            final dateStr = DateFormat('yyyy-MM-dd').format(day);

                            return Card(
                              margin: const EdgeInsets.only(bottom: 16),
                              child: Padding(
                                padding: const EdgeInsets.all(12.0),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      dayName,
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppTheme.primary),
                                    ),
                                    const Divider(height: 16),
                                    ...['breakfast', 'lunch', 'dinner', 'snack'].map((time) {
                                      final meal = _meals.firstWhere(
                                        (m) => m['date'] == dateStr && m['meal_time'] == time,
                                        orElse: () => null,
                                      );

                                      String timeLabel = '';
                                      IconData timeIcon = Icons.fastfood;
                                      Color iconColor = Colors.grey;

                                      if (time == 'breakfast') {
                                        timeLabel = 'Sarapan';
                                        timeIcon = Icons.wb_sunny_outlined;
                                        iconColor = Colors.orangeAccent;
                                      } else if (time == 'lunch') {
                                        timeLabel = 'Makan Siang';
                                        timeIcon = Icons.wb_sunny;
                                        iconColor = Colors.yellow;
                                      } else if (time == 'dinner') {
                                        timeLabel = 'Makan Malam';
                                        timeIcon = Icons.nights_stay_outlined;
                                        iconColor = Colors.purpleAccent;
                                      } else {
                                        timeLabel = 'Camilan';
                                        timeIcon = Icons.cookie_outlined;
                                        iconColor = Colors.brown;
                                      }

                                      return InkWell(
                                        onTap: () => _showMealFormSheet(day, time, meal),
                                        child: Padding(
                                          padding: const EdgeInsets.symmetric(vertical: 8.0),
                                          child: Row(
                                            children: [
                                              Icon(timeIcon, color: iconColor, size: 20),
                                              const SizedBox(width: 8),
                                              Expanded(
                                                child: Column(
                                                  crossAxisAlignment: CrossAxisAlignment.start,
                                                  children: [
                                                    Text(
                                                      timeLabel,
                                                      style: const TextStyle(fontSize: 12, color: AppTheme.onSurfaceVariant),
                                                    ),
                                                    Text(
                                                      meal != null ? meal['meal_name'] : 'Add Menu...',
                                                      style: TextStyle(
                                                        fontSize: 14,
                                                        fontWeight: meal != null ? FontWeight.w600 : FontWeight.normal,
                                                        color: meal != null ? AppTheme.onBackground : AppTheme.onSurfaceVariant.withOpacity(0.5),
                                                        fontStyle: meal != null ? FontStyle.normal : FontStyle.italic,
                                                      ),
                                                    ),
                                                    if (meal != null && meal['calories'] != null)
                                                      Text(
                                                        '${meal['calories']} kcal',
                                                        style: const TextStyle(fontSize: 11, color: Colors.orange),
                                                      ),
                                                  ],
                                                ),
                                              ),
                                              if (meal != null)
                                                IconButton(
                                                  icon: const Icon(Icons.delete_outline, color: AppTheme.error, size: 20),
                                                  onPressed: () {
                                                    showDialog(
                                                      context: context,
                                                      builder: (context) => AlertDialog(
                                                        title: const Text('Delete Meal Plan'),
                                                        content: const Text('Delete this meal entry?'),
                                                        actions: [
                                                          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
                                                          TextButton(
                                                            onPressed: () {
                                                              Navigator.pop(context);
                                                              _deleteMeal(meal['id']);
                                                            },
                                                            child: const Text('Delete', style: TextStyle(color: AppTheme.error)),
                                                          ),
                                                        ],
                                                      ),
                                                    );
                                                  },
                                                )
                                              else
                                                const Icon(Icons.add, color: AppTheme.primaryContainer, size: 20),
                                            ],
                                          ),
                                        ),
                                      );
                                    }).toList(),
                                  ],
                                ),
                              ),
                            );
                          },
                        ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildGroceryListTab() {
    final weekStartStr = DateFormat('dd MMM').format(_groceryWeekStart);
    final weekEndStr = DateFormat('dd MMM yyyy').format(_groceryWeekStart.add(const Duration(days: 6)));

    // Group items by category
    final Map<String, List<dynamic>> grouped = {};
    for (var item in _groceryItems) {
      final category = item['category'] ?? 'Umum';
      grouped.putIfAbsent(category, () => []).add(item);
    }

    return Scaffold(
      body: RefreshIndicator(
        onRefresh: _fetchGroceryItems,
        color: AppTheme.primaryContainer,
        child: Column(
          children: [
            // Week navigation header
            Padding(
              padding: const EdgeInsets.all(12.0),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  IconButton(
                    icon: const Icon(Icons.chevron_left),
                    onPressed: () => _changeGroceryWeek(-7),
                  ),
                  Text(
                    '$weekStartStr - $weekEndStr',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                  IconButton(
                    icon: const Icon(Icons.chevron_right),
                    onPressed: () => _changeGroceryWeek(7),
                  ),
                ],
              ),
            ),

            // Actions row
            Padding(
              padding: const EdgeInsets.only(left: 16.0, right: 16.0, bottom: 8.0),
              child: Row(
                children: [
                  Expanded(
                    child: ElevatedButton.icon(
                      icon: const Icon(Icons.sync_alt, size: 18),
                      label: const Text('Generate from Meals'),
                      onPressed: _generateGroceryList,
                    ),
                  ),
                  const SizedBox(width: 8),
                  OutlinedButton.icon(
                    icon: const Icon(Icons.delete_sweep, color: AppTheme.error, size: 18),
                    label: const Text('Reset', style: TextStyle(color: AppTheme.error)),
                    style: OutlinedButton.styleFrom(side: const BorderSide(color: AppTheme.error)),
                    onPressed: () {
                      showDialog(
                        context: context,
                        builder: (context) => AlertDialog(
                          title: const Text('Reset Grocery List'),
                          content: const Text('Clear all items for this week?'),
                          actions: [
                            TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
                            TextButton(
                              onPressed: () {
                                Navigator.pop(context);
                                _resetGroceryList();
                              },
                              child: const Text('Reset', style: TextStyle(color: AppTheme.error)),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),

            Expanded(
              child: _isLoadingGrocery
                  ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryContainer))
                  : _groceryError != null
                      ? Center(child: Text(_groceryError!, style: const TextStyle(color: AppTheme.error)))
                      : _groceryItems.isEmpty
                          ? const Center(child: Text('No items in grocery list.'))
                          : ListView.builder(
                              padding: const EdgeInsets.all(16.0),
                              itemCount: grouped.keys.length,
                              itemBuilder: (context, catIndex) {
                                final category = grouped.keys.elementAt(catIndex);
                                final items = grouped[category]!;

                                return Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Padding(
                                      padding: const EdgeInsets.symmetric(vertical: 8.0),
                                      child: Text(
                                        category.toUpperCase(),
                                        style: const TextStyle(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 13,
                                          letterSpacing: 1.0,
                                          color: AppTheme.secondary,
                                        ),
                                      ),
                                    ),
                                    ...items.map((item) {
                                      final isChecked = item['is_checked'] ?? false;

                                      return Card(
                                        margin: const EdgeInsets.only(bottom: 8.0),
                                        child: ListTile(
                                          leading: Checkbox(
                                            value: isChecked,
                                            activeColor: AppTheme.primaryContainer,
                                            onChanged: (val) {
                                              if (val != null) {
                                                _toggleGroceryItem(item['id'], val);
                                              }
                                            },
                                          ),
                                          title: Text(
                                            item['item_name'],
                                            style: TextStyle(
                                              decoration: isChecked ? TextDecoration.lineThrough : null,
                                              color: isChecked ? AppTheme.onSurfaceVariant.withOpacity(0.5) : AppTheme.onBackground,
                                            ),
                                          ),
                                          subtitle: (item['qty'] != null)
                                              ? Text(
                                                  '${item['qty']} ${item['unit'] ?? ''}',
                                                  style: TextStyle(
                                                    decoration: isChecked ? TextDecoration.lineThrough : null,
                                                    color: AppTheme.onSurfaceVariant,
                                                    fontSize: 12,
                                                  ),
                                                )
                                              : null,
                                          trailing: Row(
                                            mainAxisSize: MainAxisSize.min,
                                            children: [
                                              IconButton(
                                                icon: const Icon(Icons.edit_outlined, size: 20),
                                                onPressed: () => _showGroceryFormSheet(item),
                                              ),
                                              IconButton(
                                                icon: const Icon(Icons.delete_outline, color: AppTheme.error, size: 20),
                                                onPressed: () => _deleteGroceryItem(item['id']),
                                              ),
                                            ],
                                          ),
                                        ),
                                      );
                                    }).toList(),
                                    const SizedBox(height: 12),
                                  ],
                                );
                              },
                            ),
            ),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton(
        heroTag: 'health_add_grocery_fab',
        onPressed: () => _showGroceryFormSheet(null),
        backgroundColor: AppTheme.primaryContainer,
        foregroundColor: Colors.black,
        child: const Icon(Icons.add),
      ),
    );
  }

  void _changeMealWeek(int offsetDays) {
    setState(() {
      _currentWeekStart = _currentWeekStart.add(Duration(days: offsetDays));
      _fetchMeals();
    });
  }

  void _changeGroceryWeek(int offsetDays) {
    setState(() {
      _groceryWeekStart = _groceryWeekStart.add(Duration(days: offsetDays));
      _fetchGroceryItems();
    });
  }

  void _showMealFormSheet(DateTime day, String mealTime, Map<String, dynamic>? meal) {
    final nameController = TextEditingController(text: meal != null ? meal['meal_name'] : '');
    final caloriesController = TextEditingController(text: meal != null && meal['calories'] != null ? meal['calories'].toString() : '');
    final notesController = TextEditingController(text: meal != null && meal['recipe_notes'] != null ? meal['recipe_notes'] : '');

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppTheme.background,
      builder: (context) {
        return Padding(
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom,
            left: 24,
            right: 24,
            top: 24,
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  meal != null ? 'Edit Meal Plan' : 'Add Meal Plan',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                Text(
                  '${DateFormat('EEEE, dd MMMM').format(day)} - ${mealTime.toUpperCase()}',
                  style: const TextStyle(fontSize: 12, color: AppTheme.primary),
                ),
                const SizedBox(height: 20),
                TextFormField(
                  controller: nameController,
                  decoration: const InputDecoration(
                    labelText: 'Meal Name',
                    prefixIcon: Icon(Icons.restaurant),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: caloriesController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Calories (kcal) - Optional',
                    prefixIcon: Icon(Icons.local_fire_department),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: notesController,
                  maxLines: 3,
                  decoration: const InputDecoration(
                    labelText: 'Recipe / Notes - Optional',
                    prefixIcon: Icon(Icons.edit_note),
                  ),
                ),
                const SizedBox(height: 24),
                ElevatedButton(
                  onPressed: () {
                    if (nameController.text.trim().isEmpty) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Meal name is required'), backgroundColor: AppTheme.error),
                      );
                      return;
                    }
                    Navigator.pop(context);
                    _saveMeal(
                      DateFormat('yyyy-MM-dd').format(day),
                      mealTime,
                      nameController.text.trim(),
                      int.tryParse(caloriesController.text.trim()),
                      notesController.text.trim().isNotEmpty ? notesController.text.trim() : null,
                    );
                  },
                  child: const Text('Save'),
                ),
                const SizedBox(height: 24),
              ],
            ),
          ),
        );
      },
    );
  }

  void _showGroceryFormSheet(Map<String, dynamic>? item) {
    final nameController = TextEditingController(text: item != null ? item['item_name'] : '');
    final qtyController = TextEditingController(text: item != null && item['qty'] != null ? item['qty'].toString() : '');
    final unitController = TextEditingController(text: item != null && item['unit'] != null ? item['unit'] : '');
    final categoryController = TextEditingController(text: item != null && item['category'] != null ? item['category'] : '');

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppTheme.background,
      builder: (context) {
        return Padding(
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom,
            left: 24,
            right: 24,
            top: 24,
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  item != null ? 'Edit Grocery Item' : 'Add Grocery Item',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 20),
                TextFormField(
                  controller: nameController,
                  decoration: const InputDecoration(
                    labelText: 'Item Name',
                    prefixIcon: Icon(Icons.shopping_cart),
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      flex: 2,
                      child: TextFormField(
                        controller: qtyController,
                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        decoration: const InputDecoration(
                          labelText: 'Qty',
                          prefixIcon: Icon(Icons.production_quantity_limits),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      flex: 3,
                      child: TextFormField(
                        controller: unitController,
                        decoration: const InputDecoration(
                          labelText: 'Unit (e.g. kg, pcs)',
                          prefixIcon: Icon(Icons.ad_units),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: categoryController,
                  decoration: const InputDecoration(
                    labelText: 'Category (e.g. Sayur, Daging)',
                    prefixIcon: Icon(Icons.category),
                  ),
                ),
                const SizedBox(height: 24),
                ElevatedButton(
                  onPressed: () async {
                    if (nameController.text.trim().isEmpty) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Item name is required'), backgroundColor: AppTheme.error),
                      );
                      return;
                    }
                    Navigator.pop(context);

                    final name = nameController.text.trim();
                    final qty = double.tryParse(qtyController.text.trim());
                    final unit = unitController.text.trim().isNotEmpty ? unitController.text.trim() : null;
                    final category = categoryController.text.trim().isNotEmpty ? categoryController.text.trim() : null;

                    if (item != null) {
                      // Update via PUT request
                      try {
                        final response = await _apiService.put('/grocery-lists/${item['id']}', {
                          'item_name': name,
                          'qty': qty,
                          'unit': unit,
                          'category': category,
                        });
                        if (response['success'] == true) {
                          _fetchGroceryItems();
                        }
                      } catch (e) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(content: Text('Failed to update item: $e'), backgroundColor: AppTheme.error),
                        );
                      }
                    } else {
                      // Add new item
                      _addGroceryItem(name, qty, unit, category);
                    }
                  },
                  child: const Text('Save'),
                ),
                const SizedBox(height: 24),
              ],
            ),
          ),
        );
      },
    );
  }
}
