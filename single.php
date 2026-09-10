<?php
/**
 * Single post.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'section section--content' ); ?>>
		<div class="container container--narrow">
			<header class="page-header">
				<p class="card__meta">
					<?php echo esc_html( get_the_date() ); ?>
					<?php if ( get_the_category_list( ', ' ) ) : ?>
						&middot; <?php echo wp_kses_post( get_the_category_list( ', ' ) ); ?>
					<?php endif; ?>
				</p>
				<h1><?php the_title(); ?></h1>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure>
					<?php the_post_thumbnail( 'large', array( 'loading' => 'eager' ) ); ?>
				</figure>
			<?php endif; ?>

			<div class="prose">
				<?php the_content(); ?>
			</div>
		</div>
	</article>
	<?php
	if ( comments_open() || get_comments_number() ) {
		echo '<div class="container container--narrow">';
		comments_template();
		echo '</div>';
	}
endwhile;

get_footer();
