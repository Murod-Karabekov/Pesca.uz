import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Saqlangan mobil sessiya (Bearer token).
class AuthService {
  AuthService._();
  static final AuthService instance = AuthService._();

  static const _keyToken = 'pesca_mobile_token';
  static const _keyUser = 'pesca_mobile_user_json';

  /// Kirish/chiqishda o‘zgaradi — Tarix va boshqa ekranlar qayta yuklanishi uchun.
  static final ValueNotifier<int> sessionRevision = ValueNotifier(0);

  String? _token;
  Map<String, dynamic>? _user;

  String? get token => _token;
  bool get isLoggedIn => _token != null && _token!.isNotEmpty;
  String? get fullName => _user?['fullName'] as String?;
  String? get phone => _user?['phone'] as String?;

  Future<void> load() async {
    final p = await SharedPreferences.getInstance();
    _token = p.getString(_keyToken);
    final raw = p.getString(_keyUser);
    if (raw != null && raw.isNotEmpty) {
      try {
        _user = jsonDecode(raw) as Map<String, dynamic>;
      } catch (_) {
        _user = null;
      }
    } else {
      _user = null;
    }
  }

  Future<void> saveSession(String token, Map<String, dynamic> user) async {
    final p = await SharedPreferences.getInstance();
    await p.setString(_keyToken, token);
    await p.setString(_keyUser, jsonEncode(user));
    _token = token;
    _user = user;
    sessionRevision.value++;
  }

  Future<void> logout() async {
    final p = await SharedPreferences.getInstance();
    await p.remove(_keyToken);
    await p.remove(_keyUser);
    _token = null;
    _user = null;
    sessionRevision.value++;
  }
}
