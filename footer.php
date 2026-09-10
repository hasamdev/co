<?php
/**
 * Site footer.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

$co_footer_text = co_field(
	'footer_text',
	sprintf(
		/* translators: 1: year, 2: site name. */
		__( '© %1$s %2$s. All rights reserved.', 'co' ),
		gmdate( 'Y' ),
		get_bloginfo( 'name' )
	),
	'option'
);
?>
	</main>

	<footer class="site-footer is-inverse">
		<div class="container site-footer__inner">
			<?php if ( has_custom_logo() ) : ?>
				<div class="site-footer__logo">
					<?php the_custom_logo(); ?>
				</div>
			<?php else: ?>
				<a class="site-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<?php bloginfo( 'name' ); ?>
				</a>
			<?php endif; ?>

			<p><?php echo wp_kses_post( $co_footer_text ); ?></p>

			<?php
			co_component(
				'nav',
				array(
					'location' => 'footer',
					'label'    => __( 'Footer navigation', 'co' ),
				)
			);
			?>
		</div>
	</footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
