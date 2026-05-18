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

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _fetchHabits();
    _fetchWeightLogs();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
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
        title: const Text('Health & Habits'),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: AppTheme.primaryContainer,
          labelColor: AppTheme.primaryContainer,
          unselectedLabelColor: AppTheme.onBackground,
          tabs: const [
            Tab(icon: Icon(Icons.flash_on), text: 'Habits'),
            Tab(icon: Icon(Icons.monitor_weight), text: 'Weight Log'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildHabitsTab(),
          _buildWeightTab(),
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
        onPressed: _showAddWeightSheet,
        backgroundColor: AppTheme.primaryContainer,
        foregroundColor: Colors.black,
        child: const Icon(Icons.add),
      ),
    );
  }
}
