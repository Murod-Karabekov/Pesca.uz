import 'package:flutter/foundation.dart'
    show TargetPlatform, defaultTargetPlatform, kIsWeb;

/// Symfony backend manzili va mobil API kaliti.
///
/// `--dart-define=PESCA_API_BASE=...` berilsa, u har doim ustun.
/// Aks holda:
/// - **Web** → `http://localhost:8080`
/// - **Android** → `http://10.0.2.2:8080` (emulyator host kompyuterga yo‘l)
/// - **Boshqa** → `http://127.0.0.1:8080`
///
/// Haqiqiy telefon: `flutter run --dart-define=PESCA_API_BASE=http://192.168.x.x:8080`
abstract final class ApiConfig {
  static String get baseUrl {
    const fromEnv = String.fromEnvironment('PESCA_API_BASE');
    if (fromEnv.isNotEmpty) {
      return fromEnv;
    }
    if (kIsWeb) {
      return 'http://localhost:8080';
    }
    if (defaultTargetPlatform == TargetPlatform.android) {
      return 'http://10.0.2.2:8080';
    }
    return 'http://127.0.0.1:8080';
  }

  static const String apiKey = String.fromEnvironment(
    'PESCA_API_KEY',
    defaultValue: 'dev_mobile_key_change_me',
  );

  static const String shopUrl = String.fromEnvironment(
    'PESCA_SHOP_URL',
    defaultValue: 'https://pesca.uz',
  );
}
