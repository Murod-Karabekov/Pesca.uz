import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import 'profile_screen.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key, this.onStartScan});

  final VoidCallback? onStartScan;

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context).textTheme;

    return SafeArea(
      child: CustomScrollView(
        physics: const BouncingScrollPhysics(),
        slivers: [
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(24, 28, 24, 12),
            sliver: SliverToBoxAdapter(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Pesca', style: t.labelLarge?.copyWith(color: AppTheme.accent)),
                  const SizedBox(height: 8),
                  Text(
                    'SmartStyle',
                    style: t.displayMedium?.copyWith(fontSize: 34),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    '«Style» — saytdagi kabi 6 qadam (jins → voqea → uslub → fasl → o‘lcham → surat). Savat, hamkorlik va profil veb ko‘rinishida.',
                    style: t.bodyMedium,
                  ),
                ],
              ),
            ),
          ),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(24, 28, 24, 8),
            sliver: SliverToBoxAdapter(
              child: FilledButton.icon(
                onPressed: onStartScan,
                icon: const Icon(Icons.center_focus_strong_rounded, size: 22),
                label: const Text('SmartStyle boshlash'),
              ),
            ),
          ),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(24, 0, 24, 12),
            sliver: SliverToBoxAdapter(
              child: OutlinedButton.icon(
                onPressed: () {
                  Navigator.of(context).push(
                    MaterialPageRoute<void>(builder: (_) => const ProfileScreen()),
                  );
                },
                icon: const Icon(Icons.badge_outlined, size: 20),
                label: const Text('Mobil akkaunt (API tarix)'),
              ),
            ),
          ),
          SliverPadding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            sliver: SliverToBoxAdapter(
              child: _StepCard(
                step: '1',
                title: 'Yuzni ramka ichiga oling',
                subtitle: 'Yaxshi yoritish va neytral ifoda yaxshiroq natija beradi.',
              ),
            ),
          ),
          const SliverToBoxAdapter(child: SizedBox(height: 12)),
          SliverPadding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            sliver: SliverToBoxAdapter(
              child: _StepCard(
                step: '2',
                title: 'Tahlil',
                subtitle: 'AI shakl va rang uyg‘unligini hisobga oladi.',
              ),
            ),
          ),
          const SliverToBoxAdapter(child: SizedBox(height: 12)),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(24, 0, 24, 32),
            sliver: SliverToBoxAdapter(
              child: _StepCard(
                step: '3',
                title: 'Mos kiyimlar',
                subtitle: 'Keyinchalik veb-sayt orqali buyurtma berishingiz mumkin.',
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StepCard extends StatelessWidget {
  const _StepCard({
    required this.step,
    required this.title,
    required this.subtitle,
  });

  final String step;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context).textTheme;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 40,
              height: 40,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: AppTheme.ink.withValues(alpha: 0.06),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                step,
                style: t.titleMedium?.copyWith(fontWeight: FontWeight.w700),
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: t.titleMedium),
                  const SizedBox(height: 6),
                  Text(subtitle, style: t.bodySmall),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
