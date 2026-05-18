import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'dart:io' show Platform;

class ApiService {
  // Helper to determine the API base URL based on the platform.
  // Using 10.0.2.2 for Android emulator to access localhost of the host machine.
  static String get baseUrl {
    // Production API Base URL
    return 'https://life.cyrstallize.my.id/api';

    // Local Development Fallbacks (Uncomment if working locally)
    /*
    if (kIsWeb) {
      return 'http://127.0.0.1:8000/api';
    }
    if (Platform.isAndroid) {
      return 'http://10.0.2.2:8000/api';
    }
    return 'http://127.0.0.1:8000/api';
    */
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
    final headers = await _getHeaders();
    
    final response = await http.get(url, headers: headers);
    return _handleResponse(response);
  }

  // POST Request
  Future<dynamic> post(String endpoint, Map<String, dynamic> data) async {
    final url = Uri.parse('$baseUrl$endpoint');
    final headers = await _getHeaders();
    
    final response = await http.post(
      url, 
      headers: headers,
      body: json.encode(data),
    );
    return _handleResponse(response);
  }

  // PUT Request
  Future<dynamic> put(String endpoint, Map<String, dynamic> data) async {
    final url = Uri.parse('$baseUrl$endpoint');
    final headers = await _getHeaders();
    
    final response = await http.put(
      url, 
      headers: headers,
      body: json.encode(data),
    );
    return _handleResponse(response);
  }

  // DELETE Request
  Future<dynamic> delete(String endpoint) async {
    final url = Uri.parse('$baseUrl$endpoint');
    final headers = await _getHeaders();
    
    final response = await http.delete(url, headers: headers);
    return _handleResponse(response);
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
