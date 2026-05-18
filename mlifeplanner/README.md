# 📱 mlifeplanner — Flutter Companion App

> **Premium Native Mobile Companion for LifePlanner SIM**
> Replicating the ultra-sleek, dark-mode personal management workspace natively on iOS and Android.

[![Flutter Version](https://img.shields.io/badge/Flutter-SDK--3.x-02569B?style=for-the-badge&logo=flutter)](https://flutter.dev)
[![Dart Version](https://img.shields.io/badge/Dart-3.x-0175C2?style=for-the-badge&logo=dart)](https://dart.dev)
[![Platform Support](https://img.shields.io/badge/Platforms-iOS%20%7C%20Android-lightgrey?style=for-the-badge)](#)

---

## 🎨 Premium Mobile Design System

The mobile application replicates the exact UI tokens, typography styles, and dark-mode aesthetic from our web system using direct Flutter custom themes:

* **Theme Structure**: Managed dynamically in [`app_theme.dart`](file:///d:/src_code/Project/mlifeplanner/lib/core/theme/app_theme.dart).
* **Color Palette**:
  - `Ink` (`#0D0D0F`): Main Scaffold backgrounds and navigation surfaces.
  - `Paper` (`#F5F2ED`): Primary high-contrast text and sleek card surfaces.
  - `Forest` (`#2D5016`): Active metrics, positive yields, and safe habit states.
  - `Gold` (`#C9962A`): Highlight components and priority badges.
  - `Violet` (`#3D2B8A`): Focus timers, productivity tasks, and custom calendars.
  - `Blush` (`#C45C6A`): Budget limits exceeded, deleted states, and warnings.
* **Fonts**: Loaded via `google_fonts` including `Plus Jakarta Sans` for clean body elements and `DM Serif Display` for modern headings.

---

## 🗂️ Project Directory Structure

```
📁 mlifeplanner
├── 📁 android/           # Native Android configuration files
├── 📁 ios/               # Native iOS configuration files
├── 📁 lib/               # Main application source code
│   ├── 📁 core/          # Core utilities, services, and theme files
│   │   ├── 📁 services/  # Dio REST API service engines
│   │   └── 📁 theme/     # Global Color & Font token mappings
│   ├── 📁 providers/     # State management (Auth, Finance, Habits)
│   └── 📁 ui/            # UI components and modules
│       ├── 📁 screens/   # Main screens (Dashboard, Finance, Habits, Auth)
│       └── 📁 widgets/   # Custom reusable UI cards and progress bars
├── 📄 pubspec.yaml       # Flutter packages and assets declaration
└── 📄 DESIGN.md          # Architectural visual notes and layout wireframes
```

---

## 🛠️ Main Dependency Stack

* **State Management**: `provider` (Clean, decoupled model notifications)
* **Networking**: `dio` (Advanced HTTP clients supporting custom authentication headers)
* **Design & Icons**: `flutter_lucide` (Modern, beautiful outline icon sets matching the web interface)
* **Fonts**: `google_fonts` (Plus Jakarta Sans, Outfit, DM Serif Display, DM Mono)

---

## 🚀 Local Setup & Configuration

Follow these quick steps to get the Flutter app running on your simulator or physical device:

### 1. Restore Packages
Restore all Dart/Flutter packages:
```bash
flutter pub get
```

### 2. Configure Backend API Endpoint
Open [`api_service.dart`](file:///d:/src_code/Project/mlifeplanner/lib/core/services/api_service.dart) and configure the `baseUrl` corresponding to your local Laravel server environment:

```dart
// For Local Android Emulator:
const String baseUrl = 'http://10.0.2.2:80/api';

// For Local iOS Simulator:
const String baseUrl = 'http://localhost:80/api';

// For Physical Devices connected to same Wi-Fi:
const String baseUrl = 'http://<YOUR_LOCAL_IP>:80/api';
```

### 3. Start Application
```bash
# Verify attached devices
flutter devices

# Run the project on default device
flutter run
```

---

## 🔐 Session & Auth Interceptors

Authentication is securely bridged using **Laravel Sanctum**. 
* **State Preservation**: The [`AuthProvider`](file:///d:/src_code/Project/mlifeplanner/lib/providers/auth_provider.dart) manages active user sessions.
* **API Interceptor**: The [`ApiService`](file:///d:/src_code/Project/mlifeplanner/lib/core/services/api_service.dart) automatically intercepts outbound requests to attach authorization headers and manages HTTP `401 Unauthorized` states by redirecting users directly back to the premium [`LoginScreen`](file:///d:/src_code/Project/mlifeplanner/lib/ui/screens/auth/login_screen.dart).

---

*mlifeplanner v1.0 | Mobile Companion App | MIT Licensed*
