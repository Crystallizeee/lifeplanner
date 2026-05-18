import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppTheme {
  // Colors from DESIGN.md
  static const Color surface = Color(0xFF131315);
  static const Color surfaceVariant = Color(0xFF353437);
  static const Color background = Color(0xFF131315);
  static const Color primary = Color(0xFF4EDEA3);
  static const Color primaryContainer = Color(0xFF10B981);
  static const Color onPrimary = Color(0xFF003824);
  static const Color secondary = Color(0xFFFFB95F);
  static const Color tertiary = Color(0xFFFFB3B6);
  static const Color error = Color(0xFFFFB4AB);
  static const Color onBackground = Color(0xFFE5E1E4);
  static const Color onSurfaceVariant = Color(0xFFBBCABF);
  static const Color outline = Color(0xFF2D2D30); // Subdued border

  // Material Design 3 Color Scheme
  static const ColorScheme darkColorScheme = ColorScheme.dark(
    primary: primary,
    onPrimary: onPrimary,
    primaryContainer: primaryContainer,
    secondary: secondary,
    tertiary: tertiary,
    error: error,
    background: background,
    onBackground: onBackground,
    surface: surface,
    onSurface: onBackground,
    surfaceVariant: surfaceVariant,
    onSurfaceVariant: onSurfaceVariant,
    outline: outline,
  );

  static ThemeData get darkTheme {
    return ThemeData(
      useMaterial3: true,
      colorScheme: darkColorScheme,
      scaffoldBackgroundColor: background,
      
      // Typography
      textTheme: TextTheme(
        displayLarge: GoogleFonts.dmSerifDisplay(
          fontSize: 48,
          fontWeight: FontWeight.w400,
          color: onBackground,
        ),
        displayMedium: GoogleFonts.dmSerifDisplay(
          fontSize: 36,
          fontWeight: FontWeight.w400,
          color: onBackground,
        ),
        headlineMedium: GoogleFonts.dmSerifDisplay(
          fontSize: 32,
          fontWeight: FontWeight.w400,
          color: onBackground,
        ),
        titleLarge: GoogleFonts.plusJakartaSans(
          fontSize: 20,
          fontWeight: FontWeight.w600,
          color: onBackground,
        ),
        bodyLarge: GoogleFonts.plusJakartaSans(
          fontSize: 16,
          fontWeight: FontWeight.w400,
          color: onBackground,
        ),
        bodyMedium: GoogleFonts.plusJakartaSans(
          fontSize: 14,
          fontWeight: FontWeight.w400,
          color: onSurfaceVariant,
        ),
        labelSmall: GoogleFonts.dmMono(
          fontSize: 12,
          fontWeight: FontWeight.w500,
          letterSpacing: 0.5,
          color: onSurfaceVariant,
        ),
      ),

      // Input Decoration (Text Fields)
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: const Color(0xFF1A1A1C), // Level 1 Surface
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(4), // 0.25rem from DESIGN.md
          borderSide: const BorderSide(color: outline),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(4),
          borderSide: const BorderSide(color: outline),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(4),
          borderSide: const BorderSide(color: primaryContainer, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(4),
          borderSide: const BorderSide(color: error),
        ),
        hintStyle: GoogleFonts.dmMono(color: onSurfaceVariant),
      ),

      // Buttons
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: primaryContainer,
          foregroundColor: Colors.black, // High contrast text on emerald
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(8), // 0.5rem
          ),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          textStyle: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w600,
            fontSize: 16,
          ),
          elevation: 0,
        ),
      ),
      
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: onBackground,
          side: const BorderSide(color: outline),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(8),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          textStyle: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w600,
            fontSize: 16,
          ),
        ),
      ),

      // Cards
      cardTheme: CardThemeData(
        color: const Color(0xFF1A1A1C),
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(8), // 0.5rem
          side: const BorderSide(color: outline),
        ),
        margin: EdgeInsets.zero,
      ),

      // App Bar
      appBarTheme: AppBarTheme(
        backgroundColor: background,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: GoogleFonts.dmSerifDisplay(
          fontSize: 24,
          fontWeight: FontWeight.w400,
          color: onBackground,
        ),
        iconTheme: const IconThemeData(color: onBackground),
      ),
      
      // Divider
      dividerTheme: const DividerThemeData(
        color: outline,
        thickness: 1,
        space: 1,
      ),
    );
  }
}
