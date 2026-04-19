import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../models/smart_style_analyze_result.dart';
import '../theme/app_theme.dart';
import '../widgets/xfile_image.dart';

class ResultsScreen extends StatelessWidget {
  const ResultsScreen({
    super.key,
    this.faceImage,
    this.apiResult,
    this.demoMode = false,
  });

  final XFile? faceImage;
  final SmartStyleAnalyzeResult? apiResult;
  final bool demoMode;

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context).textTheme;
    final fromApi = apiResult != null;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Tavsiyalar'),
      ),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(24, 8, 24, 24),
          children: [
            if (faceImage != null) ...[
              ClipRRect(
                borderRadius: BorderRadius.circular(20),
                child: AspectRatio(
                  aspectRatio: 4 / 3,
                  child: XFileImage(
                    file: faceImage!,
                    fit: BoxFit.cover,
                  ),
                ),
              ),
              const SizedBox(height: 16),
            ],
            Text(
              'Sizga mos uslublar',
              style: t.titleLarge,
            ),
            const SizedBox(height: 8),
            if (fromApi) ...[
              if (apiResult!.savedToAccount) ...[
                Card(
                  color: AppTheme.ink.withValues(alpha: 0.05),
                  child: Padding(
                    padding: const EdgeInsets.all(14),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(Icons.cloud_done_outlined, color: AppTheme.accent, size: 22),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            'Natija akkauntingizga saqlandi. «Tarix» bo‘limidan ko‘rishingiz mumkin.',
                            style: t.bodySmall?.copyWith(color: AppTheme.ink),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 12),
              ],
              if (apiResult!.usedAssumedProfile) ...[
                Card(
                  color: AppTheme.accent.withValues(alpha: 0.12),
                  child: Padding(
                    padding: const EdgeInsets.all(14),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(Icons.info_outline_rounded, color: AppTheme.accent, size: 22),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            apiResult!.message ??
                                'Profil maydonlari standart qiymat bilan to‘ldirildi. Aniqroq natija uchun keyinroq jins, teri rangi va yuz shaklini tanlang.',
                            style: t.bodySmall?.copyWith(color: AppTheme.ink),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 12),
              ],
              if (apiResult!.tips.isNotEmpty) ...[
                Text('Maslahatlar', style: t.titleMedium),
                const SizedBox(height: 8),
                ...apiResult!.tips.map(
                  (tip) => Padding(
                    padding: const EdgeInsets.only(bottom: 6),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('• ', style: t.bodyMedium),
                        Expanded(child: Text(tip, style: t.bodyMedium)),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
              ],
              Text('Mahsulotlar', style: t.titleMedium),
              const SizedBox(height: 8),
              if (apiResult!.recommendations.isEmpty)
                Text(
                  'Hozircha mos mahsulot topilmadi. Saytda yangi kolleksiyalar paydo bo‘lishi mumkin.',
                  style: t.bodyMedium,
                )
              else
                ...apiResult!.recommendations.map(
                  (p) => Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: _ProductCard(rec: p),
                  ),
                ),
            ] else if (demoMode) ...[
              Text(
                'Namuna ma’lumot — backendga ulanmagan rejim.',
                style: t.bodyMedium,
              ),
              const SizedBox(height: 20),
              const _DemoSuggestionTile(
                title: 'Klassik siluet',
                caption: 'To‘g‘ri chiziqli palto, past kontrast.',
              ),
              const SizedBox(height: 12),
              const _DemoSuggestionTile(
                title: 'Yumshoq minimal',
                caption: 'Neytral tonlar, sodda kesim.',
              ),
              const SizedBox(height: 12),
              const _DemoSuggestionTile(
                title: 'Accent detal',
                caption: 'Bitta kuchli aksent (belbog‘ yoki sumka).',
              ),
            ] else
              Text(
                'Natija yo‘q. Skan ekranidan qayta urinib ko‘ring.',
                style: t.bodyMedium,
              ),
          ],
        ),
      ),
    );
  }
}

class _ProductCard extends StatelessWidget {
  const _ProductCard({required this.rec});

  final ProductRecommendation rec;

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context).textTheme;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: SizedBox(
                width: 56,
                height: 72,
                child: rec.imageUrl != null && rec.imageUrl!.isNotEmpty
                    ? Image.network(
                        rec.imageUrl!,
                        fit: BoxFit.cover,
                        errorBuilder: (_, _, _) => ColoredBox(
                          color: AppTheme.hairline,
                          child: Icon(
                            Icons.checkroom_outlined,
                            color: AppTheme.ink.withValues(alpha: 0.35),
                          ),
                        ),
                      )
                    : ColoredBox(
                        color: AppTheme.hairline,
                        child: Icon(
                          Icons.checkroom_outlined,
                          color: AppTheme.ink.withValues(alpha: 0.35),
                        ),
                      ),
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(rec.name, style: t.titleMedium?.copyWith(fontSize: 16)),
                  const SizedBox(height: 4),
                  Text(
                    '${rec.price} ${rec.currency}',
                    style: t.bodySmall?.copyWith(fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 4),
                  Text(rec.scoreLabel, style: t.bodySmall),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DemoSuggestionTile extends StatelessWidget {
  const _DemoSuggestionTile({
    required this.title,
    required this.caption,
  });

  final String title;
  final String caption;

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context).textTheme;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Row(
          children: [
            Container(
              width: 52,
              height: 68,
              decoration: BoxDecoration(
                color: AppTheme.hairline,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(
                Icons.checkroom_outlined,
                color: AppTheme.ink.withValues(alpha: 0.35),
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: t.titleMedium),
                  const SizedBox(height: 4),
                  Text(caption, style: t.bodySmall),
                ],
              ),
            ),
            Icon(
              Icons.chevron_right_rounded,
              color: AppTheme.inkMuted.withValues(alpha: 0.6),
            ),
          ],
        ),
      ),
    );
  }
}
