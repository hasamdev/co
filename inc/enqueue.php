<?php
/**
 * Styles & scripts.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

/**
 * Version string for an asset: its modification time.
 *
 * This used to fall back to the theme version whenever WP_DEBUG was off.
 * Since the theme version is a literal in style.css that nobody remembers
 * to bump, that pinned every asset at 1.0.0 forever — edit a stylesheet and
 * returning visitors keep the old one until they hard-refresh. filemtime is
 * a stat call that PHP caches per request, so paying it always is cheaper
 * than shipping changes nobody can see.
 *
 * @param string $relative_path Path relative to the theme root.
 * @return string
 */
function co_asset_version( string $relative_path ): string {
	$file = CO_THEME_DIR . '/' . ltrim( $relative_path, '/' );

	if ( file_exists( $file ) ) {
		return (string) filemtime( $file );
	}

	return CO_THEME_VERSION;
}

add_action( 'wp_enqueue_scripts', 'co_enqueue_assets' );
/**
 * Front-end assets.
 */
function co_enqueue_assets(): void {
	// Design tokens first — everything else consumes them.
	wp_enqueue_style(
		'co-variables',
		CO_THEME_URI . '/assets/css/variables.css',
		array(),
		co_asset_version( 'assets/css/variables.css' )
	);

	wp_enqueue_style(
		'co-main',
		CO_THEME_URI . '/assets/css/main.css',
		array( 'co-variables' ),
		co_asset_version( 'assets/css/main.css' )
	);

	wp_enqueue_script(
		'co-main',
		CO_THEME_URI . '/assets/js/main.js',
		array(),
		co_asset_version( 'assets/js/main.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	// Access gate for the token-protected apps.
	if ( function_exists( 'co_app_for_query' ) && co_app_for_query() ) {
		wp_enqueue_style(
			'co-gate',
			CO_THEME_URI . '/assets/css/gate.css',
			array( 'co-main' ),
			co_asset_version( 'assets/css/gate.css' )
		);

		wp_enqueue_script(
			'co-gate',
			CO_THEME_URI . '/assets/js/gate.js',
			array(),
			co_asset_version( 'assets/js/gate.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	// Front page (Cypher-One launch layout) only.
	if ( is_front_page() ) {
		wp_enqueue_style(
			'co-front-page',
			CO_THEME_URI . '/assets/css/front-page.css',
			array( 'co-main' ),
			co_asset_version( 'assets/css/front-page.css' )
		);

		wp_enqueue_script(
			'co-roi-calculator',
			CO_THEME_URI . '/assets/js/roi-calculator.js',
			array(),
			co_asset_version( 'assets/js/roi-calculator.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
}
