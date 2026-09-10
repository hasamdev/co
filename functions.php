<?php
/**
 * Cypher One bootstrap.
 *
 * Keep this file thin. All functionality lives in /inc/,
 * loaded in dependency order below.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

define( 'CO_THEME_VERSION', wp_get_theme()->get( 'Version' ) );
define( 'CO_THEME_DIR', get_template_directory() );
define( 'CO_THEME_URI', get_template_directory_uri() );

$co_includes = array(
	'inc/setup.php',      // Theme supports, menus, image sizes.
	'inc/enqueue.php',    // Styles & scripts.
	'inc/acf.php',        // ACF JSON sync, options page, dependency notice.
	'inc/helpers.php',    // Template helpers (co_field, co_section, co_component...).
	'inc/security.php',   // Hardening & head cleanup.
	'inc/launch-form.php', // Front-page launch-list signup handler.
	'inc/access.php',       // Token store & session for the gated apps.
	'inc/apps.php',         // Gated app registry, routing and serving.
	'inc/access-request.php', // "Request access" relay on the gate page.
	'inc/admin-tokens.php', // Tools → App access.
);

foreach ( $co_includes as $co_file ) {
	$co_path = CO_THEME_DIR . '/' . $co_file;
	if ( file_exists( $co_path ) ) {
		require_once $co_path;
	}
}
unset( $co_includes, $co_file, $co_path );
