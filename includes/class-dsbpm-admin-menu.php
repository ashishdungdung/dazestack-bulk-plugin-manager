<?php

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Handles menu registration, UI templates, and main dashboard rendering.
 */
class DSBPM_Admin_Menu
{
	private static $instance;

	public static function instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function register_menu()
	{
		add_menu_page(
			__('DazeStack Suite', 'dazestack-bulk-plugin-manager'),
			__('DazeStack', 'dazestack-bulk-plugin-manager'),
			'install_plugins',
			DSBPM_BASE_SLUG,
			array($this, 'render_dashboard'),
			'dashicons-screenoptions',
			58
		);

		add_submenu_page(
			DSBPM_BASE_SLUG,
			__('Dashboard', 'dazestack-bulk-plugin-manager'),
			__('Dashboard', 'dazestack-bulk-plugin-manager'),
			'install_plugins',
			DSBPM_BASE_SLUG,
			array($this, 'render_dashboard')
		);

		add_submenu_page(
			DSBPM_BASE_SLUG,
			__('Bulk Plugin Manager', 'dazestack-bulk-plugin-manager'),
			__('Bulk Plugin Manager', 'dazestack-bulk-plugin-manager'),
			'install_plugins',
			DSBPM_SLUG,
			array($this, 'render_page')
		);

		add_submenu_page(
			DSBPM_BASE_SLUG,
			__('About & Legal', 'dazestack-bulk-plugin-manager'),
			__('About & Legal', 'dazestack-bulk-plugin-manager'),
			'install_plugins',
			'dsbpm-about-legal',
			array($this, 'render_about_legal_page')
		);

		add_submenu_page(
			DSBPM_BASE_SLUG,
			__('Review & Rate Us', 'dazestack-bulk-plugin-manager'),
			__('⭐ Review & Rate Us', 'dazestack-bulk-plugin-manager'),
			'install_plugins',
			'dsbpm-review',
			array($this, 'render_review_page')
		);
	}

	public function render_dashboard()
	{
?>
		<div class="dsbpm-app dsbpm-app--suite">
			<div class="dsbpm-shell">
				<div class="dsbpm-hero dsbpm-hero--suite">
					<div class="dsbpm-hero__branding">
						<div class="dsbpm-pill"><?php esc_html_e('DazeStack - Made in India', 'dazestack-bulk-plugin-manager'); ?></div>
						<div class="dsbpm-hero__title"><?php esc_html_e('DazeStack Dashboard', 'dazestack-bulk-plugin-manager'); ?></div>
						<div class="dsbpm-hero__subtitle"><?php esc_html_e('A living space for the DazeStack WordPress plugin series. Crafted by Ashish Dungdung to keep sites lean, colorful, and fast.', 'dazestack-bulk-plugin-manager'); ?></div>
						<div class="dsbpm-hero__tags">
							<span class="dsbpm-chip"><?php esc_html_e('Performance-first', 'dazestack-bulk-plugin-manager'); ?></span>
							<span class="dsbpm-chip"><?php esc_html_e('Design Language', 'dazestack-bulk-plugin-manager'); ?></span>
							<span class="dsbpm-chip"><?php esc_html_e('Future-ready', 'dazestack-bulk-plugin-manager'); ?></span>
						</div>
					</div>
					<div class="dsbpm-hero__actions">
						<a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=' . DSBPM_SLUG)); ?>"><?php esc_html_e('Open Bulk Plugin Manager', 'dazestack-bulk-plugin-manager'); ?></a>
						<a class="button button-secondary" href="<?php echo esc_url($this->tracked_url('https://dazestack.com/plugins/dazestack-wp-bulk-plugin-manager/', 'dashboard-project')); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Project Page', 'dazestack-bulk-plugin-manager'); ?></a>
					</div>
				</div>

				<div class="dsbpm-grid dsbpm-grid--suite">
					<div class="dsbpm-card dsbpm-card--story dsbpm-card--lead">
						<div class="dsbpm-card__title"><?php esc_html_e('The Story', 'dazestack-bulk-plugin-manager'); ?></div>
						<p><?php esc_html_e('DazeStack is a bold WordPress toolkit born in India. It pairs visual finesse with automation so teams can launch stacks faster without losing craft.', 'dazestack-bulk-plugin-manager'); ?></p>
						<p class="dsbpm-muted"><?php esc_html_e('This dashboard will house future modules. For now, jump into the Bulk Plugin Manager to orchestrate your plugins with grace.', 'dazestack-bulk-plugin-manager'); ?></p>
					</div>
					<div class="dsbpm-card dsbpm-card--cta">
						<div class="dsbpm-card__title"><?php esc_html_e('Bulk Plugin Manager', 'dazestack-bulk-plugin-manager'); ?></div>
						<div class="dsbpm-card__meta"><?php esc_html_e('Provision faster. Install smarter. Stay lean.', 'dazestack-bulk-plugin-manager'); ?></div>
						<div class="dsbpm-pill dsbpm-pill--glow"><?php esc_html_e('Live', 'dazestack-bulk-plugin-manager'); ?></div>
						<a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=' . DSBPM_SLUG)); ?>"><?php esc_html_e('Launch Manager', 'dazestack-bulk-plugin-manager'); ?></a>
					</div>
					<div class="dsbpm-card dsbpm-card--roadmap">
						<div class="dsbpm-card__title"><?php esc_html_e('Coming Next', 'dazestack-bulk-plugin-manager'); ?></div>
						<ul class="dsbpm-list">
							<li><?php esc_html_e('Performance Canvas', 'dazestack-bulk-plugin-manager'); ?></li>
							<li><?php esc_html_e('Stack Guard (Uptime + Security)', 'dazestack-bulk-plugin-manager'); ?></li>
							<li><?php esc_html_e('Playbooks for multisite rollouts', 'dazestack-bulk-plugin-manager'); ?></li>
						</ul>
						<div class="dsbpm-hint"><?php echo wp_kses_post(sprintf(/* translators: 1: URL to website */__('Your ideas welcome. Drop a note at <a href="%1$s" target="_blank" rel="noopener noreferrer">ashishdungdung.com</a>.', 'dazestack-bulk-plugin-manager'), esc_url($this->tracked_url('http://ashishdungdung.com', 'dashboard-note')))); ?></div>
					</div>
				</div>

				<?php $this->render_modules(); ?>

				<?php $this->render_support_cards(); ?>

				<?php $this->render_footer(); ?>
			</div>
		</div>
	<?php
	}

