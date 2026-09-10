<?php
/**
 * Site header.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="site">
	<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'co' ); ?></a>

	<header class="site-header">
		<div class="container site-header__inner">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="site-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<?php bloginfo( 'name' ); ?>
				</a>
			<?php endif; ?>

			<?php
			co_component(
				'nav',
				array(
					'location' => 'primary',
					'label'    => __( 'Primary navigation', 'co' ),
				)
			);
			?>
		</div>
	</header>

	<main id="main" class="site-main">
