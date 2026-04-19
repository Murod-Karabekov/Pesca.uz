import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// Minimal fashion palette: warm paper, ink, subtle accent.
abstract final class AppTheme {
  static const Color canvas = Color(0xFFF7F5F2);
  static const Color ink = Color(0xFF1C1B1A);
  static const Color inkMuted = Color(0xFF6B6560);
  static const Color accent = Color(0xFF8B7355);
  static const Color hairline = Color(0xFFE8E4DE);

  static ThemeData light() {
    final base = ThemeData(
      useMaterial3: true,
      brightness: Brightness.light,
      colorScheme: ColorScheme.light(
        surface: canvas,
        onSurface: ink,
        primary: ink,
        onPrimary: canvas,
        secondary: accent,
        onSecondary: canvas,
        outline: hairline,
      ),
      scaffoldBackgroundColor: canvas,
      splashFactory: InkRipple.splashFactory,
    );

    final display = GoogleFonts.frauncesTextTheme(base.textTheme);
    final body = GoogleFonts.outfitTextTheme(base.textTheme);

    return base.copyWith(
      textTheme: body.copyWith(
        displayLarge: display.displayLarge?.copyWith(
          fontWeight: FontWeight.w500,
          color: ink,
          letterSpacing: -0.5,
        ),
        displayMedium: display.displayMedium?.copyWith(
          fontWeight: FontWeight.w500,
          color: ink,
          letterSpacing: -0.3,
        ),
        headlineSmall: display.headlineSmall?.copyWith(
          fontWeight: FontWeight.w500,
          color: ink,
        ),
        titleLarge: body.titleLarge?.copyWith(
          fontWeight: FontWeight.w600,
          color: ink,
          letterSpacing: 0.2,
        ),
        titleMedium: body.titleMedium?.copyWith(
          fontWeight: FontWeight.w600,
          color: ink,
        ),
        bodyLarge: body.bodyLarge?.copyWith(
          color: ink,
          height: 1.45,
        ),
        bodyMedium: body.bodyMedium?.copyWith(
          color: inkMuted,
          height: 1.45,
        ),
        bodySmall: body.bodySmall?.copyWith(
          color: inkMuted,
          height: 1.35,
        ),
        labelLarge: body.labelLarge?.copyWith(
          fontWeight: FontWeight.w600,
          letterSpacing: 0.6,
        ),
      ),
      appBarTheme: AppBarTheme(
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: false,
        backgroundColor: canvas,
        foregroundColor: ink,
        titleTextStyle: GoogleFonts.outfit(
          fontSize: 18,
          fontWeight: FontWeight.w600,
          color: ink,
        ),
      ),
      navigationBarTheme: NavigationBarThemeData(
        indicatorColor: ink.withValues(alpha: 0.06),
        backgroundColor: canvas,
        elevation: 0,
        height: 72,
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return GoogleFonts.outfit(
            fontSize: 12,
            fontWeight: selected ? FontWeight.w600 : FontWeight.w500,
            color: selected ? ink : inkMuted,
          );
        }),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return IconThemeData(
            size: 24,
            color: selected ? ink : inkMuted,
          );
        }),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: ink,
          foregroundColor: canvas,
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
          textStyle: GoogleFonts.outfit(
            fontWeight: FontWeight.w600,
            letterSpacing: 0.4,
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: ink,
          side: const BorderSide(color: hairline, width: 1.2),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
          textStyle: GoogleFonts.outfit(fontWeight: FontWeight.w600),
        ),
      ),
      cardTheme: CardThemeData(
        color: Colors.white,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
          side: const BorderSide(color: hairline),
        ),
        margin: EdgeInsets.zero,
      ),
      dividerTheme: const DividerThemeData(color: hairline, thickness: 1),
    );
  }
}
