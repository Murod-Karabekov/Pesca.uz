import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../config/api_config.dart';
import '../services/auth_service.dart';
import '../services/web_session_cookies.dart';
import '../theme/app_theme.dart';
import 'login_screen.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context).textTheme;
    final auth = AuthService.instance;
    final loggedIn = auth.isLoggedIn;

    return SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(24, 20, 24, 24),
        children: [
          Text('Profil', style: t.headlineSmall),
          const SizedBox(height: 8),
          Text(
            loggedIn
                ? 'Tahlillar akkauntingizga yoziladi va tarixda ko‘rinadi.'
                : 'Kirmasangiz ham tahlil qilish mumkin; natija faqat qurilmada.',
            style: t.bodyMedium,
          ),
          if (kDebugMode) ...[
            const SizedBox(height: 10),
            SelectableText(
              'API: ${ApiConfig.baseUrl}\nKalit: ${ApiConfig.apiKey.length > 6 ? '${ApiConfig.apiKey.substring(0, 6)}…' : ApiConfig.apiKey}',
              style: t.bodySmall?.copyWith(fontSize: 11, color: AppTheme.inkMuted),
            ),
          ],
          const SizedBox(height: 24),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 28,
                    backgroundColor: AppTheme.ink.withValues(alpha: 0.08),
                    child: Icon(
                      Icons.person_rounded,
                      color: AppTheme.ink.withValues(alpha: 0.5),
                      size: 28,
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          loggedIn ? (auth.fullName ?? 'Foydalanuvchi') : 'Mehmon',
                          style: t.titleMedium,
                        ),
                        const SizedBox(height: 4),
                        Text(
                          loggedIn ? (auth.phone ?? '') : 'Kirish — ixtiyoriy',
                          style: t.bodySmall,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          if (!loggedIn)
            FilledButton.icon(
              onPressed: () async {
                final ok = await Navigator.of(context).push<bool>(
                  MaterialPageRoute(builder: (_) => const LoginScreen()),
                );
                if (ok == true && mounted) setState(() {});
              },
              icon: const Icon(Icons.login_rounded),
              label: const Text('Kirish'),
            )
          else
            OutlinedButton.icon(
              onPressed: () async {
                await AuthService.instance.logout();
                await WebSessionCookies.clearAll();
                if (mounted) setState(() {});
              },
              icon: const Icon(Icons.logout_rounded),
              label: const Text('Chiqish'),
            ),
          const SizedBox(height: 16),
          _ProfileTile(
            icon: Icons.language_rounded,
            title: 'Til',
            subtitle: 'O‘zbek / Русский',
            onTap: () {},
          ),
          _ProfileTile(
            icon: Icons.privacy_tip_outlined,
            title: 'Maxfiylik',
            subtitle: 'Tez orada',
            onTap: () {},
          ),
          _ProfileTile(
            icon: Icons.storefront_outlined,
            title: 'Do‘kon',
            subtitle: ApiConfig.shopUrl,
            onTap: _openShop,
          ),
          const SizedBox(height: 24),
          Text(
            'Pesca SmartStyle · 1.0.0',
            textAlign: TextAlign.center,
            style: t.bodySmall?.copyWith(fontSize: 12),
          ),
        ],
      ),
    );
  }

  Future<void> _openShop() async {
    final uri = Uri.parse(ApiConfig.shopUrl);
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Brauzer ochilmadi')),
      );
    }
  }
}

class _ProfileTile extends StatelessWidget {
  const _ProfileTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context).textTheme;

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(20),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: AppTheme.hairline),
            ),
            child: Row(
              children: [
                Icon(icon, size: 22, color: AppTheme.inkMuted),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(title, style: t.titleMedium?.copyWith(fontSize: 16)),
                      Text(subtitle, style: t.bodySmall),
                    ],
                  ),
                ),
                Icon(
                  Icons.chevron_right_rounded,
                  color: AppTheme.inkMuted.withValues(alpha: 0.5),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
