import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../models/history_entry.dart';
import '../services/auth_service.dart';
import '../services/web_session_cookies.dart';
import '../services/smart_style_history_api.dart';
import '../theme/app_theme.dart';
import 'login_screen.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  late Future<List<HistoryEntry>> _future;

  @override
  void initState() {
    super.initState();
    AuthService.sessionRevision.addListener(_onSessionChanged);
    _future = _load();
  }

  @override
  void dispose() {
    AuthService.sessionRevision.removeListener(_onSessionChanged);
    super.dispose();
  }

  void _onSessionChanged() {
    if (!mounted) return;
    setState(() {
      _future = _load();
    });
  }

  Future<List<HistoryEntry>> _load() async {
    if (!AuthService.instance.isLoggedIn) {
      return [];
    }
    return SmartStyleHistoryApi().fetch();
  }

  Future<void> _refresh() async {
    setState(() {
      _future = _load();
    });
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context).textTheme;
    final loggedIn = AuthService.instance.isLoggedIn;

    if (!loggedIn) {
      return SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text('Tarix', style: t.headlineSmall),
              const SizedBox(height: 12),
              Text(
                'Oldingi tahlillar shu yerda. Buning uchun akkauntga kiring.',
                style: t.bodyMedium,
              ),
              const SizedBox(height: 24),
              FilledButton(
                onPressed: () async {
                  await Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => const LoginScreen()),
                  );
                  if (mounted) setState(() {});
                },
                child: const Text('Kirish'),
              ),
            ],
          ),
        ),
      );
    }

    final dateFmt = DateFormat('dd.MM.yyyy HH:mm');

    return SafeArea(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 8, 8),
            child: Row(
              children: [
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.only(left: 8),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Tarix', style: t.headlineSmall),
                        const SizedBox(height: 6),
                        Text(
                          'Serverda saqlangan SmartStyle tahlillaringiz.',
                          style: t.bodyMedium,
                        ),
                      ],
                    ),
                  ),
                ),
                IconButton(
                  tooltip: 'Chiqish',
                  onPressed: () async {
                    await AuthService.instance.logout();
                    await WebSessionCookies.clearAll();
                    if (mounted) setState(() {});
                  },
                  icon: const Icon(Icons.logout_rounded),
                ),
              ],
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _refresh,
              child: FutureBuilder<List<HistoryEntry>>(
                future: _future,
                builder: (context, snapshot) {
                  if (snapshot.connectionState == ConnectionState.waiting) {
                    return const Center(child: CircularProgressIndicator());
                  }
                  if (snapshot.hasError) {
                    return ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.all(24),
                      children: [
                        Text(
                          snapshot.error.toString(),
                          style: t.bodyMedium,
                        ),
                      ],
                    );
                  }
                  final items = snapshot.data ?? [];
                  if (items.isEmpty) {
                    return ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.all(24),
                      children: [
                        Icon(
                          Icons.auto_awesome_outlined,
                          size: 48,
                          color: AppTheme.ink.withValues(alpha: 0.2),
                        ),
                        const SizedBox(height: 16),
                        Text('Hozircha yozuv yo‘q', style: t.titleMedium),
                        const SizedBox(height: 8),
                        Text(
                          '«Tahlil qilish (server)» ni kirgan akkaunt bilan bajaring.',
                          style: t.bodySmall,
                        ),
                      ],
                    );
                  }
                  return ListView.separated(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(24, 0, 24, 24),
                    itemCount: items.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 12),
                    itemBuilder: (context, i) {
                      final h = items[i];
                      final dt = DateTime.tryParse(h.createdAtIso)?.toLocal();
                      final dateStr = dt != null ? dateFmt.format(dt) : h.createdAtIso;
                      final recs = h.recommendations;
                      final preview = recs
                          .take(3)
                          .map((r) => r['name']?.toString() ?? '')
                          .where((s) => s.isNotEmpty)
                          .join(', ');

                      return Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(dateStr, style: t.titleSmall),
                              const SizedBox(height: 6),
                              Text(
                                preview.isEmpty ? 'Tavsiyalar yo‘q' : preview,
                                style: t.bodySmall,
                              ),
                              if (h.photoFilename != null) ...[
                                const SizedBox(height: 6),
                                Text(
                                  'Rasm: ${h.photoFilename}',
                                  style: t.bodySmall?.copyWith(fontSize: 11),
                                ),
                              ],
                            ],
                          ),
                        ),
                      );
                    },
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}
