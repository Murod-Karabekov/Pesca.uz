import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../data/smart_style_profile_options.dart';
import '../services/smart_style_api.dart';
import '../theme/app_theme.dart';
import '../widgets/xfile_image.dart';
import 'results_screen.dart';

class ScanScreen extends StatefulWidget {
  const ScanScreen({super.key});

  @override
  State<ScanScreen> createState() => _ScanScreenState();
}

class _ScanScreenState extends State<ScanScreen> {
  final ImagePicker _picker = ImagePicker();
  final SmartStyleApi _api = SmartStyleApi();
  XFile? _face;
  bool _loading = false;

  String _gender = 'female';
  String _skinTone = 'warm_medium';
  String _faceShape = 'oval';
  String _occasion = '';
  String _season = '';
  String _styleIntent = '';

  Future<void> _pick(ImageSource source) async {
    try {
      final file = await _picker.pickImage(
        source: source,
        maxWidth: 1600,
        maxHeight: 1600,
        imageQuality: 88,
      );
      if (!mounted) return;
      if (file != null) {
        setState(() => _face = file);
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Rasm tanlanmadi: $e'),
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  SmartStyleProfilePayload get _profilePayload => SmartStyleProfilePayload(
        gender: _gender,
        skinTone: _skinTone,
        faceShape: _faceShape,
        occasion: _occasion.isEmpty ? null : _occasion,
        season: _season.isEmpty ? null : _season,
        styleIntent: _styleIntent.isEmpty ? null : _styleIntent,
      );

  Future<void> _runAnalyze() async {
    setState(() => _loading = true);
    try {
      final result = await _api.analyze(
        photo: _face,
        profile: _profilePayload,
      );
      if (!mounted) return;
      await Navigator.of(context).push<void>(
        MaterialPageRoute<void>(
          builder: (_) => ResultsScreen(
            faceImage: _face,
            apiResult: result,
          ),
        ),
      );
    } on SmartStyleApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.message),
          behavior: SnackBarBehavior.floating,
          duration: const Duration(seconds: 5),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Tarmoq xato: $e'),
          behavior: SnackBarBehavior.floating,
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  void _openDemo() {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => ResultsScreen(
          faceImage: _face,
          demoMode: true,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context).textTheme;

    return Stack(
      children: [
        Positioned.fill(
          child: SafeArea(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(24, 20, 24, 24),
              child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text('Skan', style: t.headlineSmall),
                const SizedBox(height: 8),
                Text(
                  'Rasm ixtiyoriy. Pastdagi profil maydonlari aniq tavsiya uchun (saytdagi SmartStyle bilan bir xil qiymatlar).',
                  style: t.bodyMedium,
                ),
                const SizedBox(height: 20),
                SizedBox(
                  height: 320,
                  child: Container(
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(24),
                      border: Border.all(color: AppTheme.hairline, width: 1.2),
                    ),
                    clipBehavior: Clip.antiAlias,
                    child: _face == null
                        ? Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                Icons.face_retouching_natural_rounded,
                                size: 64,
                                color: AppTheme.ink.withValues(alpha: 0.35),
                              ),
                              const SizedBox(height: 16),
                              Text('Rasm ixtiyoriy', style: t.titleMedium),
                              const SizedBox(height: 6),
                              Padding(
                                padding: const EdgeInsets.symmetric(horizontal: 32),
                                child: Text(
                                  'Rasmsiz ham yuborish mumkin',
                                  textAlign: TextAlign.center,
                                  style: t.bodySmall,
                                ),
                              ),
                            ],
                          )
                        : XFileImage(
                            file: _face!,
                            fit: BoxFit.cover,
                          ),
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: _loading ? null : () => _pick(ImageSource.gallery),
                        icon: const Icon(Icons.photo_library_outlined),
                        label: const Text('Galereya'),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: _loading ? null : () => _pick(ImageSource.camera),
                        icon: const Icon(Icons.photo_camera_outlined),
                        label: const Text('Kamera'),
                      ),
                    ),
                  ],
                ),
                if (_face != null) ...[
                  const SizedBox(height: 8),
                  Align(
                    alignment: Alignment.centerRight,
                    child: TextButton(
                      onPressed: _loading ? null : () => setState(() => _face = null),
                      child: const Text('Rasmni olib tashlash'),
                    ),
                  ),
                ],
                const SizedBox(height: 8),
                ExpansionTile(
                  tilePadding: EdgeInsets.zero,
                  title: Text('Profil maydonlari', style: t.titleMedium),
                  subtitle: Text(
                    'Jins, teri rangi, yuz shakli',
                    style: t.bodySmall,
                  ),
                  children: [
                    _ProfileDropdown(
                      label: 'Jins',
                      value: _gender,
                      options: kGenderOptions,
                      onChanged: (v) => setState(() => _gender = v ?? _gender),
                    ),
                    _ProfileDropdown(
                      label: 'Teri rangi',
                      value: _skinTone,
                      options: kSkinToneOptions,
                      onChanged: (v) => setState(() => _skinTone = v ?? _skinTone),
                    ),
                    _ProfileDropdown(
                      label: 'Yuz shakli',
                      value: _faceShape,
                      options: kFaceShapeOptions,
                      onChanged: (v) => setState(() => _faceShape = v ?? _faceShape),
                    ),
                    _ProfileDropdown(
                      label: 'Voqe / muhit',
                      value: _occasion.isEmpty ? '' : _occasion,
                      options: kOccasionOptions,
                      onChanged: (v) => setState(() => _occasion = v ?? ''),
                    ),
                    _ProfileDropdown(
                      label: 'Fasl',
                      value: _season.isEmpty ? '' : _season,
                      options: kSeasonOptions,
                      onChanged: (v) => setState(() => _season = v ?? ''),
                    ),
                    _ProfileDropdown(
                      label: 'Uslub maqsadi',
                      value: _styleIntent.isEmpty ? '' : _styleIntent,
                      options: kStyleIntentOptions,
                      onChanged: (v) => setState(() => _styleIntent = v ?? ''),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                FilledButton(
                  onPressed: _loading ? null : _runAnalyze,
                  child: _loading
                      ? const SizedBox(
                          height: 22,
                          width: 22,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Color(0xFFF7F5F2),
                          ),
                        )
                      : const Text('Tahlil qilish (server)'),
                ),
                const SizedBox(height: 10),
                TextButton(
                  onPressed: _loading ? null : _openDemo,
                  child: const Text('Namuna natija (offline)'),
                ),
              ],
            ),
            ),
          ),
        ),
        if (_loading)
          Positioned.fill(
            child: AbsorbPointer(
              child: Container(
                color: const Color(0x33000000),
                alignment: Alignment.center,
                child: const Card(
                  child: Padding(
                    padding: EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        CircularProgressIndicator(),
                        SizedBox(height: 16),
                        Text('Serverga ulanmoqda…'),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }
}

class _ProfileDropdown extends StatelessWidget {
  const _ProfileDropdown({
    required this.label,
    required this.value,
    required this.options,
    required this.onChanged,
  });

  final String label;
  final String value;
  final List<ProfileOption> options;
  final ValueChanged<String?> onChanged;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: DropdownButtonFormField<String>(
        // Controlled maydon: Flutter 3.33+ `initialValue` faqat birinchi build uchun.
        // ignore: deprecated_member_use
        value: value,
        decoration: InputDecoration(
          labelText: label,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
        ),
        items: options
            .map(
              (o) => DropdownMenuItem<String>(
                value: o.value,
                child: Text(o.label),
              ),
            )
            .toList(),
        onChanged: onChanged,
      ),
    );
  }
}
