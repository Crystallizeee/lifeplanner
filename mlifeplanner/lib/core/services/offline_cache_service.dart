import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';

class OfflineCacheService {
  static final OfflineCacheService _instance = OfflineCacheService._internal();
  factory OfflineCacheService() => _instance;
  OfflineCacheService._internal();

  static const String _syncQueueKey = 'offline_sync_queue';
  final ApiService _apiService = ApiService();
  bool _isSyncing = false;

  /// Cache GET response data
  Future<void> cacheData(String cacheKey, dynamic data) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('cache_$cacheKey', json.encode(data));
  }

  /// Read GET cached response data
  Future<dynamic> getCachedData(String cacheKey) async {
    final prefs = await SharedPreferences.getInstance();
    final cached = prefs.getString('cache_$cacheKey');
    if (cached != null) {
      try {
        return json.decode(cached);
      } catch (_) {
        return null;
      }
    }
    return null;
  }

  /// Add a pending write/delete operation to the queue
  Future<void> enqueueMutation({
    required String method,
    required String endpoint,
    Map<String, dynamic>? body,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    final queueJson = prefs.getString(_syncQueueKey);
    List<dynamic> queue = [];
    if (queueJson != null) {
      try {
        queue = json.decode(queueJson);
      } catch (_) {}
    }

    queue.add({
      'id': DateTime.now().millisecondsSinceEpoch.toString(),
      'method': method,
      'endpoint': endpoint,
      'body': body,
      'timestamp': DateTime.now().toIso8601String(),
    });

    await prefs.setString(_syncQueueKey, json.encode(queue));
  }

  /// Get count of pending operations in the sync queue
  Future<int> getPendingSyncCount() async {
    final prefs = await SharedPreferences.getInstance();
    final queueJson = prefs.getString(_syncQueueKey);
    if (queueJson == null) return 0;
    try {
      final List queue = json.decode(queueJson);
      return queue.length;
    } catch (_) {
      return 0;
    }
  }

  /// Run background sync to replay queued modifications to the server
  Future<bool> syncPendingMutations() async {
    if (_isSyncing) return false;
    _isSyncing = true;

    final prefs = await SharedPreferences.getInstance();
    final queueJson = prefs.getString(_syncQueueKey);
    if (queueJson == null) {
      _isSyncing = false;
      return false;
    }

    List<dynamic> queue = [];
    try {
      queue = json.decode(queueJson);
    } catch (_) {}

    if (queue.isEmpty) {
      _isSyncing = false;
      return false;
    }

    List<dynamic> remaining = [];
    bool success = true;

    for (var mutation in queue) {
      final method = mutation['method'] as String;
      final endpoint = mutation['endpoint'] as String;
      final body = mutation['body'] as Map<String, dynamic>?;

      try {
        if (method == 'POST') {
          await _apiService.post(endpoint, body ?? {});
        } else if (method == 'PUT') {
          await _apiService.put(endpoint, body ?? {});
        } else if (method == 'DELETE') {
          await _apiService.delete(endpoint);
        }
      } catch (e) {
        // If it's a validation error or page-not-found (400-499), skip to avoid infinite block
        if (e.toString().contains('Exception:') && !e.toString().contains('SocketException') && !e.toString().contains('Failed host')) {
          // Skip invalid requests
          continue;
        }
        
        // Otherwise (network issue), keep it in queue and stop processing
        remaining.add(mutation);
        success = false;
      }
    }

    // Save whatever couldn't be synced
    if (remaining.isEmpty) {
      await prefs.remove(_syncQueueKey);
    } else {
      await prefs.setString(_syncQueueKey, json.encode(remaining));
    }

    _isSyncing = false;
    return success;
  }
}