	public function render_page()
	{
	?>
		<div class="dsbpm-app dsbpm-app--bpm">
			<div class="dsbpm-shell">
				<div class="dsbpm-hero dsbpm-hero--bpm">
					<div class="dsbpm-hero__branding">
						<div class="dsbpm-pill"><?php esc_html_e('Made in India &middot; DazeStack Series', 'dazestack-bulk-plugin-manager'); ?></div>
						<div class="dsbpm-hero__title"><?php esc_html_e('DazeStack Bulk Plugin Manager', 'dazestack-bulk-plugin-manager'); ?></div>
						<div class="dsbpm-hero__subtitle"><?php esc_html_e('Provision faster. Install smarter. Stay lean. A vibrant, fluid workspace to orchestrate plugins without losing design craft.', 'dazestack-bulk-plugin-manager'); ?></div>
						<div class="dsbpm-hero__tags">
							<span class="dsbpm-chip"><?php esc_html_e('Bulk install', 'dazestack-bulk-plugin-manager'); ?></span>
							<span class="dsbpm-chip"><?php esc_html_e('Health + Simulation', 'dazestack-bulk-plugin-manager'); ?></span>
							<span class="dsbpm-chip"><?php esc_html_e('Payload aware', 'dazestack-bulk-plugin-manager'); ?></span>
						</div>
					</div>
					<div class="dsbpm-hero__actions">
						<button class="button button-secondary" id="dsbpm-open-recipes"><?php esc_html_e('Recipe Library', 'dazestack-bulk-plugin-manager'); ?></button>
						<button class="button button-secondary" id="dsbpm-export-wp-cli"><?php esc_html_e('Export WP-CLI', 'dazestack-bulk-plugin-manager'); ?></button>
						<a class="button button-secondary" href="<?php echo esc_url(admin_url('admin-post.php?action=dsbpm_export')); ?>"><?php esc_html_e('Export CSV', 'dazestack-bulk-plugin-manager'); ?></a>
						<button class="button button-primary" id="dsbpm-start-install"><?php esc_html_e('Run Bulk Install', 'dazestack-bulk-plugin-manager'); ?></button>
						<button class="button dsbpm-button--danger" id="dsbpm-stop-install" style="display:none;"><?php esc_html_e('Stop Install', 'dazestack-bulk-plugin-manager'); ?></button>
					</div>
				</div>

				<div class="dsbpm-dashboard">
					<div class="dsbpm-toast" id="dsbpm-toast" role="status" aria-live="polite"></div>

					<?php $this->render_support_cards(); ?>

					<div class="dsbpm-grid dsbpm-grid--xl">
						<div class="dsbpm-card dsbpm-card--import dsbpm-card--lead">
							<div class="dsbpm-card__title">
								<?php esc_html_e('Import Stack', 'dazestack-bulk-plugin-manager'); ?>
								<button class="dsbpm-help" data-help="<?php esc_attr_e('CSV / XLSX headers: slug, source (url), version, activate (1/0), network (1/0), force (1/0). JSON: [{"slug":"elementor","activate":1}]. Slugs are sanitized automatically.', 'dazestack-bulk-plugin-manager'); ?>">?</button>
							</div>
							<div class="dsbpm-dropzone" id="dsbpm-dropzone">
								<div class="dsbpm-dropzone__icon">DS</div>
								<div class="dsbpm-dropzone__text"><?php esc_html_e('Drop a CSV/XLSX or paste slugs to build your stack.', 'dazestack-bulk-plugin-manager'); ?></div>
								<input type="file" id="dsbpm-file" accept=".csv,.xlsx,text/csv" />
							</div>
							<textarea id="dsbpm-slug-input" class="dsbpm-textarea" placeholder="<?php esc_attr_e('Paste plugin slugs, one per line', 'dazestack-bulk-plugin-manager'); ?>"></textarea>
							<div class="dsbpm-inline-actions">
								<button class="button" id="dsbpm-add-slugs"><?php esc_html_e('Add Slugs', 'dazestack-bulk-plugin-manager'); ?></button>
								<div class="dsbpm-inline-actions__group">
									<button class="button button-secondary" id="dsbpm-template-csv"><?php esc_html_e('Download CSV Template', 'dazestack-bulk-plugin-manager'); ?></button>
									<button class="button button-secondary" id="dsbpm-template-json"><?php esc_html_e('Download JSON Template', 'dazestack-bulk-plugin-manager'); ?></button>
								</div>
							</div>
							<div class="dsbpm-hint" id="dsbpm-import-meta"><?php esc_html_e('Ready for clean imports. Duplicates are ignored.', 'dazestack-bulk-plugin-manager'); ?></div>
						</div>

						<div class="dsbpm-card dsbpm-card--remote">
							<div class="dsbpm-card__title">
								<?php esc_html_e('Remote Source', 'dazestack-bulk-plugin-manager'); ?>
								<button class="dsbpm-help" data-help="<?php esc_attr_e('Point to a CSV or JSON file hosted anywhere. CSV must include a header row. JSON can be an array of strings (slugs) or objects with slug/source/version.', 'dazestack-bulk-plugin-manager'); ?>">?</button>
							</div>
							<div class="dsbpm-field">
								<input type="url" id="dsbpm-remote-url" placeholder="https://example.com/stack.csv or .json" />
								<button class="button" id="dsbpm-remote-fetch"><?php esc_html_e('Fetch', 'dazestack-bulk-plugin-manager'); ?></button>
							</div>
							<div class="dsbpm-hint"><?php esc_html_e('Supports CSV or JSON lists. Remote fetches are user-initiated and logged.', 'dazestack-bulk-plugin-manager'); ?></div>
							<div class="dsbpm-status" id="dsbpm-remote-status"></div>
						</div>

						<div class="dsbpm-card dsbpm-card--health">
							<div class="dsbpm-card__title"><?php esc_html_e('Stack Health', 'dazestack-bulk-plugin-manager'); ?></div>
							<div class="dsbpm-health" id="dsbpm-health">
								<div class="dsbpm-health__ring">
									<svg viewBox="0 0 120 120">
										<circle cx="60" cy="60" r="54" class="dsbpm-health__track"></circle>
										<circle cx="60" cy="60" r="54" class="dsbpm-health__value" stroke-dasharray="339" stroke-dashoffset="339"></circle>
									</svg>
									<div class="dsbpm-health__score" id="dsbpm-health-score">--</div>
								</div>
								<div class="dsbpm-health__meta" id="dsbpm-health-meta"><?php esc_html_e('Run pre-flight to see risk flags.', 'dazestack-bulk-plugin-manager'); ?></div>
							</div>
							<div class="dsbpm-toggle">
								<input type="checkbox" id="dsbpm-auto-preflight" checked />
								<label for="dsbpm-auto-preflight"><?php esc_html_e('Auto pre-flight on import', 'dazestack-bulk-plugin-manager'); ?></label>
							</div>
							<div class="dsbpm-toggle">
								<input type="checkbox" id="dsbpm-auto-payload" checked />
								<label for="dsbpm-auto-payload"><?php esc_html_e('Auto payload sizing', 'dazestack-bulk-plugin-manager'); ?></label>
							</div>
							<div class="dsbpm-inline-actions">
								<button class="button" id="dsbpm-preflight"><?php esc_html_e('Run Pre-Flight', 'dazestack-bulk-plugin-manager'); ?></button>
								<button class="button" id="dsbpm-simulate"><?php esc_html_e('Simulation Mode', 'dazestack-bulk-plugin-manager'); ?></button>
							</div>
							<div class="dsbpm-health-report" id="dsbpm-health-report"></div>
						</div>

						<div class="dsbpm-card dsbpm-card--payload">
							<div class="dsbpm-card__title"><?php esc_html_e('Payload', 'dazestack-bulk-plugin-manager'); ?></div>
							<div class="dsbpm-payload">
								<div class="dsbpm-payload__bar"><span id="dsbpm-payload-bar"></span></div>
								<div class="dsbpm-payload__text" id="dsbpm-payload-text"><?php esc_html_e('Total Payload: --', 'dazestack-bulk-plugin-manager'); ?></div>
							</div>
							<div class="dsbpm-inline-actions">
								<button class="button" id="dsbpm-payload"><?php esc_html_e('Calculate Payload', 'dazestack-bulk-plugin-manager'); ?></button>
								<div class="dsbpm-toggle">
									<input type="checkbox" id="dsbpm-reliability" />
									<label for="dsbpm-reliability"><?php esc_html_e('High Reliability Mode (background queue)', 'dazestack-bulk-plugin-manager'); ?></label>
								</div>
							</div>
							<div class="dsbpm-hint" id="dsbpm-payload-meta"><?php esc_html_e('Get a payload profile before you press install.', 'dazestack-bulk-plugin-manager'); ?></div>
						</div>
					</div>

					<div class="dsbpm-stage">
						<div class="dsbpm-stage__header">
							<div class="dsbpm-stage__title"><?php esc_html_e('Queue', 'dazestack-bulk-plugin-manager'); ?></div>
							<div class="dsbpm-stage__actions">
								<button class="button" id="dsbpm-sort-queue"><?php esc_html_e('Smart Sort', 'dazestack-bulk-plugin-manager'); ?></button>
								<button class="button" id="dsbpm-save-recipe-btn"><?php esc_html_e('Save Recipe', 'dazestack-bulk-plugin-manager'); ?></button>
								<button class="button" id="dsbpm-save-favorite"><?php esc_html_e('Save Favorite', 'dazestack-bulk-plugin-manager'); ?></button>
								<button class="button" id="dsbpm-load-favorite"><?php esc_html_e('Load Favorite', 'dazestack-bulk-plugin-manager'); ?></button>
								<button class="button" id="dsbpm-clear-queue"><?php esc_html_e('Clear', 'dazestack-bulk-plugin-manager'); ?></button>
							</div>
						</div>
						<div class="dsbpm-queue-meta" id="dsbpm-queue-summary"><?php esc_html_e('Lean queue. Add plugins or load a favorite.', 'dazestack-bulk-plugin-manager'); ?></div>
						<div class="dsbpm-queue" id="dsbpm-queue"></div>
						<div class="dsbpm-queue-empty" id="dsbpm-queue-empty"><?php esc_html_e('No plugins queued yet. Import a stack, fetch remote, or paste slugs to start.', 'dazestack-bulk-plugin-manager'); ?></div>
						<svg class="dsbpm-deps" id="dsbpm-deps"></svg>
						<div class="dsbpm-queue-legend">
							<span class="dsbpm-chip"><?php esc_html_e('Clean = no risk flags', 'dazestack-bulk-plugin-manager'); ?></span>
							<span class="dsbpm-chip"><?php esc_html_e('Installed = successful', 'dazestack-bulk-plugin-manager'); ?></span>
							<span class="dsbpm-chip"><?php esc_html_e('Rescued = install failed and rolled back', 'dazestack-bulk-plugin-manager'); ?></span>
							<span class="dsbpm-chip"><?php esc_html_e('Blocked = security', 'dazestack-bulk-plugin-manager'); ?></span>
						</div>
					</div>

					<div class="dsbpm-grid dsbpm-grid--tight">
						<div class="dsbpm-card dsbpm-card--metrics">
							<div class="dsbpm-card__title"><?php esc_html_e('Site Metrics', 'dazestack-bulk-plugin-manager'); ?></div>
							<div class="dsbpm-metrics-chart" id="dsbpm-metrics-chart"></div>
							<div class="dsbpm-metrics" id="dsbpm-metrics"></div>
							<button class="button" id="dsbpm-refresh-metrics"><?php esc_html_e('Refresh Metrics', 'dazestack-bulk-plugin-manager'); ?></button>
						</div>
						<div class="dsbpm-card dsbpm-card--security">
							<div class="dsbpm-card__title"><?php esc_html_e('Security Bouncer', 'dazestack-bulk-plugin-manager'); ?></div>
							<div class="dsbpm-field">
								<input type="password" id="dsbpm-wpscan-key" placeholder="<?php esc_attr_e('WPScan API key (optional)', 'dazestack-bulk-plugin-manager'); ?>" />
								<button class="button" id="dsbpm-vuln-check"><?php esc_html_e('Run Scan', 'dazestack-bulk-plugin-manager'); ?></button>
							</div>
							<div class="dsbpm-hint"><?php esc_html_e('Blocks critical vulnerabilities when an API key is set.', 'dazestack-bulk-plugin-manager'); ?></div>
						</div>
						<div class="dsbpm-card dsbpm-card--bulk">
							<div class="dsbpm-card__title"><?php esc_html_e('Bulk Actions', 'dazestack-bulk-plugin-manager'); ?></div>
							<div class="dsbpm-actions">
								<button class="button" id="dsbpm-deactivate-all"><?php esc_html_e('Deactivate All (except DazeStack)', 'dazestack-bulk-plugin-manager'); ?></button>
								<button class="button button-danger" id="dsbpm-delete-all"><?php esc_html_e('Delete All (except DazeStack)', 'dazestack-bulk-plugin-manager'); ?></button>
							</div>
							<div class="dsbpm-hint"><?php esc_html_e('Use with care. These actions cannot be undone.', 'dazestack-bulk-plugin-manager'); ?></div>
						</div>
					</div>

					<div class="dsbpm-card dsbpm-card--wide">
						<div class="dsbpm-card__header-row">
							<div class="dsbpm-card__title"><?php esc_html_e('Managed Plugins', 'dazestack-bulk-plugin-manager'); ?></div>
							<input type="text" id="dsbpm-managed-search" class="dsbpm-input dsbpm-input--search" placeholder="<?php esc_attr_e('Search plugins...', 'dazestack-bulk-plugin-manager'); ?>" />
						</div>
						<div class="dsbpm-managed-legend">
							<span class="dsbpm-chip"><?php esc_html_e('Active', 'dazestack-bulk-plugin-manager'); ?></span>
							<span class="dsbpm-chip dsbpm-chip--pin"><?php esc_html_e('Pinned', 'dazestack-bulk-plugin-manager'); ?></span>
							<span class="dsbpm-chip"><?php esc_html_e('Auto-Update', 'dazestack-bulk-plugin-manager'); ?></span>
							<span class="dsbpm-chip"><?php esc_html_e('Notes ready', 'dazestack-bulk-plugin-manager'); ?></span>
						</div>
						<div class="dsbpm-managed" id="dsbpm-managed"></div>
					</div>

					<div class="dsbpm-card dsbpm-card--wide dsbpm-card--promo-hero">
						<div class="dsbpm-card__title"><?php esc_html_e('Spotlight', 'dazestack-bulk-plugin-manager'); ?></div>
						<div class="dsbpm-promo-hero" id="dsbpm-promo-hero">
							<a class="dsbpm-promo-hero__link" href="<?php echo esc_url($this->tracked_url('https://dazestack.com/plugins/dazestack-wp-bulk-plugin-manager/', 'promo-hero-cta')); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Curated partner recommendations', 'dazestack-bulk-plugin-manager'); ?>">
								<div class="dsbpm-promo-hero__content">
									<div class="dsbpm-pill dsbpm-pill--soft"><?php esc_html_e('Partner spotlight', 'dazestack-bulk-plugin-manager'); ?></div>
									<div class="dsbpm-promo-hero__title"><?php esc_html_e('Curated partner recommendations', 'dazestack-bulk-plugin-manager'); ?></div>
									<div class="dsbpm-promo-hero__desc"><?php esc_html_e('Fresh offers from trusted tools and services that complement your DazeStack workflow.', 'dazestack-bulk-plugin-manager'); ?></div>
									<div class="dsbpm-promo-hero__cta">
										<span class="dsbpm-button" id="dsbpm-promo-hero-cta"><?php esc_html_e('Explore offers', 'dazestack-bulk-plugin-manager'); ?></span>
									</div>
								</div>
								<div class="dsbpm-promo-hero__media" aria-hidden="true"></div>
							</a>
						</div>
						<div class="dsbpm-promo-source" id="dsbpm-promo-source"><?php esc_html_e('Loading partner feed...', 'dazestack-bulk-plugin-manager'); ?></div>
					</div>

					<div class="dsbpm-card dsbpm-card--promo-grid">
						<div class="dsbpm-card__title"><?php esc_html_e('Partner Offers', 'dazestack-bulk-plugin-manager'); ?></div>
						<div class="dsbpm-promo-grid" id="dsbpm-promo-grid">
							<div class="dsbpm-muted"><?php esc_html_e('Offers will appear here once the feed loads.', 'dazestack-bulk-plugin-manager'); ?></div>
						</div>
					</div>

					<div class="dsbpm-modal" id="dsbpm-credentials-modal" aria-hidden="true">
						<div class="dsbpm-modal__content">
							<div class="dsbpm-modal__title"><?php esc_html_e('Filesystem Credentials Required', 'dazestack-bulk-plugin-manager'); ?></div>
							<div class="dsbpm-modal__body" id="dsbpm-credentials-body"></div>
							<button class="button" id="dsbpm-credentials-close"><?php esc_html_e('Close', 'dazestack-bulk-plugin-manager'); ?></button>
						</div>
					</div>

					<div class="dsbpm-modal" id="dsbpm-recipes-modal" aria-hidden="true">
						<div class="dsbpm-modal__content dsbpm-modal__content--wide">
							<div class="dsbpm-modal__title"><?php esc_html_e('Recipe Library & Playbooks', 'dazestack-bulk-plugin-manager'); ?></div>
							<div class="dsbpm-modal__body" id="dsbpm-recipes-body">
								<div class="dsbpm-muted"><?php esc_html_e('Loading recipes...', 'dazestack-bulk-plugin-manager'); ?></div>
							</div>
							<div class="dsbpm-modal__footer">
								<button class="button" id="dsbpm-recipes-close"><?php esc_html_e('Close', 'dazestack-bulk-plugin-manager'); ?></button>
							</div>
						</div>
					</div>

					<div class="dsbpm-modal" id="dsbpm-save-recipe-modal" aria-hidden="true">
						<div class="dsbpm-modal__content">
							<div class="dsbpm-modal__title"><?php esc_html_e('Save Current Queue as Recipe', 'dazestack-bulk-plugin-manager'); ?></div>
							<div class="dsbpm-modal__body">
								<div class="dsbpm-field-group">
									<label for="dsbpm-recipe-title-input"><?php esc_html_e('Recipe Title', 'dazestack-bulk-plugin-manager'); ?></label>
									<input type="text" id="dsbpm-recipe-title-input" class="dsbpm-input" placeholder="<?php esc_attr_e('e.g. Agency Base Recipe', 'dazestack-bulk-plugin-manager'); ?>" />
								</div>
								<div class="dsbpm-field-group">
									<label for="dsbpm-recipe-desc-input"><?php esc_html_e('Description', 'dazestack-bulk-plugin-manager'); ?></label>
									<textarea id="dsbpm-recipe-desc-input" class="dsbpm-textarea" placeholder="<?php esc_attr_e('Brief description of what this stack includes...', 'dazestack-bulk-plugin-manager'); ?>"></textarea>
								</div>
							</div>
							<div class="dsbpm-modal__footer">
								<button class="button button-primary" id="dsbpm-save-recipe-confirm"><?php esc_html_e('Save Recipe', 'dazestack-bulk-plugin-manager'); ?></button>
								<button class="button" id="dsbpm-save-recipe-close"><?php esc_html_e('Cancel', 'dazestack-bulk-plugin-manager'); ?></button>
							</div>
						</div>
					</div>

					<div class="dsbpm-modal" id="dsbpm-wpcli-modal" aria-hidden="true">
						<div class="dsbpm-modal__content">
							<div class="dsbpm-modal__title"><?php esc_html_e('WP-CLI Command Generator', 'dazestack-bulk-plugin-manager'); ?></div>
							<div class="dsbpm-modal__body">
								<p class="dsbpm-muted"><?php esc_html_e('Copy and paste this command into your terminal to provision this stack via WP-CLI:', 'dazestack-bulk-plugin-manager'); ?></p>
								<textarea id="dsbpm-wpcli-output" class="dsbpm-textarea dsbpm-textarea--code" readonly></textarea>
							</div>
							<div class="dsbpm-modal__footer">
								<button class="button button-primary" id="dsbpm-wpcli-copy"><?php esc_html_e('Copy to Clipboard', 'dazestack-bulk-plugin-manager'); ?></button>
								<button class="button" id="dsbpm-wpcli-close"><?php esc_html_e('Close', 'dazestack-bulk-plugin-manager'); ?></button>
							</div>
						</div>
					</div>
				</div>

				<?php $this->render_support_cards(); ?>

				<?php $this->render_footer(); ?>
			</div>
		</div>
	<?php
	}

