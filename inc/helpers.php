<?php
/**
 * Template helpers.
 *
 * The three functions every template uses:
 *
 *   co_field( 'name', $default )   Safe ACF getter — never fatals if ACF is off.
 *   co_section( 'hero', $args )    Loads /sections/hero.php.
 *   co_component( 'button', $args ) Loads /components/button.php.
 *   co_page_builder()              Renders the flexible-content loop.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

/**
 * Safe ACF field getter with a default.
 *
 * @param string $name    Field name.
 * @param mixed  $default Returned when ACF is inactive or the field is empty.
 * @param mixed  $post_id Post ID, 'option', etc. Null = current post.
 * @return mixed
 */
function co_field( string $name, $default = null, $post_id = null ) {
	if ( ! co_acf_active() ) {
		return $default;
	}

	$value = get_field( $name, $post_id );

	return ( null === $value || '' === $value || false === $value ) ? $default : $value;
}

/**
 * Safe sub-field getter for use inside have_rows() loops.
 *
 * @param string $name    Sub-field name.
 * @param mixed  $default Default value.
 * @return mixed
 */
function co_sub_field( string $name, $default = null ) {
	if ( ! function_exists( 'get_sub_field' ) ) {
		return $default;
	}

	$value = get_sub_field( $name );

	return ( null === $value || '' === $value || false === $value ) ? $default : $value;
}

/**
 * Render a section partial from /sections/.
 *
 * @param string $name Section slug, e.g. 'hero'.
 * @param array  $args Data passed to the partial (available as $args).
 */
function co_section( string $name, array $args = array() ): void {
	get_template_part( 'sections/' . sanitize_file_name( $name ), null, $args );
}

/**
 * Render a component partial from /components/.
 *
 * @param string $name Component slug, e.g. 'button'.
 * @param array  $args Data passed to the partial (available as $args).
 */
function co_component( string $name, array $args = array() ): void {
	get_template_part( 'components/' . sanitize_file_name( $name ), null, $args );
}

/**
 * Render the ACF flexible-content page builder for the current post.
 *
 * Each layout maps 1:1 to a file in /sections/. Adding a new section:
 *   1. Add a layout to the "Page builder" field group (slug = file name).
 *   2. Create /sections/{slug}.php.
 * No changes here are ever needed.
 *
 * @param string $field_name Flexible content field name.
 */
function co_page_builder( string $field_name = 'sections' ): void {
	if ( ! co_acf_active() || ! have_rows( $field_name ) ) {
		return;
	}

	$index = 0;

	while ( have_rows( $field_name ) ) {
		the_row();
		++$index;

		$layout = get_row_layout();
		$file   = CO_THEME_DIR . '/sections/' . sanitize_file_name( $layout ) . '.php';

		if ( ! file_exists( $file ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				printf( '<!-- co: missing section template "%s" -->', esc_html( $layout ) );
			}
			continue;
		}

		co_section( $layout, array( 'index' => $index ) );
	}
}

/**
 * Echo a responsive image from an ACF image field (array return format).
 *
 * @param array|int|null $image ACF image value (array or attachment ID).
 * @param string         $size  Registered image size.
 * @param array          $attrs Extra attributes for wp_get_attachment_image().
 */
function co_image( $image, string $size = 'large', array $attrs = array() ): void {
	$id = is_array( $image ) ? ( $image['ID'] ?? 0 ) : (int) $image;

	if ( ! $id ) {
		return;
	}

	echo wp_get_attachment_image( $id, $size, false, $attrs ); // phpcs:ignore WordPress.Security.EscapeOutput -- core escapes.
}

/**
 * Build a BEM-ish class list for a section wrapper.
 *
 * @param string $name  Section slug.
 * @param array  $extra Extra classes.
 * @return string Escaped class attribute value.
 */
function co_section_classes( string $name, array $extra = array() ): string {
	$classes = array_merge( array( 'section', 'section--' . $name ), $extra );

	return esc_attr( implode( ' ', array_filter( array_map( 'sanitize_html_class', $classes ) ) ) );
}
