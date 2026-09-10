<?php
/**
 * Default page template — renders the ACF page builder.
 * Falls back to editor content when no sections exist.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	if ( co_acf_active() && have_rows( 'sections' ) ) {
		co_page_builder();
	} else {
		?>
		<article <?php post_class( 'section section--content' ); ?>>
			<div class="container container--narrow">
				<header class="page-header">
					<h1><?php the_title(); ?></h1>
				</header>
				<div class="prose">
					<?php the_content(); ?>
				</div>
			</div>
		</article>
		<?php
	}

endwhile;

get_footer();