	private function render_modules()
	{
		$modules = $this->get_dashboard_modules();
		if (empty($modules)) {
			return;
		}
	?>
		<div class="dsbpm-card dsbpm-card--wide dsbpm-card--modules">
			<div class="dsbpm-card__title"><?php esc_html_e('DazeStack Modules', 'dazestack-bulk-plugin-manager'); ?></div>
			<div class="dsbpm-modules">
				<?php foreach ($modules as $module) : ?>
					<?php
					$title = isset($module['title']) ? $module['title'] : '';
					$desc  = isset($module['description']) ? $module['description'] : '';
					$status = isset($module['status']) ? $module['status'] : '';
					$cta   = isset($module['cta']) ? $module['cta'] : '';
					$url   = isset($module['url']) ? $module['url'] : '';
					$install_url = isset($module['install_url']) ? $module['install_url'] : '';
					$wporg_slug = isset($module['wporg_slug']) ? $module['wporg_slug'] : '';
					$installed = array_key_exists('installed', $module) ? (bool) $module['installed'] : true;
					$tag   = isset($module['tag']) ? $module['tag'] : '';
					$status_key = strtolower(preg_replace('/[^a-z0-9]+/', '-', $status));
					$status_class = in_array($status_key, array('live', 'active', 'installed'), true) ? 'dsbpm-chip--on' : 'dsbpm-chip--ghost';
					if (! $installed) {
						if (empty($cta)) {
							$cta = __('Install Plugin', 'dazestack-bulk-plugin-manager');
						}
						if (empty($url)) {
							if (! empty($install_url)) {
								$url = $install_url;
							} elseif (! empty($wporg_slug)) {
								$url = admin_url('plugin-install.php?s=' . rawurlencode($wporg_slug) . '&tab=search&type=term');
							} else {
								$url = admin_url('plugin-install.php');
							}
						}
					} elseif (empty($cta)) {
						$cta = __('Open Module', 'dazestack-bulk-plugin-manager');
					}
					?>
					<div class="dsbpm-module-card">
						<div class="dsbpm-module-card__main">
							<div class="dsbpm-module-card__title-row">
								<div class="dsbpm-module-card__title"><?php echo esc_html($title); ?></div>
							</div>
							<?php if ($status || $tag) : ?>
								<div class="dsbpm-module-card__meta-row">
									<?php if ($status) : ?>
										<span class="dsbpm-chip <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status); ?></span>
									<?php endif; ?>
									<?php if ($tag) : ?>
										<span class="dsbpm-badge"><?php echo esc_html($tag); ?></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
							<div class="dsbpm-module-card__desc"><?php echo esc_html($desc); ?></div>
						</div>
						<div class="dsbpm-module-card__actions">
							<?php if ($url && $cta) : ?>
								<a class="button button-secondary" href="<?php echo esc_url($url); ?>"><?php echo esc_html($cta); ?></a>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php
	}

