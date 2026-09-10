<?php
/**
 * Theme setup: supports, menus, image sizes.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'co_setup' );
/**
 * Register theme supports and nav menus.
 */
function co_setup(): void {
	load_theme_textdomain( 'co', CO_THEME_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'custom-logo',
		array(
			'height'               => 80,
			'width'                => 240,
			'flex-height'          => true,
			'flex-width'           => true,
			'unlink-homepage-logo' => true,
		)
	);
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);

	// Editor styles so the block editor mirrors the front end tokens.
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/variables.css', 'assets/css/editor.css' ) );

	register_nav_menus(
		array(
			'primary' => __( 'Primary navigation', 'co' ),
			'footer'  => __( 'Footer navigation', 'co' ),
		)
	);

	// Content-width sizes used by sections.
	add_image_size( 'co-hero', 1920, 1080, true );
	add_image_size( 'co-card', 800, 600, true );
}

add_action( 'after_setup_theme', 'co_content_width', 0 );
/**
 * Set the global content width (used by oEmbeds).
 */
function co_content_width(): void {
	$GLOBALS['content_width'] = apply_filters( 'co_content_width', 1200 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
}
