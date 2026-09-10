<?php
/**
 * Section: hero.
 * ACF layout slug: hero
 * Fields: eyebrow, heading, lede, image, buttons (repeater: button [link], style).
 *
 * @package co
 * @var array $args
 */

defined( 'ABSPATH' ) || exit;

$co_eyebrow = co_sub_field( 'eyebrow' );
$co_heading = co_sub_field( 'heading', get_the_title() );
$co_lede    = co_sub_field( 'lede' );
$co_image   = co_sub_field( 'image' );
?>
<section class="<?php echo co_section_classes( 'hero', array( $co_image ? 'is-inverse' : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>">
	<?php if ( $co_image ) : ?>
		<div class="hero__media" aria-hidden="true">
			<?php co_image( $co_image, 'co-hero', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?>
		</div>
	<?php endif; ?>

	<div class="container hero__content">
		<?php if ( $co_eyebrow ) : ?>
			<span class="section__eyebrow"><?php echo esc_html( $co_eyebrow ); ?></span>
		<?php endif; ?>

		<h1><?php echo esc_html( $co_heading ); ?></h1>

		<?php if ( $co_lede ) : ?>
			<p class="hero__lede"><?php echo esc_html( $co_lede ); ?></p>
		<?php endif; ?>

		<?php if ( have_rows( 'buttons' ) ) : ?>
			<div class="hero__actions">
				<?php
				while ( have_rows( 'buttons' ) ) :
					the_row();
					co_component(
						'button',
						array(
							'link'  => co_sub_field( 'button', array() ),
							'style' => co_sub_field( 'style', 'solid' ),
						)
					);
				endwhile;
				?>
			</div>
		<?php endif; ?>
	</div>
</section>
