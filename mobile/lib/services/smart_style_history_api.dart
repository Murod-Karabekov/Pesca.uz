import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import '../models/history_entry.dart';
import 'auth_service.dart';

class HistoryApiException implements Exception {
  HistoryApiException(this.message);
  final String message;

  @override
  String toString() => message;
}

class SmartStyleHistoryApi {
  Future<List<HistoryEntry>> fetch() async {
    final token = AuthService.instance.token;
    if (token == null || token.isEmpty) {
      throw HistoryApiException('Avval kirish qiling.');
    }

    final uri = Uri.parse('${ApiConfig.baseUrl}/api/smart-style/history');
    late http.Response response;
    try {
      response = await http.get(
        uri,
        headers: {
          'X-Pesca-Key': ApiConfig.apiKey,
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 20));
    } on TimeoutException {
      throw HistoryApiException(
        'Tarix yuklanmadi (vaqt tugadi). Tarmoq va API manzilini tekshiring.',
      );
    }

    if (response.statusCode == 401) {
      throw HistoryApiException('Sessiya tugagan. Qayta kiring.');
    }
    if (response.statusCode != 200) {
      throw HistoryApiException('Server: ${response.statusCode}');
    }

    final map = jsonDecode(response.body) as Map<String, dynamic>;
    final raw = map['items'] as List<dynamic>? ?? [];
    return raw.map((e) => HistoryEntry.fromJson(e as Map<String, dynamic>)).toList();
  }
}
