import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';

import '../data/smart_style_profile_options.dart';
import '../services/smart_style_api.dart';
import '../services/smart_style_face_analysis.dart';
import '../theme/app_theme.dart';
import '../widgets/xfile_image.dart';
import 'results_screen.dart';

List<ProfileOption> _multiOptions(List<ProfileOption> src) =>
    src.where((e) => e.value.isNotEmpty).toList();

/// Veb `/smart-style/scan` bilan bir xil ketma-ketlik (boshqa UI).
class SmartStyleWizardScreen extends StatefulWidget {
  const SmartStyleWizardScreen({super.key});

  @override
  State<SmartStyleWizardScreen> createState() => _SmartStyleWizardScreenState();
}

class _SmartStyleWizardScreenState extends State<SmartStyleWizardScreen> {
  static const int _stepGender = 0;
  static const int _stepOccasion = 1;
  static const int _stepStyle = 2;
  static const int _stepSeason = 3;
  static const int _stepMeasure = 4;
  static const int _stepPhoto = 5;

  int _step = _stepGender;

  String? _gender;
  final Set<String> _occasions = {};
  final Set<String> _styleIntents = {};
  final Set<String> _seasons = {};

  final _height = TextEditingController();
  final _shoulder = TextEditingController();
  final _chest = TextEditingController();
  final _waist = TextEditingController();
  final _hip = TextEditingController();

  final ImagePicker _picker = ImagePicker();
  XFile? _photo;
  bool _analyzing = false;
  String _analyzeStatus = '';

  @override
  void dispose() {
    _height.dispose();
    _shoulder.dispose();
    _chest.dispose();
    _waist.dispose();
    _hip.dispose();
    super.dispose();
  }

