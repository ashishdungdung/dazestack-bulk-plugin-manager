# Project Conventions (Developer & AI Instruction Manual)

This document is designed to guide AI agents and developers on how to maintain, extend, and optimize the **DazeStack Bulk Plugin Manager** codebase.

---

## 🤖 AI & Developer Guidelines

- **Be Concise**: Favor brief, high-impact comments over verbose explanations.
- **Context Awareness**: Refer to `docs/ARCHITECTURE.md` to understand system data flows instead of re-indexing every time.
- **Incremental Edits**: When modifying existing code, use precise diff replacements rather than rewriting entire files.
- **No Placeholders**: All code implementations must be 100% functional.
- **Zero Frontend Impact**: All admin styles and scripts must be strictly scoped to `wp-admin` pages (`allowed_pages`).

---

## 🛠 Coding Standards

- **WordPress Standards**: Follow WordPress Coding Standards for PHP. Use tabs for indentation in PHP and JS.
- **Singleton Pattern**: Core service classes must implement `instance()` for static access.
- **Security & Authorization**: All AJAX actions must enforce `check_ajax_referer('dsbpm_nonce', 'nonce')` and `current_user_can('capability')`.
- **SSRF Defense**: Remote URL requests must validate protocol (`wp_http_validate_url`) and resolve host IPs to block private/loopback address ranges.
- **UI Design System**: Maintain the DazeStack aesthetic — frosted glassmorphic cards, Warm Tangerine primary buttons, Deep Emerald Teal accents, and `Fraunces` + `Plus Jakarta Sans` typography.

---

## 📂 File Structure

- `bulk-plugin-manager.php`: Main entry point and constants definition.
- `includes/`: Core PHP logic classes:
  - `class-dsbpm-admin.php`: Enqueues assets, registers AJAX endpoints and REST routes.
  - `class-dsbpm-admin-menu.php`: Submenu registration, templates, and CSV export.
  - `class-dsbpm-ajax.php`: AJAX controller for all 24+ background operations.
  - `class-dsbpm-recipes.php`: Stack recipe playbooks and WP-CLI command generator.
  - `class-dsbpm-installer.php`: Upgrader wrapper with activation rollback guard.
  - `class-dsbpm-credentials.php`: Encrypted FTP/SSH filesystem credential manager.
  - `class-dsbpm-metrics.php`: Site disk usage and WPScan security scanner.
  - `class-dsbpm-partners.php`: Dynamic offer feed fetcher and CSV parser.
  - `class-dsbpm-skin.php`: Silent upgrader feedback skin.
- `assets/`: UI design system (`admin.css`, `admin.js`, vendor assets).
- `docs/`: Technical specifications and architectural guides.

---

## 🔄 Quality Assurance Workflow

1. Always run PHP syntax check (`php -l`) on modified PHP files before committing.
2. Re-package `dazestack-bulk-plugin-manager.zip` when updating releases.
3. Keep `docs/ARCHITECTURE.md` and `readme.txt` up-to-date with new endpoints or options.