	private function render_footer()
	{
	?>
		<div class="dsbpm-footer">
			<div class="dsbpm-footer__cat" aria-hidden="true">
				<svg class="dsbpm-cat" viewBox="0 0 140 90" role="presentation">
					<g class="dsbpm-cat__float">
						<path class="dsbpm-cat__tail" d="M20 60 C 5 58 6 32 30 34" />
						<path class="dsbpm-cat__ear" d="M52 26 L40 8 L38 32 Z" />
						<path class="dsbpm-cat__ear" d="M88 26 L100 8 L102 32 Z" />
						<circle class="dsbpm-cat__face" cx="70" cy="55" r="28" />
						<circle class="dsbpm-cat__eye" cx="60" cy="52" r="4" />
						<circle class="dsbpm-cat__eye" cx="80" cy="52" r="4" />
						<circle class="dsbpm-cat__nose" cx="70" cy="60" r="3" />
						<path class="dsbpm-cat__mouth" d="M70 63 C 64 68 60 68 56 66" />
						<path class="dsbpm-cat__mouth" d="M70 63 C 76 68 80 68 84 66" />
					</g>
				</svg>
			</div>
			<div class="dsbpm-footer__links">
				<a href="<?php echo esc_url($this->tracked_url('https://dazestack.com/legal/terms-and-conditions/dazestack-wp-bulk-plugin-manager/', 'footer-terms')); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Terms &amp; Conditions', 'dazestack-bulk-plugin-manager'); ?></a>
				<a href="<?php echo esc_url($this->tracked_url('https://dazestack.com/legal/privacy-policy/dazestack-wp-bulk-plugin-manager/', 'footer-privacy')); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Privacy Policy', 'dazestack-bulk-plugin-manager'); ?></a>
			</div>
			<div class="dsbpm-footer__copy"><?php esc_html_e('Made with <span class="dsbpm-heart" aria-hidden="true">&hearts;</span> in India.', 'dazestack-bulk-plugin-manager'); ?></div>
			<div class="dsbpm-footer__copy"><?php echo wp_kses_post(sprintf(/* translators: 1: URL to brand site */__('All Rights and Trademarks reserved. &copy; 2026 <a href="%1$s" target="_blank" rel="noopener noreferrer">DazeStack</a> - DazeStack Bulk Manager - DazeStack WP Plugin Series.', 'dazestack-bulk-plugin-manager'), esc_url($this->tracked_url('https://dazestack.com', 'footer-brand')))); ?></div>
			<div class="dsbpm-footer__copy"><?php echo wp_kses_post(sprintf(/* translators: 1: URL to author site */__('Author - &copy; 2026 <a href="%1$s" target="_blank" rel="noopener noreferrer">Ashish Dungdung</a>.', 'dazestack-bulk-plugin-manager'), esc_url($this->tracked_url('http://ashishdungdung.com', 'footer-author')))); ?></div>
		</div>
		<a class="dsbpm-float-coffee" href="<?php echo esc_url($this->tracked_url('https://www.buymeacoffee.com/dazestack', 'support-float')); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Buy me a coffee', 'dazestack-bulk-plugin-manager'); ?>">
			<span class="dsbpm-float-coffee__icon" aria-hidden="true"></span>
			<span class="dsbpm-float-coffee__label"><?php esc_html_e('Buy me a coffee', 'dazestack-bulk-plugin-manager'); ?></span>
		</a>
	<?php
	}

