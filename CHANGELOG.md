# Changelog — DazeStack Bulk Plugin Manager

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html) and [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [0.0.3] — 2026-11-20

### 🌐 Internationalization & Accessibility
- **Full i18n & GlotPress Readiness**: Textdomain properly loaded via `plugins_loaded` hook and localized script translations connected through `wp_set_script_translations`.
- **Bundled Fallback Locales**: Built and packaged binary `.mo` / `.po` catalogs for Spanish (`es_ES`), German (`de_DE`), French (`fr_FR`), and Hindi (`hi_IN`).
- **Bidirectional RTL Support**: Native RTL layout flipping (`body.rtl`, `[dir="rtl"]`) for Arabic (`ar`), Hebrew (`he_IL`), Urdu (`ur`), and Persian (`fa_IR`).
- **Accessible Queue Dialog**: Fixed missing `#dsbpm-queue-complete` dialog structure in DOM with complete ARIA attributes (`role="dialog"`, `aria-modal="true"`).
- **Z-Index Stacking Guard**: Elevated modal stacking context to `z-index: 999999` with backdrop blur, preventing bleed-through from WordPress admin menu and floating widgets.
- **Adaptive Dark Mode**: Integrated CSS `@media (prefers-color-scheme: dark)` system token overrides.

### 🏢 Multisite & Core Reliability
- **WordPress Multisite Network Provisioning**: Registered `network_admin_menu` hooks and `-network` screen IDs for network super-administrators.
- **Data Hygiene (`uninstall.php`)**: Automated scrubbing of plugin options, transient caches, and recipes upon uninstallation.
- **Bounded Path Traversal**: Enforced max depth ($8) and symlink verification in `class-dsbpm-metrics.php` to prevent execution timeouts on large filesystems.

---

## [0.0.2] — 2026-11-20

### 📚 Added
- **Stack Recipes & Playbooks Engine** (`includes/class-dsbpm-recipes.php`): Built-in starter stacks (WooCommerce, Performance, Security, SEO) and custom user recipe persistence via WordPress options (`dsbpm_recipes`).
- **Automated Rollback Guard** (`includes/class-dsbpm-installer.php`): Wrapped `activate_plugin()` inside `try...catch (\Throwable $e)` exception sandbox; fatal activation crashes are caught, cleanly deactivated, and logged with diagnostic traces without breaking WP Admin.
- **1-Click WP-CLI Command Generator**: Exports active queue as ready-to-run terminal commands (`wp plugin install <slugs> --activate`) with 1-click clipboard copy.
- **Universal 4-Tier AI Mesh Integration** (`ai-mesh.json`): Connected to Local Apple Silicon Grid ($0.00), NVIDIA NIM Kimi-K3 1M Context & Llama 3.2 90B Vision, and Experiential Labs Claude Haiku 5.5 (541 tok/s) / Mercury 3.
- **4-Tier Project Management Mesh**: Adapted from SolarQna (`docs/roadmap/plane-mirror.md`, `notion-mirror.md`, `github-mirror.md`, and Obsidian Canvas maps).
- **Internationalization (i18n) & RTL Layout**: Added native Right-to-Left styling in `assets/admin.css` for Arabic, Hebrew, Urdu, and Persian admin dashboards; script translations hooked via `wp.i18n` and `wp_set_script_translations`.
- **WordPress Multisite Network Support**: Added `network_admin_menu` and screen detection for network super administrators.
- **Clean Uninstallation Lifecycle** (`uninstall.php`): Automatically cleans up options and transient caches upon deletion.
- **Public GitHub Remote**: Synchronized repository at `https://github.com/ashishdungdung/dazestack-bulk-plugin-manager`.
- **Submenu Navigation**: Added dedicated `About & Legal` documentation and `⭐ Review & Rate Us` pages.

### 🔒 Security & Performance
- **SSRF Defense**: Enforced host IP validation (`FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE`) and 5MB response body limit on remote URL imports.
- **AES-256-CBC Key Hardening**: Derived credential encryption keys using `wp_salt('auth')` fallback.
- **Full-Queue Batching**: Removed arbitrary 10-item cap on pre-flight health checks and payload calculations.
- **Modal Accessibility**: Added `role="dialog"`, `aria-modal="true"`, and backdrop-click dismissal.

---

## [0.0.1] — 2026-03-04

### 🚀 Initial Release
- **Core Bulk Provisioning Engine**: Batch installation, activation, deactivation, and deletion of WordPress plugins from slugs, CSV, and XLSX.
- **Silent Upgrader Skin** (`DSBPM_Upgrader_Skin`): Captures core installation messages without spilling raw HTML into JSON responses.
- **Mac-Style Glassmorphic UI**: Translucent frosted glassmorphism with `Fraunces` editorial serif typography and `Plus Jakarta Sans` UI clarity.
- **WPScan Vulnerability Scanner**: Optional CVSS score analysis against queued plugin slugs.
- **Partner Offers Grid**: Curated partner recommendations with 12-hour transient caching and local CSV fallback.
