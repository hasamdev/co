<?php
/**
 * Component: navigation menu.
 *
 * Usage: co_component( 'nav', array( 'location' => 'primary', 'label' => 'Primary navigation' ) );
 *
 * @package co
 * @var array $args
 */

defined( 'ABSPATH' ) || exit;

$co_location = $args['location'] ?? 'primary';
$co_label    = $args['label'] ?? __( 'Navigation', 'co' );

if ( ! has_nav_menu( $co_location ) ) {
	return;
}
?>
<nav class="nav nav--<?php echo esc_attr( $co_location ); ?>" aria-label="<?php echo esc_attr( $co_label ); ?>" data-nav>
	<?php
	wp_nav_menu(
		array(
			'theme_location' => $co_location,
			'menu_class'     => 'nav__list',
			'container'      => false,
			'depth'          => 2,
			'fallback_cb'    => false,
		)
	);
	?>
</nav>
