# DazeStack Dashboard Kit

Purpose
- Make future DazeStack plugins feel consistent with the dashboard design language.
- Register new plugins inside the DazeStack Dashboard module list.

## 1) Add your plugin to the DazeStack Dashboard list
In your new plugin, register a module card with the `dsbpm_dashboard_modules` filter.

```php
add_filter( 'dsbpm_dashboard_modules', function ( $modules ) {
	$modules[] = array(
		'id'          => 'my-plugin-slug',
		'title'       => 'My Plugin Name',
		'description' => 'Short, friendly summary of what the module does.',
		'status'      => 'Live',
		'cta'         => 'Open Module',
		'url'         => admin_url( 'admin.php?page=my-plugin-slug' ),
		'tag'         => 'Installed',
	);
	return $modules;
} );
```

Supported keys
- `id`: unique slug for the module.
- `title`: module name shown in the card.
- `description`: short blurb shown under the title.
- `status`: badge text (ex: Live, Coming Soon).
- `cta`: button label.
- `url`: admin page link for the module.
- `tag`: small secondary label (ex: Installed, New).
- `installed`: boolean flag; when false, the CTA defaults to "Install Plugin".
- `install_url`: optional install link used when `installed` is false.
- `wporg_slug`: optional slug used to build a WP.org install search link.

## 2) Add your menu under the DazeStack suite
Use the shared base slug so your module appears under the same DazeStack menu.

```php
add_submenu_page(
	'dazestack-suite',
	'My Plugin',
	'My Plugin',
	'install_plugins',
	'my-plugin-slug',
	'my_plugin_render_page'
);
```

## 3) Reuse the DazeStack admin design language
Copy the base styles from `assets/admin.css` and keep these class patterns:
- `dsbpm-app`, `dsbpm-shell`, `dsbpm-hero`
- `dsbpm-card`, `dsbpm-card__title`, `dsbpm-grid`
- `dsbpm-pill`, `dsbpm-chip`, `dsbpm-button`

Minimal layout template
```html
<div class="dsbpm-app">
	<div class="dsbpm-shell">
		<div class="dsbpm-hero">
			<div class="dsbpm-hero__branding">
				<div class="dsbpm-pill">DazeStack Module</div>
				<div class="dsbpm-hero__title">My Plugin</div>
				<div class="dsbpm-hero__subtitle">One-line description of the module.</div>
			</div>
		</div>
		<div class="dsbpm-grid">
			<div class="dsbpm-card">
				<div class="dsbpm-card__title">Primary Block</div>
				<p class="dsbpm-muted">Describe the main workflow.</p>
			</div>
		</div>
	</div>
</div>
```

## 4) Notes
- Keep spacing, typography, and card structure consistent to maintain the DazeStack look.
- Use the shared `dsbpm-card--lead` class on the first card when you need extra breathing room.
