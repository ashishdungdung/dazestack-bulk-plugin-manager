=== DazeStack Bulk Plugin Manager ===
Contributors: dazestack, ashishdungdung
Tags: bulk install, plugin management, automation, admin tools, migration
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The most beautiful, native Mac-like bulk plugin manager for WordPress. Import, export, and provision plugin stacks in one streamlined workspace.

== Description ==

Welcome to the **DazeStack Bulk Plugin Manager (DSBPM)**, the ultimate utility for WordPress professionals who demand both power and aesthetics. Say goodbye to the tedious, one-by-one plugin installation process and hello to a streamlined, beautifully designed bulk management workspace.

Crafted by Ashish Dungdung under the DazeStack brand, this tool is designed from the ground up to bring a premium, native modern app experience directly to your WordPress admin dashboard. It's fast, elegant, and designed to save you hours of repetitive work.

### 🌟 Why Choose DazeStack Bulk Plugin Manager?

*   **Lightning Fast Provisioning**: Install, activate, deactivate, or delete multiple plugins simultaneously with a single click.
*   **Premium Mac-Style UI**: Experience a WordPress dashboard like never before. DSBPM features a stunning, glassmorphic UI inspired by modern macOS design. It's dark-mode ready, visually clean, and a joy to use.
*   **One-Click Import & Export**: Found the perfect stack of plugins? Export your list and import it into any other site in seconds using JSON, CSV, or raw text.
*   **Pre-Flight Safety Checks**: Real-time validation checks ensure the plugins you are bulk installing are available and safe before you commit changes.
*   **No Coding Required**: Forget complex WP-CLI commands. Everything is handled through an intuitive, fluid visual interface.

### 🚀 Key Features

*   **Bulk Actions**: Install, Activate, Deactivate, and Delete.
*   **Stack Imports**: Paste a comma-separated list of plugin slugs, or upload a JSON/CSV file.
*   **Instant Export**: Backup your currently active plugin stack to re-use on client sites.
*   **Vulnerability Scanning (Optional)**: Integration with WPScan API to ensure your plugin stack is secure.
*   **Non-Intrusive**: Leaves zero footprint on your website's frontend. 100% focused on wp-admin optimization.

### 💼 Who is this for?

*   **Freelancers & Agencies**: Standardize your "base recipe" of plugins across all new client projects.
*   **Developers**: Quickly spin up testing environments with your required toolsets.
*   **Power Users**: Keep your site’s backend organized and efficiently manage heavy plugin loads.

---

== External services ==

This plugin interacts with the following external services to provide plugin information, metrics, and security checks:

1. **WordPress.org Plugins API**: Used to fetch plugin metadata (title, version, last updated, active installs) and download links for bulk installation.
   - Data sent: Plugin slugs.
   - Purpose: Core functionality for plugin discovery and provisioning.

2. **WPScan API**: Used (optionally) to check for known vulnerabilities in queued plugins.
   - Data sent: Plugin slugs and (if provided by user) a WPScan API key.
   - Purpose: Security auditing of the plugin stack.
   - Terms: [WPScan Terms of Service](https://wpscan.com/terms), [Privacy Policy](https://wpscan.com/privacy).

3. **Google Sheets (CSV Export)**: Used to fetch curated partner recommendations.
   - Data sent: None (read-only fetch).
   - Purpose: Providing relevant toolkit suggestions.

== Installation ==

1. **Upload**: Install the plugin via the WordPress admin dashboard by uploading the zip file, or search for it in the Plugins directory.
2. **Activate**: Activate the plugin through the 'Plugins' menu in WordPress.
3. **Launch**: Locate the beautiful new **DazeStack** menu item in your WordPress admin sidebar to launch the workspace.

== Frequently Asked Questions ==

= Do I need to know how to use WP-CLI? =
No! That is the beauty of DazeStack Bulk Plugin Manager. It brings the power of bulk operations typically reserved for developers into a beautiful, visual user interface.

= Which import formats are supported? =
You can copy and paste raw plugin slugs (e.g., `elementor, woocommerce, contact-form-7`), or upload lists via CSV, XLSX, and JSON formats for maximum flexibility.

= Can I backup my current plugins? =
Yes. The plugin includes an instant "Export List" feature that downloads your currently active plugins as a portable list, perfect for starting new projects.

= Is it safe to use? =
Absolutely. The plugin leverages core WordPress APIs for all installations and activations, ensuring 100% compatibility and safety. It also includes pre-flight checks before executing operations.

== Screenshots ==

1. **The Workspace**: A stunning, high-fidelity Mac-style glassmorphic dashboard for managing your plugin stacks.
2. **Bulk Import**: Paste your slugs or upload a file.
3. **Execution**: Watch real-time progress bars as your plugins are fetched and installed.

== Changelog ==

= 0.0.2 =
* 📚 Added Stack Recipes & Playbooks Library (E-Commerce, Performance, Security, SEO starter stacks + custom saved recipes).
* 🛡️ Added Automated Rollback Guard: trapped fatal errors on plugin activation and automatically rolled back site state safely.
* 💻 Added WP-CLI Command Generator: 1-click export of `wp plugin install` commands for terminal provisioners.
* ⚡ Enhanced Pre-flight & Payload batch processing for arbitrary queue lengths.
* 🔍 Added Live Search filtering for Managed Plugins list.
* 🔒 Hardened SSRF defenses for remote URL fetches and enhanced AES-256 credential key derivation using `wp_salt()`.
* ⌨️ Added keyboard shortcuts (`Cmd/Ctrl+Enter` to launch queue, `Esc` to close modals).

= 0.0.1 =
* 🚀 Initial release to the WordPress Plugin Directory.
* ✨ Added beautiful Mac-style glassmorphic UI.
* 📦 Added core bulk import/export functionality.
* 🛡️ Added WPScan API and pre-flight check integrations.