  bool get _mlSupported {
    if (kIsWeb) return false;
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
      case TargetPlatform.iOS:
        return true;
      default:
        return false;
    }
  }

  void _goBack() {
    if (_step > _stepGender) {
      setState(() => _step -= 1);
    }
  }

  void _goForward() {
    if (_step < _stepPhoto) {
      setState(() => _step += 1);
    }
  }

  int? _parseCm(TextEditingController c, int min, int max) {
    final t = c.text.trim();
    if (t.isEmpty) return null;
    final v = int.tryParse(t);
    if (v == null) return null;
    if (v < min || v > max) return null;
    return v;
  }

  Future<void> _pickPhoto(ImageSource source) async {
    try {
      final f = await _picker.pickImage(
        source: source,
        maxWidth: 1600,
        maxHeight: 1600,
        imageQuality: 88,
      );
      if (!mounted) return;
      if (f != null) setState(() => _photo = f);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Rasm tanlanmadi: $e'), behavior: SnackBarBehavior.floating),
      );
    }
  }

  Future<void> _runAnalyze() async {
    if (_gender == null || _gender!.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Jins tanlanmagan.'), behavior: SnackBarBehavior.floating),
      );
      return;
    }
    if (_photo == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Surat majburiy. Galereya yoki kameradan tanlang.'),
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }

    setState(() {
      _analyzing = true;
      _analyzeStatus = 'Model yuklanmoqda...';
    });

    String skinTone;
    String faceShape;
    try {
      if (!_mlSupported) {
        throw SmartStyleFaceAnalysisException(
          'Yuz tahlili hozircha faqat Android va iOS da ishlaydi.',
        );
      }
      setState(() => _analyzeStatus = 'Yuz aniqlanmoqda...');
      final r = await SmartStyleFaceAnalysis.analyzeFromFilePath(_photo!.path);
      skinTone = r.skinTone;
      faceShape = r.faceShape;
      setState(() => _analyzeStatus = 'Natijalar saqlanmoqda...');
    } on SmartStyleFaceAnalysisException catch (e) {
      if (!mounted) return;
      setState(() => _analyzing = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.message), behavior: SnackBarBehavior.floating, duration: const Duration(seconds: 5)),
      );
      return;
    } catch (e) {
      if (!mounted) return;
      setState(() => _analyzing = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Tahlil xato: $e'), behavior: SnackBarBehavior.floating),
      );
      return;
    }

    final occasionList = _occasions.toList()..sort();
    final styleList = _styleIntents.toList()..sort();
    final seasonList = _seasons.toList()..sort();

    final profile = SmartStyleProfilePayload(
      gender: _gender,
      skinTone: skinTone,
      faceShape: faceShape,
      occasion: occasionList.isEmpty ? null : occasionList.first,
      styleIntent: styleList.isEmpty ? null : styleList.first,
      season: seasonList.isEmpty ? null : seasonList.first,
      heightCm: _parseCm(_height, 100, 230),
      shoulderCm: _parseCm(_shoulder, 30, 80),
      chestCm: _parseCm(_chest, 50, 180),
      waistCm: _parseCm(_waist, 40, 180),
      hipCm: _parseCm(_hip, 50, 200),
    );

    try {
      final result = await SmartStyleApi().analyze(photo: _photo, profile: profile);
      if (!mounted) return;
      setState(() => _analyzing = false);
      await Navigator.of(context).push<void>(
        MaterialPageRoute<void>(
          builder: (_) => ResultsScreen(faceImage: _photo, apiResult: result),
        ),
      );
    } on SmartStyleApiException catch (e) {
      if (!mounted) return;
      setState(() => _analyzing = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.message), behavior: SnackBarBehavior.floating, duration: const Duration(seconds: 6)),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() => _analyzing = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Server: $e'), behavior: SnackBarBehavior.floating),
      );
    }
  }

  void _resetPhotoOnly() {
    setState(() => _photo = null);
  }

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context).textTheme;

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (didPop) return;
        if (_analyzing) return;
        if (_step > _stepGender) {
          _goBack();
        }
      },
      child: Stack(
        children: [
          Scaffold(
            appBar: AppBar(
              title: Text('SmartStyle · ${_step + 1}/6'),
              leading: IconButton(
                icon: const Icon(Icons.arrow_back_rounded),
                onPressed: _analyzing
                    ? null
                    : () {
                        if (_step > _stepGender) {
                          _goBack();
                        }
                      },
              ),
            ),
            body: SafeArea(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text(_stepTitle, style: t.titleLarge),
                    const SizedBox(height: 8),
                    Text(_stepSubtitle, style: t.bodyMedium?.copyWith(color: AppTheme.inkMuted)),
                    const SizedBox(height: 20),
                    Expanded(child: _stepBody(t)),
                  ],
                ),
              ),
            ),
          ),
          if (_analyzing)
            ColoredBox(
              color: Colors.black54,
              child: Center(
                child: Card(
                  margin: const EdgeInsets.all(32),
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const CircularProgressIndicator(),
                        const SizedBox(height: 20),
                        Text(_analyzeStatus, textAlign: TextAlign.center),
                      ],
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  String get _stepTitle {
    switch (_step) {
      case _stepGender:
        return '1-qadam: Jinsni tanlang';
      case _stepOccasion:
        return '2-qadam: Nimaga kiyim tanlayapsiz?';
      case _stepStyle:
        return '3-qadam: Qanday uslub xohlaysiz?';
      case _stepSeason:
        return '4-qadam: Ob-havo (fasl)';
      case _stepMeasure:
        return '5-qadam: Tana ma’lumoti';
      case _stepPhoto:
        return '6-qadam: Yuz skan (majburiy)';
      default:
        return 'SmartStyle';
    }
  }

  String get _stepSubtitle {
    switch (_step) {
      case _stepGender:
        return 'Aniqroq tavsiya va tana tipini aniqlash uchun zarur.';
      case _stepOccasion:
      case _stepStyle:
      case _stepSeason:
        return 'Bu qadamni xohlasangiz o‘tkazib yuborishingiz mumkin.';
      case _stepMeasure:
        return 'Har bir joyni o‘lchab kiriting yoki o‘tkazib yuboring.';
      case _stepPhoto:
        return 'Rasm serverga ixtiyoriy yuborilishi mumkin (tahlil asosan qurilmada).';
      default:
        return '';
    }
  }

  Widget _stepBody(TextTheme t) {
    switch (_step) {
      case _stepGender:
        return _GenderStep(
          selected: _gender,
          onPick: (g) => setState(() => _gender = g),
          onNext: _gender != null ? _goForward : null,
        );
      case _stepOccasion:
        return _MultiToggleStep(
          options: _multiOptions(kOccasionOptions),
          selected: _occasions,
          onToggle: (v) => setState(() {
            if (_occasions.contains(v)) {
              _occasions.remove(v);
            } else {
              _occasions.add(v);
            }
          }),
          onSkip: () {
            setState(() => _occasions.clear());
            _goForward();
          },
          onNext: _goForward,
        );
      case _stepStyle:
        return _MultiToggleStep(
          options: _multiOptions(kStyleIntentOptions),
          selected: _styleIntents,
          onToggle: (v) => setState(() {
            if (_styleIntents.contains(v)) {
              _styleIntents.remove(v);
            } else {
              _styleIntents.add(v);
            }
          }),
          onSkip: () {
            setState(() => _styleIntents.clear());
            _goForward();
          },
          onNext: _goForward,
        );
      case _stepSeason:
        return _MultiToggleStep(
          options: _multiOptions(kSeasonOptions),
          selected: _seasons,
          onToggle: (v) => setState(() {
            if (_seasons.contains(v)) {
              _seasons.remove(v);
            } else {
              _seasons.add(v);
            }
          }),
          onSkip: () {
            setState(() => _seasons.clear());
            _goForward();
          },
          onNext: _goForward,
        );
      case _stepMeasure:
        return _MeasureStep(
          height: _height,
          shoulder: _shoulder,
          chest: _chest,
          waist: _waist,
          hip: _hip,
          onSkip: () {
            _height.clear();
            _shoulder.clear();
            _chest.clear();
            _waist.clear();
            _hip.clear();
            _goForward();
          },
          onNext: _goForward,
        );
      case _stepPhoto:
        return _PhotoStep(
          photo: _photo,
          onCamera: () => _pickPhoto(ImageSource.camera),
          onGallery: () => _pickPhoto(ImageSource.gallery),
          onRetake: _resetPhotoOnly,
          onAnalyze: _runAnalyze,
        );
      default:
        return const SizedBox.shrink();
    }
  }
}

class _GenderStep extends StatelessWidget {
  const _GenderStep({
    required this.selected,
    required this.onPick,
    required this.onNext,
  });

  final String? selected;
  final ValueChanged<String> onPick;
  final VoidCallback? onNext;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Expanded(
          child: Row(
            children: [
              Expanded(
                child: _GenderCard(
                  emoji: '👨',
                  label: 'Erkak',
                  selected: selected == 'male',
                  onTap: () => onPick('male'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _GenderCard(
                  emoji: '👩',
                  label: 'Ayol',
                  selected: selected == 'female',
                  onTap: () => onPick('female'),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        FilledButton(
          onPressed: onNext,
          child: const Text('Davom etish'),
        ),
      ],
    );
  }
}

class _GenderCard extends StatelessWidget {
  const _GenderCard({
    required this.emoji,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String emoji;
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(20),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(vertical: 28),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(20),
            border: Border.all(
              color: selected ? AppTheme.accent : AppTheme.hairline,
              width: selected ? 2.5 : 1,
            ),
            color: selected ? AppTheme.accent.withValues(alpha: 0.08) : null,
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(emoji, style: const TextStyle(fontSize: 48)),
              const SizedBox(height: 12),
              Text(label, style: Theme.of(context).textTheme.titleMedium),
            ],
          ),
        ),
      ),
    );
  }
}

class _MultiToggleStep extends StatelessWidget {
  const _MultiToggleStep({
    required this.options,
    required this.selected,
    required this.onToggle,
    required this.onSkip,
    required this.onNext,
  });

  final List<ProfileOption> options;
  final Set<String> selected;
  final ValueChanged<String> onToggle;
  final VoidCallback onSkip;
  final VoidCallback onNext;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Expanded(
          child: SingleChildScrollView(
            child: Wrap(
              spacing: 8,
              runSpacing: 8,
              children: options
                  .map(
                    (o) => FilterChip(
                      label: Text(o.label),
                      selected: selected.contains(o.value),
                      onSelected: (_) => onToggle(o.value),
                    ),
                  )
                  .toList(),
            ),
          ),
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: OutlinedButton(
                onPressed: onSkip,
                child: const Text('O‘tkazib yuborish'),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: FilledButton(
                onPressed: onNext,
                child: const Text('Davom etish'),
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _MeasureStep extends StatelessWidget {
  const _MeasureStep({
    required this.height,
    required this.shoulder,
    required this.chest,
    required this.waist,
    required this.hip,
    required this.onSkip,
    required this.onNext,
  });

  final TextEditingController height;
  final TextEditingController shoulder;
  final TextEditingController chest;
  final TextEditingController waist;
  final TextEditingController hip;
  final VoidCallback onSkip;
  final VoidCallback onNext;

  @override
  Widget build(BuildContext context) {
    Widget field(String label, String hint, TextEditingController c, List<TextInputFormatter> formatters) {
      return Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: TextField(
          controller: c,
          keyboardType: TextInputType.number,
          inputFormatters: formatters,
          decoration: InputDecoration(
            labelText: label,
            hintText: hint,
            border: const OutlineInputBorder(),
          ),
        ),
      );
    }

    final digits = [FilteringTextInputFormatter.digitsOnly];

    return Column(
      children: [
        Expanded(
          child: SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                field('Bo‘y (sm)', '100–230, masalan 170', height, digits),
                field('Yelka (sm)', '30–80', shoulder, digits),
                field('Ko‘krak (sm)', '50–180', chest, digits),
                field('Bel (sm)', '40–180', waist, digits),
                field('Son (sm)', '50–200', hip, digits),
              ],
            ),
          ),
        ),
        Row(
          children: [
            Expanded(
              child: OutlinedButton(
                onPressed: onSkip,
                child: const Text('O‘tkazib yuborish'),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: FilledButton(
                onPressed: onNext,
                child: const Text('Davom etish'),
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _PhotoStep extends StatelessWidget {
  const _PhotoStep({
    required this.photo,
    required this.onCamera,
    required this.onGallery,
    required this.onRetake,
    required this.onAnalyze,
  });

  final XFile? photo;
  final VoidCallback onCamera;
  final VoidCallback onGallery;
  final VoidCallback onRetake;
  final VoidCallback onAnalyze;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Expanded(
          child: ClipRRect(
            borderRadius: BorderRadius.circular(20),
            child: AspectRatio(
              aspectRatio: 1,
              child: photo != null
                  ? XFileImage(file: photo!, fit: BoxFit.cover)
                  : ColoredBox(
                      color: AppTheme.ink.withValues(alpha: 0.06),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text('😊', style: Theme.of(context).textTheme.displayMedium),
                          const SizedBox(height: 12),
                          Text(
                            'Yuzingizni shu yerga',
                            textAlign: TextAlign.center,
                            style: Theme.of(context).textTheme.bodyMedium,
                          ),
                        ],
                      ),
                    ),
            ),
          ),
        ),
        const SizedBox(height: 14),
        if (photo == null) ...[
          FilledButton.icon(
            onPressed: onCamera,
            icon: const Icon(Icons.photo_camera_rounded),
            label: const Text('Kamerani ochish'),
          ),
          const SizedBox(height: 8),
          OutlinedButton.icon(
            onPressed: onGallery,
            icon: const Icon(Icons.photo_library_outlined),
            label: const Text('Galereyadan yuklash'),
          ),
        ] else ...[
          FilledButton.icon(
            onPressed: onAnalyze,
            icon: const Icon(Icons.auto_awesome),
            label: const Text('Tahlil qilish'),
          ),
          const SizedBox(height: 8),
          OutlinedButton.icon(
            onPressed: onRetake,
            icon: const Icon(Icons.refresh_rounded),
            label: const Text('Qayta olish'),
          ),
        ],
      ],
    );
  }
}
