import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import 'auth_service.dart';

class AuthApiException implements Exception {
  AuthApiException(this.message);
  final String message;

  @override
  String toString() => message;
}

class AuthApi {
  Future<void> login({required String phone, required String password}) async {
    final uri = Uri.parse('${ApiConfig.baseUrl}/api/auth/login');
    late http.Response response;
    try {
      response = await http
          .post(
            uri,
            headers: {'Content-Type': 'application/json; charset=utf-8'},
            body: jsonEncode({'phone': phone.trim(), 'password': password}),
          )
          .timeout(const Duration(seconds: 18));
    } on TimeoutException {
      throw AuthApiException(
        'Server javob bermadi. Docker va API manzilini (Profil, debug) tekshiring.',
      );
    }

    if (response.statusCode == 401) {
      throw AuthApiException('Telefon yoki parol noto‘g‘ri.');
    }
    if (response.statusCode != 200) {
      try {
        final m = jsonDecode(response.body) as Map<String, dynamic>;
        throw AuthApiException(m['error']?.toString() ?? 'Xato ${response.statusCode}');
      } catch (_) {
        throw AuthApiException('Server xato: ${response.statusCode}');
      }
    }

    final map = jsonDecode(response.body) as Map<String, dynamic>;
    if (map['success'] != true || map['token'] == null) {
      throw AuthApiException(map['error']?.toString() ?? 'Javob noto‘g‘ri');
    }

    final token = map['token'] as String;
    final user = map['user'] as Map<String, dynamic>;
    await AuthService.instance.saveSession(token, user);
  }
}
