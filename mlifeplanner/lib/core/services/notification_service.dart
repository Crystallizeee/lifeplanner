import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:timezone/timezone.dart' as tz;
import 'package:timezone/data/latest_all.dart' as tz_data;

class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  final FlutterLocalNotificationsPlugin _plugin = FlutterLocalNotificationsPlugin();
  bool _initialized = false;
  static const String _notificationsEnabledKey = 'notifications_enabled';
  static const String _habitReminderTimeKey = 'habit_reminder_time';

  /// Initialize the notification plugin.
  Future<void> init() async {
    if (_initialized) return;

    tz_data.initializeTimeZones();

    const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
    const initSettings = InitializationSettings(android: androidSettings);

    await _plugin.initialize(initSettings);
    _initialized = true;
  }

  /// Check if notifications are enabled.
  Future<bool> isNotificationsEnabled() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(_notificationsEnabledKey) ?? false;
  }

  /// Toggle notifications on/off.
  Future<void> setNotificationsEnabled(bool enabled) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_notificationsEnabledKey, enabled);

    if (!enabled) {
      await cancelAllNotifications();
    }
  }

  /// Get the saved reminder time (default 08:00).
  Future<TimeOfDay> getReminderTime() async {
    final prefs = await SharedPreferences.getInstance();
    final hour = prefs.getInt('${_habitReminderTimeKey}_hour') ?? 8;
    final minute = prefs.getInt('${_habitReminderTimeKey}_minute') ?? 0;
    return TimeOfDay(hour: hour, minute: minute);
  }

  /// Save the reminder time.
  Future<void> setReminderTime(TimeOfDay time) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt('${_habitReminderTimeKey}_hour', time.hour);
    await prefs.setInt('${_habitReminderTimeKey}_minute', time.minute);
  }

  /// Schedule a daily habit reminder notification.
  Future<void> scheduleDailyHabitReminder({
    int id = 100,
    String title = '📋 Daily Habit Check-In',
    String body = 'Don\'t forget to check your habits and tasks for today!',
    required TimeOfDay time,
  }) async {
    await init();

    final now = tz.TZDateTime.now(tz.local);
    var scheduledDate = tz.TZDateTime(
      tz.local,
      now.year,
      now.month,
      now.day,
      time.hour,
      time.minute,
    );

    // If the time already passed today, schedule for tomorrow
    if (scheduledDate.isBefore(now)) {
      scheduledDate = scheduledDate.add(const Duration(days: 1));
    }

    await _plugin.zonedSchedule(
      id,
      title,
      body,
      scheduledDate,
      const NotificationDetails(
        android: AndroidNotificationDetails(
          'habit_reminder',
          'Habit Reminders',
          channelDescription: 'Daily reminders for habit tracking',
          importance: Importance.high,
          priority: Priority.high,
          icon: '@mipmap/ic_launcher',
        ),
      ),
      androidScheduleMode: AndroidScheduleMode.inexactAllowWhileIdle,
      matchDateTimeComponents: DateTimeComponents.time, // Repeat daily
    );
  }

  /// Schedule a task due date notification.
  Future<void> scheduleTaskReminder({
    required int taskId,
    required String taskTitle,
    required DateTime dueDate,
  }) async {
    await init();

    final scheduledDate = tz.TZDateTime.from(dueDate, tz.local).subtract(const Duration(hours: 1));

    if (scheduledDate.isBefore(tz.TZDateTime.now(tz.local))) return;

    await _plugin.zonedSchedule(
      taskId + 1000, // Offset to avoid ID collision with habit reminders
      '⏰ Task Due Soon',
      'Task "$taskTitle" is due in 1 hour!',
      scheduledDate,
      const NotificationDetails(
        android: AndroidNotificationDetails(
          'task_reminder',
          'Task Reminders',
          channelDescription: 'Reminders for upcoming task deadlines',
          importance: Importance.high,
          priority: Priority.high,
          icon: '@mipmap/ic_launcher',
        ),
      ),
      androidScheduleMode: AndroidScheduleMode.inexactAllowWhileIdle,
    );
  }

  /// Show an instant notification.
  Future<void> showInstantNotification({
    int id = 0,
    required String title,
    required String body,
  }) async {
    await init();

    await _plugin.show(
      id,
      title,
      body,
      const NotificationDetails(
        android: AndroidNotificationDetails(
          'general',
          'General',
          channelDescription: 'General notifications',
          importance: Importance.defaultImportance,
          priority: Priority.defaultPriority,
          icon: '@mipmap/ic_launcher',
        ),
      ),
    );
  }

  /// Cancel all scheduled notifications.
  Future<void> cancelAllNotifications() async {
    await init();
    await _plugin.cancelAll();
  }

  /// Cancel a specific notification.
  Future<void> cancelNotification(int id) async {
    await init();
    await _plugin.cancel(id);
  }
}
