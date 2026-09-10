<?php
/**
 * Hardening & head cleanup.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

// Remove WP version from the head and feeds.
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

// Trim legacy head cruft.
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );

// Disable XML-RPC (fronted brute-force vector). Remove if a client needs Jetpack/legacy apps.
add_filter( 'xmlrpc_enabled', '__return_false' );

// Don't expose usernames via REST users endpoint to logged-out visitors.
add_filter(
	'rest_endpoints',
	static function ( array $endpoints ): array {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}
		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
		return $endpoints;
	}
);

// Disable file editing from the dashboard (belt-and-braces; ideally set in wp-config.php).
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

// Vague login errors — don't confirm whether the username or password failed.
add_filter(
	'login_errors',
	static function (): string {
		return esc_html__( 'Invalid login credentials.', 'co' );
	}
);
