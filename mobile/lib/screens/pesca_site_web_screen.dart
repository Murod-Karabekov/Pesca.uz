import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

import '../config/api_config.dart';
import '../services/auth_service.dart';

/// Sayt sahifasi (Symfony sessiyasi) — mobil token orqali `/mobile/web-login` bridge.
class PescaSiteWebScreen extends StatefulWidget {
  const PescaSiteWebScreen({super.key, required this.targetPath});

  /// Masalan: `/profil`, `/cart`, `/smart-style/scan`
  final String targetPath;

  @override
  State<PescaSiteWebScreen> createState() => _PescaSiteWebScreenState();
}

class _PescaSiteWebScreenState extends State<PescaSiteWebScreen> {
  late final WebViewController _controller;
  int _progress = 0;

  static bool get _platformSupported {
    if (kIsWeb) return false;
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
      case TargetPlatform.iOS:
        return true;
      default:
        return false;
    }
  }

  @override
  void initState() {
    super.initState();
    AuthService.sessionRevision.addListener(_onSessionRevision);
    if (_platformSupported) {
      _controller = WebViewController()
        ..setJavaScriptMode(JavaScriptMode.unrestricted)
        ..setNavigationDelegate(
          NavigationDelegate(
            onProgress: (p) {
              if (mounted) setState(() => _progress = p);
            },
          ),
        )
        ..loadRequest(_entryUri());
    }
  }

  void _onSessionRevision() {
    if (!_platformSupported) return;
    _controller.loadRequest(_entryUri());
  }

  Uri _entryUri() {
    final base = Uri.parse(ApiConfig.baseUrl);
    final path = widget.targetPath.startsWith('/') ? widget.targetPath : '/${widget.targetPath}';
    final token = AuthService.instance.token;
    if (token != null && token.isNotEmpty) {
      return base.replace(
        path: '/mobile/web-login',
        queryParameters: <String, String>{
          'token': token,
          'target': path,
        },
      );
    }
    return base.replace(path: '/login');
  }

  @override
  void dispose() {
    AuthService.sessionRevision.removeListener(_onSessionRevision);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (!_platformSupported) {
      return SafeArea(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text(
              'Sayt ko‘rinishi hozircha faqat Android va iOS qurilmalarda ishlaydi.',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodyLarge,
            ),
          ),
        ),
      );
    }

    return Column(
      children: [
        if (_progress > 0 && _progress < 100)
          LinearProgressIndicator(value: _progress / 100, minHeight: 2),
        Expanded(child: WebViewWidget(controller: _controller)),
      ],
    );
  }
}
