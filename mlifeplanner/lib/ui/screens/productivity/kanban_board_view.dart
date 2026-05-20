import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../core/theme/app_theme.dart';

class KanbanBoardView extends StatelessWidget {
  final List<dynamic> todos;
  final Function(int id, String newStatus) onStatusChanged;
  final Function(int id) onDelete;
  final Function(int id) onToggleDone;

  const KanbanBoardView({
    super.key,
    required this.todos,
    required this.onStatusChanged,
    required this.onDelete,
    required this.onToggleDone,
  });

  static const List<Map<String, String>> _columns = [
    {'status': 'todo', 'title': 'To Do', 'icon': '📋', 'color': '0xFF90A4AE'},
    {'status': 'in_progress', 'title': 'In Progress', 'icon': '⚡', 'color': '0xFF29B6F6'},
    {'status': 'hold', 'title': 'On Hold', 'icon': '⏸️', 'color': '0xFFFFB74D'},
    {'status': 'done', 'title': 'Completed', 'icon': '✅', 'color': '0xFF66BB6A'},
  ];

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: _columns.map((col) {
          final status = col['status']!;
          final title = col['title']!;
          final icon = col['icon']!;
          final color = Color(int.parse(col['color']!));
          
          final columnTodos = todos.where((t) => t['status'] == status).toList();

          return Container(
            width: 290,
            margin: const EdgeInsets.only(right: 16.0),
            decoration: BoxDecoration(
              color: AppTheme.surface.withValues(alpha: 0.5),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppTheme.outline.withValues(alpha: 0.1)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Column Header
                Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Row(
                    children: [
                      Text(icon, style: const TextStyle(fontSize: 18)),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          title,
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                            color: color,
                          ),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: color.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          columnTodos.length.toString(),
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: color,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                
                // Drag Target Area
                Expanded(
                  child: DragTarget<Map<String, dynamic>>(
                    onWillAcceptWithDetails: (details) => details.data['status'] != status,
                    onAcceptWithDetails: (details) {
                      onStatusChanged(details.data['id'], status);
                    },
                    builder: (context, candidateData, rejectedData) {
                      final isOver = candidateData.isNotEmpty;
                      return Container(
                        decoration: BoxDecoration(
                          color: isOver ? color.withValues(alpha: 0.05) : Colors.transparent,
                          borderRadius: const BorderRadius.vertical(bottom: Radius.circular(16)),
                        ),
                        child: columnTodos.isEmpty
                            ? _buildEmptyState(context, isOver, color)
                            : ListView.builder(
                                shrinkWrap: true,
                                physics: const NeverScrollableScrollPhysics(),
                                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                                itemCount: columnTodos.length,
                                itemBuilder: (context, idx) {
                                  final todo = columnTodos[idx];
                                  return _KanbanCard(
                                    todo: todo,
                                    color: color,
                                    onDelete: onDelete,
                                    onToggleDone: onToggleDone,
                                  );
                                },
                              ),
                      );
                    },
                  ),
                ),
              ],
            ),
          );
        }).toList(),
      ),
    );
  }

  Widget _buildEmptyState(BuildContext context, bool isOver, Color color) {
    return Container(
      height: 180,
      alignment: Alignment.center,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            isOver ? Icons.playlist_add : Icons.drag_indicator_rounded,
            color: isOver ? color : AppTheme.onSurfaceVariant.withValues(alpha: 0.3),
            size: 28,
          ),
          const SizedBox(height: 8),
          Text(
            isOver ? 'Drop here!' : 'Drag tasks here',
            style: TextStyle(
              fontSize: 13,
              color: isOver ? color : AppTheme.onSurfaceVariant.withValues(alpha: 0.5),
            ),
          ),
        ],
      ),
    );
  }
}

class _KanbanCard extends StatelessWidget {
  final Map<String, dynamic> todo;
  final Color color;
  final Function(int id) onDelete;
  final Function(int id) onToggleDone;

  const _KanbanCard({
    required this.todo,
    required this.color,
    required this.onDelete,
    required this.onToggleDone,
  });

  @override
  Widget build(BuildContext context) {
    final id = todo['id'] as int;
    final title = todo['title'] ?? '';
    final notes = todo['description'] ?? '';
    final priority = todo['priority'] ?? 'medium';
    final isDone = todo['status'] == 'done';

    Color priorityColor;
    if (priority == 'high') {
      priorityColor = AppTheme.error;
    } else if (priority == 'medium') {
      priorityColor = Colors.orange;
    } else {
      priorityColor = Colors.green;
    }

    final cardContent = Card(
      elevation: 2,
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: color.withValues(alpha: 0.2), width: 1),
      ),
      child: Padding(
        padding: const EdgeInsets.all(12.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Text(
                    title,
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                      decoration: isDone ? TextDecoration.lineThrough : null,
                      color: isDone ? AppTheme.onSurfaceVariant : AppTheme.onBackground,
                    ),
                  ),
                ),
                // Small Priority indicator
                Container(
                  width: 8,
                  height: 8,
                  decoration: BoxDecoration(
                    color: priorityColor,
                    shape: BoxShape.circle,
                  ),
                ),
              ],
            ),
            if (notes.isNotEmpty) ...[
              const SizedBox(height: 6),
              Text(
                notes,
                style: const TextStyle(fontSize: 12, color: AppTheme.onSurfaceVariant),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],
            const SizedBox(height: 10),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                if (todo['due_date'] != null)
                  Row(
                    children: [
                      const Icon(Icons.calendar_today_outlined, size: 12, color: AppTheme.onSurfaceVariant),
                      const SizedBox(width: 4),
                      Text(
                        DateFormat('d MMM').format(DateTime.parse(todo['due_date'])),
                        style: const TextStyle(fontSize: 11, color: AppTheme.onSurfaceVariant),
                      ),
                    ],
                  )
                else
                  const SizedBox(),
                Row(
                  children: [
                    IconButton(
                      icon: const Icon(Icons.delete_outline, size: 16, color: AppTheme.error),
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(),
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
                              ElevatedButton(
                                onPressed: () {
                                  Navigator.pop(context);
                                  onDelete(id);
                                },
                                style: ElevatedButton.styleFrom(backgroundColor: AppTheme.error),
                                child: const Text('Delete', style: TextStyle(color: Colors.black)),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                  ],
                ),
              ],
            ),
          ],
        ),
      ),
    );

    return LongPressDraggable<Map<String, dynamic>>(
      data: {'id': id, 'status': todo['status']},
      feedback: Material(
        color: Colors.transparent,
        child: SizedBox(
          width: 260,
          child: cardContent,
        ),
      ),
      childWhenDragging: Opacity(
        opacity: 0.3,
        child: cardContent,
      ),
      child: cardContent,
    );
  }
}
