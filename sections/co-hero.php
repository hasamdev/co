<?php
/**
 * Front page: hero with brand mark, headline and partner logo strip.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

$co_heading = co_field( 'co_hero_heading', __( 'The Future of Work Is Being Built Today', 'co' ) );
$co_sub     = co_field( 'co_hero_subheading', __( 'We help organizations build the skills, workflows, governance, and operating models needed to compete in the AI era.', 'co' ) );
?>
<section class="co-hero">
	<div class="container co-hero__inner">

		<?php if ( has_custom_logo() ) : ?>
			<?php the_custom_logo(); ?>
		<?php else : ?>
			<a class="site-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<?php bloginfo( 'name' ); ?>
			</a>
		<?php endif; ?>

		<h1 class="co-hero__title"><?php echo esc_html( $co_heading ); ?></h1>

		<p class="co-hero__sub"><?php echo esc_html( $co_sub ); ?></p>

		<?php
		/* Partner logo ticker.
		   Drop approved SVG/PNG marks into the "Partner logos" ACF gallery,
		   or the placeholders below render. The track is duplicated (second
		   copy aria-hidden) so the CSS marquee loops seamlessly. */
		$co_logos = co_field( 'co_partner_logos', array() );
		?>
		<div class="co-logos" role="group" aria-label="<?php esc_attr_e( 'Technology partners', 'co' ); ?>">
			<div class="co-logos__track">
				<?php for ( $co_copy = 0; $co_copy < 2; $co_copy++ ) : ?>
					<ul class="co-logos__group" <?php echo $co_copy ? 'aria-hidden="true"' : ''; ?>>
						<?php
						if ( $co_logos && is_array( $co_logos ) ) :
							foreach ( $co_logos as $co_logo ) :
								?>
								<li><?php co_image( $co_logo, 'medium', array( 'loading' => 'lazy' ) ); ?></li>
								<?php
							endforeach;
						else :
							for ( $co_i = 1; $co_i <= 6; $co_i++ ) :
								?>
								<li><span class="co-logos__slot" aria-hidden="true"></span></li>
								<?php
							endfor;
						endif;
						?>
					</ul>
				<?php endfor; ?>
			</div>
		</div>
	</div>
</section>
