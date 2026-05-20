import 'package:flutter/material.dart';
import '../core/services/api_service.dart';

class User {
  final int id;
  final String name;
  final String email;

  User({required this.id, required this.name, required this.email});

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] is String ? int.tryParse(json['id']) ?? 0 : json['id'],
      name: json['name'] ?? 'User',
      email: json['email'] ?? '',
    );
  }
}

class AuthProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();
  User? _user;
  bool _isLoading = false;
  bool _isAuthenticated = false;

  User? get user => _user;
  bool get isLoading => _isLoading;
  bool get isAuthenticated => _isAuthenticated;

  Future<void> checkAuthStatus() async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _apiService.get('/auth/me');
      if (response['success'] == true) {
        _user = User.fromJson(response['user']);
        _isAuthenticated = true;
      } else {
        _isAuthenticated = false;
      }
    } catch (e) {
      _isAuthenticated = false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<String?> login(String email, String password) async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _apiService.post('/auth/login', {
        'email': email,
        'password': password,
      });

      if (response['success'] == true) {
        final token = response['token'];
        await _apiService.setToken(token);
        _user = User.fromJson(response['user']);
        _isAuthenticated = true;
        
        _isLoading = false;
        notifyListeners();
        return null; // Null means success
      }
      _isLoading = false;
      notifyListeners();
      return response['message'] ?? 'Login failed';
    } catch (e) {
      _isLoading = false;
      notifyListeners();
      return e.toString().replaceAll('Exception: ', ''); // Return the exact error
    }
  }

  Future<void> logout() async {
    try {
      await _apiService.post('/auth/logout', {});
    } catch (e) {
      // Continue logout locally even if server request fails
    }
    
    await _apiService.clearToken();
    _user = null;
    _isAuthenticated = false;
    notifyListeners();
  }
}