	private function render_support_cards()
	{
	?>
		<div class="dsbpm-grid dsbpm-grid--support">
			<div class="dsbpm-card dsbpm-card--suite-card">
				<div class="dsbpm-card__title"><?php esc_html_e('DazeStack Suite', 'dazestack-bulk-plugin-manager'); ?></div>
				<div class="dsbpm-card__meta"><?php esc_html_e('Made with <span class="dsbpm-heart" aria-hidden="true">&hearts;</span> in India', 'dazestack-bulk-plugin-manager'); ?></div>
				<div class="dsbpm-card__meta"><?php echo wp_kses_post(sprintf(/* translators: 1: URL to author site */__('Author: <a href="%1$s" target="_blank" rel="noopener noreferrer">Ashish Dungdung</a>', 'dazestack-bulk-plugin-manager'), esc_url($this->tracked_url('http://ashishdungdung.com', 'support-author')))); ?></div>
				<div class="dsbpm-card__meta"><?php esc_html_e('Brand: DazeStack &middot; WP Bulk Plugin Manager', 'dazestack-bulk-plugin-manager'); ?></div>
				<div class="dsbpm-card__meta-links">
					<a href="<?php echo esc_url(admin_url('admin.php?page=dsbpm-about-legal')); ?>"><?php esc_html_e('About &amp; Legal', 'dazestack-bulk-plugin-manager'); ?></a> &middot; 
					<a href="<?php echo esc_url(admin_url('admin.php?page=dsbpm-review')); ?>"><?php esc_html_e('⭐ Review &amp; Rate Us', 'dazestack-bulk-plugin-manager'); ?></a>
				</div>
			</div>
			<div class="dsbpm-card dsbpm-card--support-card">
				<div class="dsbpm-card__title"><?php esc_html_e('Support &amp; Community', 'dazestack-bulk-plugin-manager'); ?></div>
				<p class="dsbpm-muted"><?php esc_html_e('If this manager saves you time, consider leaving a 5-star review or supporting ongoing DazeStack modules.', 'dazestack-bulk-plugin-manager'); ?></p>
				<div class="dsbpm-support-actions">
					<a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=dsbpm-review')); ?>"><?php esc_html_e('⭐ Rate 5 Stars', 'dazestack-bulk-plugin-manager'); ?></a>
					<div class="dsbpm-support-button" id="dsbpm-bmc-button">
						<a class="dsbpm-bmc-link" href="<?php echo esc_url($this->tracked_url('https://www.buymeacoffee.com/dazestack', 'support-card')); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Buy me a coffee', 'dazestack-bulk-plugin-manager'); ?></a>
					</div>
				</div>
			</div>
		</div>
<?php
	}

