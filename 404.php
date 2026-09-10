<?php
/**
 * 404 template.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="section">
	<div class="container container--narrow">
		<span class="section__eyebrow">404</span>
		<h1><?php esc_html_e( 'This page has wandered off', 'co' ); ?></h1>
		<p><?php esc_html_e( 'The address may have changed, or the page no longer exists. Search below or head back home.', 'co' ); ?></p>
		<?php get_search_form(); ?>
		<p>
			<?php
			co_component(
				'button',
				array(
					'label' => __( 'Back to home', 'co' ),
					'url'   => home_url( '/' ),
				)
			);
			?>
		</p>
	</div>
</section>

<?php
get_footer();
