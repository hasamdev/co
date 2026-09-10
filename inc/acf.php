<?php
/**
 * ACF integration.
 *
 * - Local JSON save/load (fields live in Git, sync across environments).
 * - Global "Site settings" options page.
 * - Admin notice + graceful degradation when ACF is not active.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether ACF (free or Pro) is active.
 */
function co_acf_active(): bool {
	return function_exists( 'get_field' );
}

// Save field groups to /acf-json when edited in the admin.
add_filter(
	'acf/settings/save_json',
	static function (): string {
		return CO_THEME_DIR . '/acf-json';
	}
);

// Load field groups from /acf-json (enables the "Sync" UI).
add_filter(
	'acf/settings/load_json',
	static function ( array $paths ): array {
		$paths[] = CO_THEME_DIR . '/acf-json';
		return array_unique( $paths );
	}
);

add_action( 'acf/init', 'co_register_options_page' );
/**
 * Global site settings page (ACF Pro only — silently skipped on free).
 */
function co_register_options_page(): void {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

	acf_add_options_page(
		array(
			'page_title' => __( 'Site settings', 'co' ),
			'menu_title' => __( 'Site settings', 'co' ),
			'menu_slug'  => 'co-site-settings',
			'capability' => 'manage_options',
			'redirect'   => false,
			'position'   => 61,
			'icon_url'   => 'dashicons-admin-generic',
		)
	);
}

add_action( 'admin_notices', 'co_acf_missing_notice' );
/**
 * Warn administrators when ACF is inactive.
 */
function co_acf_missing_notice(): void {
	if ( co_acf_active() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html__( 'Cypher One: Advanced Custom Fields (Pro recommended) is not active. The page builder and site settings will be unavailable until it is installed and activated.', 'co' )
	);
}

// Hide the ACF admin UI on production so fields are only edited in dev.
add_filter(
	'acf/settings/show_admin',
	static function ( bool $show ): bool {
		if ( defined( 'WP_ENVIRONMENT_TYPE' ) && 'production' === wp_get_environment_type() ) {
			return false;
		}
		return $show;
	}
);
