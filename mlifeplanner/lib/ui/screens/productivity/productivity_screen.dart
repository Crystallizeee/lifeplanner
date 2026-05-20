import 'package:flutter/material.dart';
import '../../../core/services/api_service.dart';
import '../../../core/theme/app_theme.dart';
import 'package:intl/intl.dart';
import 'kanban_board_view.dart';

class ProductivityScreen extends StatefulWidget {
  const ProductivityScreen({super.key});

  @override
  State<ProductivityScreen> createState() => _ProductivityScreenState();
}

class _ProductivityScreenState extends State<ProductivityScreen> {
  final ApiService _apiService = ApiService();
  bool _isLoading = true;
  List<dynamic> _todos = [];
  String? _errorMessage;
  bool _isKanbanView = true; // Default to the awesome new Kanban Board!

  @override
  void initState() {
    super.initState();
    _fetchTodos();
  }

  Future<void> _fetchTodos() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final response = await _apiService.get('/todos');
      if (response['success'] == true) {
        setState(() {
          _todos = response['data'];
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

  Future<void> _toggleTodo(int id) async {
    try {
      final response = await _apiService.post('/todos/$id/toggle', {});
      if (response['success'] == true) {
        setState(() {
          final index = _todos.indexWhere((t) => t['id'] == id);
          if (index != -1) {
            _todos[index] = response['data'];
          }
        });
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to update task: $e'), backgroundColor: AppTheme.error),
      );
    }
  }

  Future<void> _updateTodoStatus(int id, String status) async {
    try {
      final response = await _apiService.put('/todos/$id', {'status': status});
      if (response['success'] == true) {
        setState(() {
          final index = _todos.indexWhere((t) => t['id'] == id);
          if (index != -1) {
            _todos[index] = response['data'];
          }
        });
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to update status: $e'), backgroundColor: AppTheme.error),
      );
    }
  }

  Future<void> _deleteTodo(int id) async {
    try {
      final response = await _apiService.delete('/todos/$id');
      if (response['success'] == true) {
        setState(() {
          _todos.removeWhere((t) => t['id'] == id);
        });
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Task deleted'), backgroundColor: AppTheme.primaryContainer),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to delete task: $e'), backgroundColor: AppTheme.error),
      );
    }
  }

  void _showAddTaskSheet() {
    final titleController = TextEditingController();
    final descController = TextEditingController();
    String selectedPriority = 'medium';
    DateTime? selectedDueDate;

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
                    Text(
                      'Add New Task',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 20),
                    TextFormField(
                      controller: titleController,
                      decoration: const InputDecoration(
                        labelText: 'Task Title',
                        prefixIcon: Icon(Icons.title),
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: descController,
                      decoration: const InputDecoration(
                        labelText: 'Description (Optional)',
                        prefixIcon: Icon(Icons.description_outlined),
                      ),
                      maxLines: 2,
                    ),
                    const SizedBox(height: 16),
                    
                    // Priority Selector
                    DropdownButtonFormField<String>(
                      value: selectedPriority,
                      decoration: const InputDecoration(
                        labelText: 'Priority',
                        prefixIcon: Icon(Icons.flag_outlined),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'low', child: Text('🟢 Low Priority')),
                        DropdownMenuItem(value: 'medium', child: Text('🟡 Medium Priority')),
                        DropdownMenuItem(value: 'high', child: Text('🔴 High Priority')),
                      ],
                      onChanged: (val) {
                        if (val != null) {
                          setModalState(() => selectedPriority = val);
                        }
                      },
                    ),
                    const SizedBox(height: 16),
                    
                    // Due Date
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Due Date'),
                      subtitle: Text(selectedDueDate == null 
                          ? 'No due date set' 
                          : DateFormat('dd MMM yyyy').format(selectedDueDate!)),
                      leading: const Icon(Icons.calendar_today),
                      trailing: selectedDueDate != null 
                          ? IconButton(
                              icon: const Icon(Icons.clear, color: AppTheme.error),
                              onPressed: () => setModalState(() => selectedDueDate = null),
                            )
                          : null,
                      onTap: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: DateTime.now(),
                          firstDate: DateTime.now(),
                          lastDate: DateTime(2100),
                        );
                        if (picked != null) {
                          setModalState(() => selectedDueDate = picked);
                        }
                      },
                    ),
                    const SizedBox(height: 24),
                    
                    ElevatedButton(
                      onPressed: () async {
                        if (titleController.text.trim().isEmpty) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Title is required'), backgroundColor: AppTheme.error),
                          );
                          return;
                        }

                        Navigator.pop(context); // Close sheet
                        
                        setState(() => _isLoading = true);
                        try {
                          final response = await _apiService.post('/todos', {
                            'title': titleController.text.trim(),
                            'description': descController.text.trim().isNotEmpty ? descController.text.trim() : null,
                            'priority': selectedPriority,
                            'due_date': selectedDueDate != null 
                                ? DateFormat('yyyy-MM-dd').format(selectedDueDate!) 
                                : null,
                          });

                          if (response['success'] == true) {
                            _fetchTodos(); // Refresh list
                          }
                        } catch (e) {
                          setState(() => _isLoading = false);
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text('Failed to add task: $e'), backgroundColor: AppTheme.error),
                          );
                        }
                      },
                      child: const Text('Add Task'),
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
        title: const Text('Productivity Center'),
        actions: [
          IconButton(
            icon: Icon(_isKanbanView ? Icons.view_list_rounded : Icons.dashboard_customize_rounded),
            tooltip: _isKanbanView ? 'Switch to List View' : 'Switch to Kanban Board',
            onPressed: () {
              setState(() {
                _isKanbanView = !_isKanbanView;
              });
            },
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _fetchTodos,
          ),
        ],
      ),
      body: _buildBody(),
      floatingActionButton: FloatingActionButton(
        heroTag: 'productivity_add_task_fab',
        onPressed: _showAddTaskSheet,
        backgroundColor: AppTheme.primaryContainer,
        foregroundColor: Colors.black,
        child: const Icon(Icons.add_task),
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
              onPressed: _fetchTodos,
              child: const Text('Retry'),
            ),
          ],
        ),
      );
    }

    if (_todos.isEmpty) {
      return const Center(
        child: Text('No tasks created yet. Tap the button below to add one!'),
      );
    }

    if (_isKanbanView) {
      return RefreshIndicator(
        onRefresh: _fetchTodos,
        color: AppTheme.primaryContainer,
        child: KanbanBoardView(
          todos: _todos,
          onStatusChanged: _updateTodoStatus,
          onDelete: _deleteTodo,
          onToggleDone: _toggleTodo,
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchTodos,
      color: AppTheme.primaryContainer,
      child: ListView.builder(
        padding: const EdgeInsets.all(16.0),
        itemCount: _todos.length,
        itemBuilder: (context, index) {
          final todo = _todos[index];
          final isDone = todo['status'] == 'done';
          final priority = todo['priority'] ?? 'medium';
          
          Color priorityColor;
          if (priority == 'high') {
            priorityColor = AppTheme.error;
          } else if (priority == 'medium') {
            priorityColor = Colors.orange;
          } else {
            priorityColor = Colors.green;
          }

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            child: ListTile(
              leading: Checkbox(
                value: isDone,
                activeColor: AppTheme.primaryContainer,
                checkColor: Colors.black,
                onChanged: (_) => _toggleTodo(todo['id']),
              ),
              title: Text(
                todo['title'] ?? '',
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  fontSize: 16,
                  decoration: isDone ? TextDecoration.lineThrough : null,
                  color: isDone ? AppTheme.onSurfaceVariant : AppTheme.onBackground,
                ),
              ),
              subtitle: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (todo['description'] != null && todo['description'].toString().isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Text(
                        todo['description'],
                        style: const TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 13),
                      ),
                    ),
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      Container(
                        width: 10,
                        height: 10,
                        decoration: BoxDecoration(
                          color: priorityColor,
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(width: 6),
                      Text(
                        priority.toString().toUpperCase(),
                        style: TextStyle(color: priorityColor, fontSize: 11, fontWeight: FontWeight.bold),
                      ),
                      if (todo['due_date'] != null) ...[
                        const SizedBox(width: 16),
                        const Icon(Icons.calendar_today, size: 12, color: AppTheme.onSurfaceVariant),
                        const SizedBox(width: 4),
                        Text(
                          todo['due_date'],
                          style: const TextStyle(color: AppTheme.onSurfaceVariant, fontSize: 11),
                        ),
                      ],
                    ],
                  ),
                ],
              ),
              trailing: IconButton(
                icon: const Icon(Icons.delete_outline, color: AppTheme.error),
                onPressed: () {
                  showDialog(
                    context: context,
                    builder: (context) => AlertDialog(
                      title: const Text('Delete Task'),
                      content: const Text('Are you sure you want to delete this task?'),
                      actions: [
                        TextButton(
                          onPressed: () => Navigator.pop(context),
                          child: const Text('Cancel'),
                        ),
                        TextButton(
                          onPressed: () {
                            Navigator.pop(context);
                            _deleteTodo(todo['id']);
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
    );
  }
}
