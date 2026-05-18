# ✨ LifePlanner SIM v1.0

> **Secure, Responsive Personal Information Management (PIM) System**
> Replacing Excel-based personal tracking with a high-fidelity, premium interface across Web and Native Mobile platforms.

[![Laravel Version](https://img.shields.io/badge/Laravel-11.x-red?style=for-the-badge&logo=laravel)](https://laravel.com)
[![Livewire Version](https://img.shields.io/badge/Livewire-v3-EE70A6?style=for-the-badge&logo=livewire)](https://laravel-livewire.com)
[![Flutter Version](https://img.shields.io/badge/Flutter-Native-02569B?style=for-the-badge&logo=flutter)](https://flutter.dev)
[![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)](https://opensource.org/licenses/MIT)

---

## 🏛️ Repository Architecture

This repository is organized as a unified, cohesive mono-repo separating the web backend/API services from the mobile native application.

```
📁 Project
├── 📁 LifePlanner     # Laravel 11 web portal, API endpoints, and Livewire TALL Stack Engine
├── 📁 mlifeplanner    # Flutter native companion app targeting iOS and Android
└── 📄 .gitignore      # Root Git configurations ignoring OS and IDE-specific files
```

---

## ⚡ Core Modules & Features

The LifePlanner platform consolidates daily habit tracking, finance, and productivity metrics into a single-user secure ecosystem:

### 📊 Finance Manager
- **Budgeting & Transactions**: Real-time ledgers, customizable expense categories, and monthly target calculations.
- **Investment Tracking**: Secure database records of assets (stocks, crypto, precious metals), profit/loss tracking, and current valuation breakdown.
- **Quick Entry Portal**: Streamlined workflow allowing rapid financial logging within 2 taps/clicks.

### 🌱 Health & Habit Tracker
- **Vital Log**: Daily metrics tracker for sleep, water intake, weight, and general well-being.
- **Habit Streaks**: Recurring habit tracker featuring progressive completion bars and visual calendar streaks.

### 📅 Productivity Hub
- **Task Management**: Visual kanban tasks, daily calendars, and focus boards to plan days with zero friction.
- **Goal Setter**: Short and long-term milestones linked to actual actionable daily tasks.

### 🖥️ Interactive Dashboard
- Combined data stream aggregating financial status, today's schedule, habit completion rates, and quick actions on a single sleek interface.

---

## 🎨 Premium Design System

Both the web and mobile interfaces share a custom, premium design system optimized for readability and elegant, dark-mode styling:

| Token | Hex Value | Semantic Target |
| :--- | :--- | :--- |
| **Ink** | `#0D0D0F` | Deep dark mode background / Primary surface |
| **Paper** | `#F5F2ED` | Smooth, high-contrast light mode text & paper accents |
| **Forest** | `#2D5016` | Accent color for positive finance, health metrics, and growth |
| **Gold** | `#C9962A` | Highlights, warnings, and high-priority metrics |
| **Violet** | `#3D2B8A` | Focus modes, calendar events, and productivity stats |
| **Blush** | `#C45C6A` | Overdue tasks, expenses, and system notifications |

### ✒️ Typography
- **Headings**: `DM Serif Display` (Elegant, bold serif styling)
- **Body**: `Plus Jakarta Sans` (Clean, hyper-readable modern geometric sans-serif)
- **Code/Metrics**: `DM Mono` (Highly precise monospaced numeric layout)

---

## 🛠️ Technology Stack

### Backend (`LifePlanner`)
* **Core Framework**: Laravel 11.x
* **UI Engine**: Livewire v3, Alpine.js v3, Tailwind CSS v3.4+
* **Database**: MySQL 8.x
* **Caching**: Redis
* **Authorization**: Laravel Sanctum (session-based for web, token-based for mobile API)
* **Containers**: Docker, Docker Compose (Nginx, App, DB, Node)

### Mobile (`mlifeplanner`)
* **Framework**: Flutter (Dart)
* **State Management**: Provider
* **Networking**: Dio (with Sanctum Cookie & Token Interceptors)
* **Platform Support**: Native Android & iOS

---

## 🚀 Setup & Installation

### 1. Web Backend (`LifePlanner`)
First, copy the environment configuration and boot up the Docker container architecture:

```bash
# Navigate to the backend
cd LifePlanner

# Copy environment template
cp .env.example .env

# Boot up the Docker environment
docker-compose up -d --build

# Run package installation
docker-compose exec app composer install
docker-compose exec node npm install

# Generate application security keys
docker-compose exec app php artisan key:generate

# Perform database migrations
docker-compose exec app php artisan migrate
```

- **Web Portal URL**: `http://localhost:80`
- **Vite HMR**: Run `docker-compose up node` in a separate terminal.

### 2. Flutter Mobile Companion (`mlifeplanner`)
Ensure you have the Flutter SDK installed and a running simulator/physical device.

```bash
# Navigate to Flutter workspace
cd mlifeplanner

# Fetch dependencies
flutter pub get

# Configure backend API target
# Update api_service.dart to target your local machine API: http://10.0.2.2:80 (Android Emulator) or http://localhost:80 (iOS Simulator)

# Run the mobile app
flutter run
```

---

## 🔒 Security Practices

1. **Authentication**: Powered by Laravel Sanctum with an 8-hour session timeout for maximum data security.
2. **Integrity Check**: Complete server-side verification and CSRF token validations are enforced across all Livewire inputs and API endpoints.
3. **Data Safety**: All passwords are encrypted using `bcrypt` (work factor $\ge$ 12). Production credentials should always be configured through environment variables.

---

## 📜 Development Guidelines & AI Context

This repository is built with strong architectural discipline. If you are developing features using AI coding agents (Claude Code, Cursor, etc.), strictly follow the rule engine defined in [SourceOfTruth.md](file:///d:/src_code/Project/LifePlanner/SourceOfTruth.md):

1. **Hierarchy of Truth**: Reference `SourceOfTruth.md` first.
2. **Behavioral Compliance**: Ensure all code lines align with the official acceptance criteria mapping (e.g., using `// @see LP-US-AC-2026-001` annotations).
3. **Responsive Rule**: Ensure CSS/Blade structures are fully verified for mobile sizes (`375px`) and desktops (`1280px`).

---

*LifePlanner SIM v1.0 | Mei 2026 | MIT Licensed*
