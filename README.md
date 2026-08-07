# DazeStack Bulk Plugin Manager (DSBPM)

High-efficiency bulk provisioning and stack management for WordPress. Crafted by Ashish Dungdung under the DazeStack series to keep sites lean, colorful, and fast.

[![WordPress Plugin Version](https://img.shields.io/badge/wordpress--plugin-v0.0.2-ff8a65.svg)](https://wordpress.org/plugins/dazestack-bulk-plugin-manager/)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%207.4-1f9d8a.svg)](https://php.net)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

---

## 🌟 Key Features

- **⚡ Bulk Provisioning**: Install, activate, deactivate, or delete multiple plugins in batch.
- **📚 Stack Recipes & Playbooks**: Built-in starter recipes (*WooCommerce*, *Performance*, *Security*, *SEO*) plus custom recipe creation and management.
- **🛡️ Automated Rollback Guard**: Traps fatal activation errors inside an exception sandbox, automatically deactivates crashing plugins, and rolls back site state cleanly.
- **💻 WP-CLI Command Generator**: Generate ready-to-run `wp plugin install <slugs> --activate` commands with 1-click clipboard export.
- **🔍 Live Search & Filter**: Instant search filtering across managed site plugins.
- **🔒 SSRF Defense & Security Engine**: Host IP resolution and private range validation for remote fetches; optional WPScan API integration to block critical vulnerabilities (CVSS >= 9.0).
- **📊 Payload & Health Profiling**: Full-queue pre-flight health scoring and download size calculations.
- **✨ Mac-Style Glassmorphic UI**: High-DPI frosted glass aesthetic with `Fraunces` serif typography and `Plus Jakarta Sans` UI clarity.

---

## 📂 File Structure Overview

- `bulk-plugin-manager.php`: Main entry point and bootstrap setup.
- `includes/`: Core PHP modules:
  - `class-dsbpm-admin.php`: Enqueue setup, AJAX routes, and REST registration.
  - `class-dsbpm-admin-menu.php`: UI templates, submenu navigation, and export handlers.
  - `class-dsbpm-ajax.php`: AJAX controller with 24+ secured actions and SSRF defenses.
  - `class-dsbpm-recipes.php`: Stack recipes catalog, storage, and WP-CLI generator.
  - `class-dsbpm-installer.php`: Upgrader wrapper with activation rollback sandbox.
  - `class-dsbpm-credentials.php`: AES-256-CBC encrypted FTP/SSH credential manager.
  - `class-dsbpm-metrics.php`: Disk usage, version metrics, and WPScan security scanner.
  - `class-dsbpm-partners.php`: Dynamic partner offer feed parser with local fallback.
  - `class-dsbpm-skin.php`: Silent upgrader feedback skin.
- `assets/`: Design system CSS, JS interactivity, and vendor libraries.
- `docs/`: Technical specifications and architecture guides.

---

## 🚀 Getting Started

1. Upload the `bulk-plugin-manager` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Access the workspace via the **DazeStack** menu in the WP Admin sidebar.

---

## 📜 License & Author

- **Author**: Ashish Dungdung ([ashishdungdung.com](http://ashishdungdung.com))
- **Brand**: DazeStack ([dazestack.com](https://dazestack.com))
- **License**: GPLv2 or later
- Made with ❤️ in India.
