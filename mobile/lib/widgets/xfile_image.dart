import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

/// Rasmni barcha platformalarda xavfsiz ko‘rsatish (web uchun ham `dart:io`siz).
class XFileImage extends StatelessWidget {
  const XFileImage({
    super.key,
    required this.file,
    this.fit = BoxFit.cover,
    this.borderRadius,
  });

  final XFile file;
  final BoxFit fit;
  final BorderRadius? borderRadius;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Uint8List>(
      future: file.readAsBytes(),
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(
            child: SizedBox(
              width: 28,
              height: 28,
              child: CircularProgressIndicator(strokeWidth: 2),
            ),
          );
        }
        if (snapshot.hasError || !snapshot.hasData) {
          return Icon(
            Icons.broken_image_outlined,
            size: 48,
            color: Theme.of(context).colorScheme.outline,
          );
        }
        Widget image = Image.memory(
          snapshot.data!,
          fit: fit,
          gaplessPlayback: true,
        );
        if (borderRadius != null) {
          image = ClipRRect(borderRadius: borderRadius!, child: image);
        }
        return image;
      },
    );
  }
}
