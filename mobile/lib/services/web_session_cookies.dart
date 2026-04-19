import 'package:webview_flutter/webview_flutter.dart';

/// Veb-sessiya cookie’larini tozalash (mobil API dan chiqishda).
class WebSessionCookies {
  WebSessionCookies._();

  static final WebViewCookieManager _manager = WebViewCookieManager();

  static Future<void> clearAll() => _manager.clearCookies();
}