	public function export_csv()
	{
		if (! current_user_can('install_plugins')) {
			wp_die(__('Insufficient permissions.', 'dazestack-bulk-plugin-manager'));
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$plugins = get_plugins();
		$active  = get_option('active_plugins', array());

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=dazestack-stack.csv');

		$output = fopen('php://output', 'w');
		fputcsv($output, array('slug', 'source', 'version', 'activate', 'network'));

		foreach ($plugins as $file => $data) {
			$slug = dirname($file);
			if ('.' === $slug) {
				$slug = basename($file, '.php');
			}
			$activate = in_array($file, $active, true) ? 1 : 0;
			fputcsv(
				$output,
				array(
					$slug,
					'',
					isset($data['Version']) ? $data['Version'] : '',
					$activate,
					0,
				)
			);
		}

		exit;
	}

	public function render_about_legal_page()
	{
	?>
		<div class="dsbpm-app dsbpm-app--suite">
			<div class="dsbpm-shell">
				<div class="dsbpm-hero dsbpm-hero--suite">
					<div class="dsbpm-hero__branding">
						<div class="dsbpm-pill"><?php esc_html_e('DazeStack &middot; Made in India', 'dazestack-bulk-plugin-manager'); ?></div>
						<div class="dsbpm-hero__title"><?php esc_html_e('About &amp; Legal Documentation', 'dazestack-bulk-plugin-manager'); ?></div>
						<div class="dsbpm-hero__subtitle"><?php esc_html_e('Transparent guidelines, licensing, external service disclosures, and terms of service for DazeStack Bulk Plugin Manager.', 'dazestack-bulk-plugin-manager'); ?></div>
					</div>
				</div>

				<div class="dsbpm-grid dsbpm-grid--tight">
					<div class="dsbpm-card dsbpm-card--wide">
						<div class="dsbpm-card__title"><?php esc_html_e('About DazeStack', 'dazestack-bulk-plugin-manager'); ?></div>
						<p><?php esc_html_e('DazeStack Bulk Plugin Manager is part of the DazeStack suite of WordPress performance and automation tools created by Ashish Dungdung.', 'dazestack-bulk-plugin-manager'); ?></p>
						<p><?php esc_html_e('Built with a performance-first mindset, DSBPM leaves zero footprint on the frontend of your website. All scripts and styles are strictly scoped to administration screens.', 'dazestack-bulk-plugin-manager'); ?></p>
					</div>

					<div class="dsbpm-card dsbpm-card--wide">
						<div class="dsbpm-card__title"><?php esc_html_e('External Services & Data Privacy', 'dazestack-bulk-plugin-manager'); ?></div>
						<p class="dsbpm-muted"><?php esc_html_e('In compliance with WordPress.org guidelines, below is an exhaustive list of external services contacted by this plugin:', 'dazestack-bulk-plugin-manager'); ?></p>
						<ul class="dsbpm-list">
							<li><strong><?php esc_html_e('WordPress.org Plugins API', 'dazestack-bulk-plugin-manager'); ?></strong>: <?php esc_html_e('Fetches plugin metadata, version compatibility, and download ZIP links. Data sent: Plugin slugs.', 'dazestack-bulk-plugin-manager'); ?></li>
							<li><strong><?php esc_html_e('WPScan API (Optional)', 'dazestack-bulk-plugin-manager'); ?></strong>: <?php esc_html_e('Checks queued plugins against known vulnerability databases when an API key is provided by the user. Data sent: Plugin slugs and user API key.', 'dazestack-bulk-plugin-manager'); ?></li>
							<li><strong><?php esc_html_e('Google Sheets / DazeStack CSV Feed', 'dazestack-bulk-plugin-manager'); ?></strong>: <?php esc_html_e('Fetches curated partner tool recommendations. Data sent: None (read-only GET request with local backup fallback).', 'dazestack-bulk-plugin-manager'); ?></li>
						</ul>
					</div>

					<div class="dsbpm-card dsbpm-card--wide">
						<div class="dsbpm-card__title"><?php esc_html_e('Licensing & Trademarks', 'dazestack-bulk-plugin-manager'); ?></div>
						<p><?php echo wp_kses_post(sprintf(/* translators: 1: License link */__('DazeStack Bulk Plugin Manager is open-source software licensed under the <a href="%1$s" target="_blank" rel="noopener noreferrer">GPLv2 or later</a>.', 'dazestack-bulk-plugin-manager'), 'https://www.gnu.org/licenses/gpl-2.0.html')); ?></p>
						<p class="dsbpm-muted"><?php esc_html_e('DazeStack, the DazeStack logo, and associated brand identifiers are trademarks of Ashish Dungdung. All rights reserved.', 'dazestack-bulk-plugin-manager'); ?></p>
					</div>
				</div>

				<?php $this->render_support_cards(); ?>
				<?php $this->render_footer(); ?>
			</div>
		</div>
	<?php
	}

	public function render_review_page()
	{
	?>
		<div class="dsbpm-app dsbpm-app--suite">
			<div class="dsbpm-shell">
				<div class="dsbpm-hero dsbpm-hero--suite">
					<div class="dsbpm-hero__branding">
						<div class="dsbpm-pill"><?php esc_html_e('Community &amp; Support', 'dazestack-bulk-plugin-manager'); ?></div>
						<div class="dsbpm-hero__title"><?php esc_html_e('Enjoying DazeStack Bulk Plugin Manager?', 'dazestack-bulk-plugin-manager'); ?></div>
						<div class="dsbpm-hero__subtitle"><?php esc_html_e('Your 5-star review helps us maintain this free tool and build next-gen WordPress automation modules.', 'dazestack-bulk-plugin-manager'); ?></div>
					</div>
				</div>

				<div class="dsbpm-grid dsbpm-grid--tight">
					<div class="dsbpm-card dsbpm-card--wide dsbpm-card--review-hero">
						<div class="dsbpm-card__title"><?php esc_html_e('⭐ Leave a 5-Star Review', 'dazestack-bulk-plugin-manager'); ?></div>
						<p><?php esc_html_e('If DazeStack Bulk Plugin Manager has saved you time when building or managing WordPress sites, please consider taking 2 minutes to leave a review on WordPress.org. It means the world to our team!', 'dazestack-bulk-plugin-manager'); ?></p>
						<div class="dsbpm-review-box">
							<div class="dsbpm-review-stars">★★★★★</div>
							<a class="button button-primary button-hero" href="<?php echo esc_url($this->tracked_url('https://wordpress.org/support/plugin/dazestack-bulk-plugin-manager/reviews/#new-post', 'review-page-cta')); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e('Write a Review on WordPress.org', 'dazestack-bulk-plugin-manager'); ?>
							</a>
						</div>
					</div>

					<div class="dsbpm-card dsbpm-card--wide">
						<div class="dsbpm-card__title"><?php esc_html_e('Need Help or Found a Bug?', 'dazestack-bulk-plugin-manager'); ?></div>
						<p><?php esc_html_e('We are here to help! If you run into any issues, have feature requests, or notice something unexpected, drop a note on the support forum before leaving a review so we can assist you right away.', 'dazestack-bulk-plugin-manager'); ?></p>
						<div class="dsbpm-inline-actions">
							<a class="button button-secondary" href="<?php echo esc_url($this->tracked_url('https://wordpress.org/support/plugin/dazestack-bulk-plugin-manager/', 'support-forum')); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e('Visit Support Forum', 'dazestack-bulk-plugin-manager'); ?>
							</a>
							<a class="button button-secondary" href="<?php echo esc_url($this->tracked_url('https://dazestack.com', 'brand-website')); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e('Visit DazeStack.com', 'dazestack-bulk-plugin-manager'); ?>
							</a>
						</div>
					</div>
				</div>

				<?php $this->render_support_cards(); ?>
				<?php $this->render_footer(); ?>
			</div>
		</div>
	<?php
	}

	private function tracked_url($url, $context = '')
	{
		$base = esc_url_raw($url);
		if (empty($base)) {
			return '';
		}
		$args = array(
			'utm_source'   => 'dazestack-bulk-plugin-manager',
			'utm_medium'   => 'plugin',
			'utm_campaign' => 'wp-admin',
		);
		if (! empty($context)) {
			$args['utm_content'] = sanitize_title($context);
		}
		return esc_url(add_query_arg($args, $base));
	}

	private function get_dashboard_modules()
	{
		$modules = array(
			array(
				'id'          => 'bulk-plugin-manager',
				'title'       => __('Bulk Plugin Manager', 'dazestack-bulk-plugin-manager'),
				'description' => __('Provision faster. Install smarter. Stay lean.', 'dazestack-bulk-plugin-manager'),
				'status'      => __('Live', 'dazestack-bulk-plugin-manager'),
				'cta'         => __('Open Manager', 'dazestack-bulk-plugin-manager'),
				'url'         => admin_url('admin.php?page=' . DSBPM_SLUG),
				'tag'         => __('Installed', 'dazestack-bulk-plugin-manager'),
				'installed'   => true,
			),
		);

		return apply_filters('dsbpm_dashboard_modules', $modules);
	}
}
