import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';

import '../config/api_config.dart';
import '../models/smart_style_analyze_result.dart';
import 'auth_service.dart';

class SmartStyleApiException implements Exception {
  SmartStyleApiException(this.message);
  final String message;

  @override
  String toString() => message;
}

/// Profil maydonlari — bo'sh qoldirilsa server standart (taxminiy) qiymat ishlatadi.
class SmartStyleProfilePayload {
  const SmartStyleProfilePayload({
    this.gender,
    this.skinTone,
    this.faceShape,
    this.occasion,
    this.styleIntent,
    this.season,
    this.heightCm,
    this.shoulderCm,
    this.chestCm,
    this.waistCm,
    this.hipCm,
  });

  final String? gender;
  final String? skinTone;
  final String? faceShape;
  final String? occasion;
  final String? styleIntent;
  final String? season;
  final int? heightCm;
  final int? shoulderCm;
  final int? chestCm;
  final int? waistCm;
  final int? hipCm;

  Map<String, String> toFields() {
    final m = <String, String>{};
    if (gender != null && gender!.isNotEmpty) {
      m['gender'] = gender!;
    }
    if (skinTone != null && skinTone!.isNotEmpty) {
      m['skinTone'] = skinTone!;
    }
    if (faceShape != null && faceShape!.isNotEmpty) {
      m['faceShape'] = faceShape!;
    }
    if (occasion != null && occasion!.isNotEmpty) {
      m['occasion'] = occasion!;
    }
    if (styleIntent != null && styleIntent!.isNotEmpty) {
      m['styleIntent'] = styleIntent!;
    }
    if (season != null && season!.isNotEmpty) {
      m['season'] = season!;
    }
    void putCm(String key, int? v) {
      if (v != null && v > 0) {
        m[key] = v.toString();
      }
    }

    putCm('heightCm', heightCm);
    putCm('shoulderCm', shoulderCm);
    putCm('chestCm', chestCm);
    putCm('waistCm', waistCm);
    putCm('hipCm', hipCm);
    return m;
  }
}

class SmartStyleApi {
  Future<SmartStyleAnalyzeResult> analyze({
    XFile? photo,
    SmartStyleProfilePayload? profile,
  }) async {
    final uri = Uri.parse('${ApiConfig.baseUrl}/api/smart-style/analyze');
    final request = http.MultipartRequest('POST', uri);
    request.headers['X-Pesca-Key'] = ApiConfig.apiKey;

    final bearer = AuthService.instance.token;
    if (bearer != null && bearer.isNotEmpty) {
      request.headers['Authorization'] = 'Bearer $bearer';
    }

    final fields = profile?.toFields() ?? {};
    fields.forEach((key, value) {
      request.fields[key] = value;
    });

    if (photo != null) {
      final bytes = await photo.readAsBytes();
      final name = photo.name.isEmpty ? 'face.jpg' : photo.name;
      request.files.add(
        http.MultipartFile.fromBytes('photo', bytes, filename: name),
      );
    }

    late http.StreamedResponse streamed;
    late http.Response response;
    try {
      streamed = await request.send().timeout(const Duration(seconds: 25));
      response = await http.Response.fromStream(streamed).timeout(const Duration(seconds: 35));
    } on TimeoutException {
      throw SmartStyleApiException(
        'Server javob bermadi (vaqt tugadi). Docker 8080 ishlayotganini, '
        'Profil dagi API manzilini tekshiring. Emulyatorda odatda 10.0.2.2:8080.',
      );
    }

    if (response.statusCode == 401) {
      throw SmartStyleApiException(
        'API kalit mos kelmayapti. PESCA_API_KEY ni tekshiring.',
      );
    }

    if (response.statusCode != 200) {
      try {
        final map = jsonDecode(response.body) as Map<String, dynamic>;
        final err = map['error']?.toString();
        throw SmartStyleApiException(err ?? 'Server xato: ${response.statusCode}');
      } on SmartStyleApiException {
        rethrow;
      } catch (_) {
        throw SmartStyleApiException('Server xato: ${response.statusCode}');
      }
    }

    final map = jsonDecode(response.body) as Map<String, dynamic>;
    if (map['success'] != true) {
      throw SmartStyleApiException(map['error']?.toString() ?? 'Noma’lum javob');
    }

    return SmartStyleAnalyzeResult.fromJson(map);
  }
}
