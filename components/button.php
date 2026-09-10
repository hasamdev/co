<?php
/**
 * Component: button.
 *
 * Usage:
 *   co_component( 'button', array( 'label' => ..., 'url' => ..., 'style' => 'ghost', 'new_tab' => true ) );
 *   co_component( 'button', array( 'link' => $acf_link_array ) ); // ACF link field.
 *
 * @package co
 * @var array $args
 */

defined( 'ABSPATH' ) || exit;

$co_link = $args['link'] ?? array();

$co_label   = $args['label'] ?? ( $co_link['title'] ?? '' );
$co_url     = $args['url'] ?? ( $co_link['url'] ?? '' );
$co_target  = ! empty( $args['new_tab'] ) || ( ! empty( $co_link['target'] ) && '_blank' === $co_link['target'] );
$co_style   = $args['style'] ?? 'solid';
$co_classes = 'button' . ( 'ghost' === $co_style ? ' button--ghost' : '' );

if ( ! $co_label || ! $co_url ) {
	return;
}
?>
<a
	class="<?php echo esc_attr( $co_classes ); ?>"
	href="<?php echo esc_url( $co_url ); ?>"
	<?php if ( $co_target ) : ?>
		target="_blank" rel="noopener noreferrer"
	<?php endif; ?>
>
	<?php echo esc_html( $co_label ); ?>
</a>
