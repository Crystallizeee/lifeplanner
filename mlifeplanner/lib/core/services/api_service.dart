import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'dart:io' show Platform, SocketException, HttpException;
import 'offline_cache_service.dart';

class ApiService {
  // Helper to determine the API base URL based on the platform.
  static String get baseUrl {
    return 'https://life.cyrstallize.my.id/api';
  }

  static const String _tokenKey = 'auth_token';

  // Helper method to get the headers, including Authorization if token exists
  Future<Map<String, String>> _getHeaders() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString(_tokenKey);
    
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  // GET Request
  Future<dynamic> get(String endpoint) async {
    final url = Uri.parse('$baseUrl$endpoint');
    
    try {
      final headers = await _getHeaders();
      final response = await http.get(url, headers: headers);
      final decoded = _handleResponse(response);
      
      // Cache successful response
      await OfflineCacheService().cacheData(endpoint, decoded);
      return decoded;
    } catch (e) {
      // Check if it's a connection failure
      if (e is SocketException || e is HttpException || e.toString().contains('Failed host lookup') || e.toString().contains('Connection failed')) {
        final cached = await OfflineCacheService().getCachedData(endpoint);
        if (cached != null) {
          return cached;
        }
      }
      rethrow;
    }
  }

  // POST Request
  Future<dynamic> post(String endpoint, Map<String, dynamic> data) async {
    final url = Uri.parse('$baseUrl$endpoint');
    
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        url, 
        headers: headers,
        body: json.encode(data),
      );
      
      final decoded = _handleResponse(response);
      // Trigger background sync when online operation succeeds
      OfflineCacheService().syncPendingMutations();
      return decoded;
    } catch (e) {
      if (e is SocketException || e is HttpException || e.toString().contains('Failed host lookup') || e.toString().contains('Connection failed')) {
        await OfflineCacheService().enqueueMutation(
          method: 'POST',
          endpoint: endpoint,
          body: data,
        );
        return {
          'success': true,
          'offline': true,
          'message': 'Saved offline. Will sync when connection is restored.',
          'data': data,
        };
      }
      rethrow;
    }
  }

  // PUT Request
  Future<dynamic> put(String endpoint, Map<String, dynamic> data) async {
    final url = Uri.parse('$baseUrl$endpoint');
    
    try {
      final headers = await _getHeaders();
      final response = await http.put(
        url, 
        headers: headers,
        body: json.encode(data),
      );
      
      final decoded = _handleResponse(response);
      OfflineCacheService().syncPendingMutations();
      return decoded;
    } catch (e) {
      if (e is SocketException || e is HttpException || e.toString().contains('Failed host lookup') || e.toString().contains('Connection failed')) {
        await OfflineCacheService().enqueueMutation(
          method: 'PUT',
          endpoint: endpoint,
          body: data,
        );
        return {
          'success': true,
          'offline': true,
          'message': 'Updated offline. Will sync when connection is restored.',
          'data': data,
        };
      }
      rethrow;
    }
  }

  // DELETE Request
  Future<dynamic> delete(String endpoint) async {
    final url = Uri.parse('$baseUrl$endpoint');
    
    try {
      final headers = await _getHeaders();
      final response = await http.delete(url, headers: headers);
      
      final decoded = _handleResponse(response);
      OfflineCacheService().syncPendingMutations();
      return decoded;
    } catch (e) {
      if (e is SocketException || e is HttpException || e.toString().contains('Failed host lookup') || e.toString().contains('Connection failed')) {
        await OfflineCacheService().enqueueMutation(
          method: 'DELETE',
          endpoint: endpoint,
        );
        return {
          'success': true,
          'offline': true,
          'message': 'Deleted offline. Will sync when connection is restored.',
        };
      }
      rethrow;
    }
  }

  // Set Auth Token
  Future<void> setToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  // Clear Auth Token
  Future<void> clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }

  // Response Handler
  dynamic _handleResponse(http.Response response) {
    if (response.statusCode >= 200 && response.statusCode < 300) {
      if (response.body.isNotEmpty) {
        return json.decode(response.body);
      }
      return null;
    } else if (response.statusCode == 401) {
      clearToken();
      throw Exception('Unauthorized');
    } else {
      final body = json.decode(response.body);
      final message = body['message'] ?? 'Unknown error occurred';
      throw Exception(message);
    }
  }
}
