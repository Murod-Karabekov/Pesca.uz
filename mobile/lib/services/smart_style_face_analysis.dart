import 'dart:io';
import 'dart:math';

import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';
import 'package:image/image.dart' as img;

/// Veb `smart-style-scan.js` dagi MediaPipe o‘rniga ML Kit landmarklari bilan
/// yuz shakli va teri rangini taxminiy aniqlash (backend enumlari bilan mos).
class SmartStyleFaceAnalysisException implements Exception {
  SmartStyleFaceAnalysisException(this.message);
  final String message;

  @override
  String toString() => message;
}

abstract final class SmartStyleFaceAnalysis {
  static double _dist(Point<int> a, Point<int> b) {
    final dx = a.x - b.x;
    final dy = a.y - b.y;
    return sqrt(dx * dx + dy * dy);
  }

  static Point<int> _lm(Face face, FaceLandmarkType t, Point<int> fallback) {
    return face.landmarks[t]?.position ?? fallback;
  }

  /// Veb `calculateFaceShape` mantig‘iga yaqin, lekin ML Kit nuqtalari bilan.
  static String faceShapeFromFace(Face face) {
    final box = face.boundingBox;
    final cx = box.center.dx.toInt();
    final cy = box.center.dy.toInt();

    final jawLeft = _lm(face, FaceLandmarkType.leftMouth, Point(cx - box.width ~/ 3, cy));
    final jawRight = _lm(face, FaceLandmarkType.rightMouth, Point(cx + box.width ~/ 3, cy));
    final chin = _lm(face, FaceLandmarkType.bottomMouth, Point(cx, box.bottom.toInt() - 4));
    final leftEye = _lm(face, FaceLandmarkType.leftEye, Point(cx - 24, cy - 16));
    final rightEye = _lm(face, FaceLandmarkType.rightEye, Point(cx + 24, cy - 16));
    final eyeY = min(leftEye.y, rightEye.y);
    final eyeDist = _dist(leftEye, rightEye);
    final forehead = Point(
      ((leftEye.x + rightEye.x) / 2).round(),
      (eyeY - eyeDist * 0.45).round(),
    );
    final cheekLeft = _lm(face, FaceLandmarkType.leftCheek, Point(cx - box.width ~/ 4, cy));
    final cheekRight = _lm(face, FaceLandmarkType.rightCheek, Point(cx + box.width ~/ 4, cy));
    final foreheadLeft = Point(
      (leftEye.x - _dist(leftEye, rightEye) * 0.12).round(),
      leftEye.y,
    );
    final foreheadRight = Point(
      (rightEye.x + _dist(leftEye, rightEye) * 0.12).round(),
      rightEye.y,
    );

    final faceHeight = _dist(forehead, chin);
    final jawWidth = _dist(jawLeft, jawRight);
    final foreheadWidth = _dist(foreheadLeft, foreheadRight);
    final cheekWidth = _dist(cheekLeft, cheekRight);

    if (jawWidth < 1 || foreheadWidth < 1) {
      return 'oval';
    }

    final ratio = faceHeight / jawWidth;
    final jawToForehead = jawWidth / foreheadWidth;

    if (ratio > 1.6) return 'oblong';
    if (ratio < 1.1 && jawToForehead > 0.9) return 'round';
    if (jawToForehead > 1.05 && ratio < 1.4) return 'square';
    if (foreheadWidth > jawWidth * 1.15) return 'heart';
    if (cheekWidth > foreheadWidth * 1.1 && cheekWidth > jawWidth * 1.1) {
      return 'diamond';
    }
    return 'oval';
  }

  /// Veb `calculateSkinTone` bilan bir xil brightness/warmth chegaralari.
  static String skinToneFromImage(
    img.Image image,
    Face face,
  ) {
    int rSum = 0, gSum = 0, bSum = 0, n = 0;

    void sampleAt(Point<int> c) {
      for (var dx = -2; dx <= 2; dx++) {
        for (var dy = -2; dy <= 2; dy++) {
          final x = c.x + dx;
          final y = c.y + dy;
          if (x < 0 || y < 0 || x >= image.width || y >= image.height) continue;
          final p = image.getPixel(x, y);
          rSum += p.r.toInt();
          gSum += p.g.toInt();
          bSum += p.b.toInt();
          n++;
        }
      }
    }

    final box = face.boundingBox;
    final cx = box.center.dx.toInt();
    final cy = box.center.dy.toInt();

    final pts = <Point<int>>[
      _lm(face, FaceLandmarkType.leftCheek, Point(cx - box.width ~/ 5, cy)),
      _lm(face, FaceLandmarkType.rightCheek, Point(cx + box.width ~/ 5, cy)),
      _lm(face, FaceLandmarkType.noseBase, Point(cx, cy + box.height ~/ 8)),
      _lm(face, FaceLandmarkType.leftMouth, Point(cx - box.width ~/ 6, cy + box.height ~/ 5)),
    ];

    for (final c in pts) {
      sampleAt(c);
    }

    if (n == 0) {
      return 'warm_medium';
    }

    final avgR = rSum / n;
    final avgG = gSum / n;
    final avgB = bSum / n;
    final brightness = (avgR * 299 + avgG * 587 + avgB * 114) / 1000;
    final warmth = avgR - avgB;

    if (brightness > 180) return 'light';
    if (brightness > 130) {
      return warmth > 25 ? 'warm_medium' : 'cool_medium';
    }
    return 'dark';
  }

  /// Rasm faylidan yuz aniqlab `skinTone` va `faceShape` qaytaradi.
  static Future<({String skinTone, String faceShape})> analyzeFromFilePath(
    String path,
  ) async {
    final bytes = await File(path).readAsBytes();
    final decoded = img.decodeImage(bytes);
    if (decoded == null) {
      throw SmartStyleFaceAnalysisException('Rasmni o‘qib bo‘lmadi.');
    }

    final options = FaceDetectorOptions(
      enableLandmarks: true,
      enableContours: false,
      performanceMode: FaceDetectorMode.accurate,
    );
    final detector = FaceDetector(options: options);
    try {
      final input = InputImage.fromFilePath(path);
      final faces = await detector.processImage(input);
      if (faces.isEmpty) {
        throw SmartStyleFaceAnalysisException(
          'Yuz aniqlanmadi. Iltimos, yuzingiz aniq ko‘rinadigan rasm ishlating.',
        );
      }
      faces.sort(
        (a, b) => (b.boundingBox.width * b.boundingBox.height)
            .compareTo(a.boundingBox.width * a.boundingBox.height),
      );
      final face = faces.first;
      final shape = faceShapeFromFace(face);
      final tone = skinToneFromImage(decoded, face);
      return (skinTone: tone, faceShape: shape);
    } finally {
      await detector.close();
    }
  }
}
