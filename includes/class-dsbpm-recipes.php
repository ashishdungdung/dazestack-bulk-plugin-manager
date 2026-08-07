<?php

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Manages stack recipes (playbooks) and WP-CLI command generation.
 */
class DSBPM_Recipes
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

	public function get_builtin_recipes()
	{
		return array(
			array(
				'id'          => 'ecommerce-starter',
				'title'       => __('WooCommerce Starter', 'dazestack-bulk-plugin-manager'),
				'description' => __('Essential stack for launching an e-commerce store with payments and email integration.', 'dazestack-bulk-plugin-manager'),
				'badge'       => __('E-Commerce', 'dazestack-bulk-plugin-manager'),
				'isBuiltin'   => true,
				'items'       => array(
					array('slug' => 'woocommerce', 'activate' => true),
					array('slug' => 'woocommerce-services', 'activate' => true),
					array('slug' => 'mailchimp-for-woocommerce', 'activate' => true),
					array('slug' => 'contact-form-7', 'activate' => true),
				),
			),
			array(
				'id'          => 'performance-cache',
				'title'       => __('Performance & Speed Suite', 'dazestack-bulk-plugin-manager'),
				'description' => __('Top-rated caching, asset minification, and image optimization plugins.', 'dazestack-bulk-plugin-manager'),
				'badge'       => __('Performance', 'dazestack-bulk-plugin-manager'),
				'isBuiltin'   => true,
				'items'       => array(
					array('slug' => 'autoptimize', 'activate' => true),
					array('slug' => 'wp-super-cache', 'activate' => true),
					array('slug' => 'shortpixel-image-optimiser', 'activate' => true),
				),
			),
			array(
				'id'          => 'security-hardening',
				'title'       => __('Security & Hardening Suite', 'dazestack-bulk-plugin-manager'),
				'description' => __('Firewall, malware scanner, and activity log stack for hardening WordPress.', 'dazestack-bulk-plugin-manager'),
				'badge'       => __('Security', 'dazestack-bulk-plugin-manager'),
				'isBuiltin'   => true,
				'items'       => array(
					array('slug' => 'wordfence', 'activate' => true),
					array('slug' => 'wp-security-audit-log', 'activate' => true),
					array('slug' => 'limit-login-attempts-reloaded', 'activate' => true),
				),
			),
			array(
				'id'          => 'seo-mastery',
				'title'       => __('SEO & Content Suite', 'dazestack-bulk-plugin-manager'),
				'description' => __('Comprehensive SEO, XML sitemaps, and rich snippet schema tools.', 'dazestack-bulk-plugin-manager'),
				'badge'       => __('SEO', 'dazestack-bulk-plugin-manager'),
				'isBuiltin'   => true,
				'items'       => array(
					array('slug' => 'wordpress-seo', 'activate' => true),
					array('slug' => 'schema', 'activate' => true),
					array('slug' => 'redirection', 'activate' => true),
				),
			),
		);
	}

	public function get_saved_recipes()
	{
		$recipes = get_option('dsbpm_recipes', array());
		return is_array($recipes) ? $recipes : array();
	}

	public function get_all_recipes()
	{
		$builtin = $this->get_builtin_recipes();
		$saved   = $this->get_saved_recipes();
		return array_merge($builtin, $saved);
	}

	public function save_recipe($title, $description, $items)
	{
		$title       = sanitize_text_field($title);
		$description = sanitize_textarea_field($description);

		if (empty($title)) {
			return new WP_Error('dsbpm_recipe_no_title', __('Recipe title is required.', 'dazestack-bulk-plugin-manager'));
		}

		if (! is_array($items) || empty($items)) {
			return new WP_Error('dsbpm_recipe_no_items', __('Recipe must contain at least one plugin.', 'dazestack-bulk-plugin-manager'));
		}

		$clean_items = array();
		foreach ($items as $item) {
			$slug = isset($item['slug']) ? sanitize_key($item['slug']) : '';
			if (empty($slug)) {
				continue;
			}
			$clean_items[] = array(
				'slug'     => $slug,
				'source'   => isset($item['source']) ? esc_url_raw($item['source']) : '',
				'version'  => isset($item['version']) ? sanitize_text_field($item['version']) : '',
				'activate' => ! empty($item['activate']),
				'network'  => ! empty($item['network']),
				'force'    => ! empty($item['force']),
			);
		}

		if (empty($clean_items)) {
			return new WP_Error('dsbpm_recipe_invalid_items', __('No valid plugin slugs found in recipe.', 'dazestack-bulk-plugin-manager'));
		}

		$recipes = $this->get_saved_recipes();
		$id      = 'recipe_' . time() . '_' . wp_rand(100, 999);

		$recipe = array(
			'id'          => $id,
			'title'       => $title,
			'description' => $description,
			'badge'       => __('Custom', 'dazestack-bulk-plugin-manager'),
			'isBuiltin'   => false,
			'items'       => $clean_items,
			'created'     => current_time('mysql'),
		);

		$recipes[] = $recipe;
		update_option('dsbpm_recipes', $recipes);

		return $recipe;
	}

	public function delete_recipe($id)
	{
		$id      = sanitize_key($id);
		$recipes = $this->get_saved_recipes();
		$filtered = array();

		foreach ($recipes as $recipe) {
			if (isset($recipe['id']) && $recipe['id'] === $id) {
				continue;
			}
			$filtered[] = $recipe;
		}

		update_option('dsbpm_recipes', $filtered);
		return true;
	}

	public function generate_wp_cli($items)
	{
		if (empty($items) || ! is_array($items)) {
			return '';
		}

		$install_slugs  = array();
		$activate_slugs = array();

		foreach ($items as $item) {
			$slug = '';
			if (is_string($item)) {
				$slug = sanitize_key($item);
			} elseif (is_array($item) && ! empty($item['slug'])) {
				$slug = sanitize_key($item['slug']);
			}

			if (empty($slug)) {
				continue;
			}

			$install_slugs[] = $slug;
			if (is_array($item) && ! empty($item['activate'])) {
				$activate_slugs[] = $slug;
			}
		}

		if (empty($install_slugs)) {
			return '';
		}

		$cmd = 'wp plugin install ' . implode(' ', $install_slugs);
		if (! empty($activate_slugs)) {
			if (count($activate_slugs) === count($install_slugs)) {
				$cmd .= ' --activate';
			} else {
				$cmd .= ' && wp plugin activate ' . implode(' ', $activate_slugs);
			}
		}

		return $cmd;
	}
}
