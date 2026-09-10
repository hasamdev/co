<?php
/**
 * Section: call to action.
 * ACF layout slug: cta
 * Fields: heading, text, button (link).
 *
 * @package co
 * @var array $args
 */

defined( 'ABSPATH' ) || exit;

$co_heading = co_sub_field( 'heading' );
$co_text    = co_sub_field( 'text' );
$co_button  = co_sub_field( 'button', array() );

if ( ! $co_heading ) {
	return;
}
?>
<section class="<?php echo co_section_classes( 'cta' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>">
	<div class="container">
		<div class="cta__panel is-inverse">
			<h2><?php echo esc_html( $co_heading ); ?></h2>

			<?php if ( $co_text ) : ?>
				<p><?php echo esc_html( $co_text ); ?></p>
			<?php endif; ?>

			<?php co_component( 'button', array( 'link' => $co_button ) ); ?>
		</div>
	</div>
</section>
