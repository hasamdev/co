<?php
/**
 * Section: rich content.
 * ACF layout slug: content
 * Fields: eyebrow, heading, body (wysiwyg).
 *
 * @package co
 * @var array $args
 */

defined( 'ABSPATH' ) || exit;

$co_eyebrow = co_sub_field( 'eyebrow' );
$co_heading = co_sub_field( 'heading' );
$co_body    = co_sub_field( 'body' );

if ( ! $co_body && ! $co_heading ) {
	return;
}
?>
<section class="<?php echo co_section_classes( 'content' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>">
	<div class="container container--narrow prose">
		<?php if ( $co_eyebrow ) : ?>
			<span class="section__eyebrow"><?php echo esc_html( $co_eyebrow ); ?></span>
		<?php endif; ?>

		<?php if ( $co_heading ) : ?>
			<h2><?php echo esc_html( $co_heading ); ?></h2>
		<?php endif; ?>

		<?php echo wp_kses_post( $co_body ); ?>
	</div>
</section>
