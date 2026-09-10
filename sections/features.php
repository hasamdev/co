<?php
/**
 * Section: features grid.
 * ACF layout slug: features
 * Fields: eyebrow, heading, intro, items (repeater: title, text).
 *
 * @package co
 * @var array $args
 */

defined( 'ABSPATH' ) || exit;

$co_eyebrow = co_sub_field( 'eyebrow' );
$co_heading = co_sub_field( 'heading' );
$co_intro   = co_sub_field( 'intro' );

if ( ! have_rows( 'items' ) ) {
	return;
}
?>
<section class="<?php echo co_section_classes( 'features' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>">
	<div class="container">
		<?php if ( $co_eyebrow || $co_heading || $co_intro ) : ?>
			<header class="section__intro">
				<?php if ( $co_eyebrow ) : ?>
					<span class="section__eyebrow"><?php echo esc_html( $co_eyebrow ); ?></span>
				<?php endif; ?>
				<?php if ( $co_heading ) : ?>
					<h2><?php echo esc_html( $co_heading ); ?></h2>
				<?php endif; ?>
				<?php if ( $co_intro ) : ?>
					<p><?php echo esc_html( $co_intro ); ?></p>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<div class="features__grid">
			<?php
			while ( have_rows( 'items' ) ) :
				the_row();
				$co_title = co_sub_field( 'title' );
				$co_text  = co_sub_field( 'text' );
				?>
				<div class="feature">
					<?php if ( $co_title ) : ?>
						<h3 class="feature__title"><?php echo esc_html( $co_title ); ?></h3>
					<?php endif; ?>
					<?php if ( $co_text ) : ?>
						<p><?php echo esc_html( $co_text ); ?></p>
					<?php endif; ?>
				</div>
			<?php endwhile; ?>
		</div>
	</div>
</section>
