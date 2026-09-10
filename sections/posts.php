<?php
/**
 * Section: latest posts.
 * ACF layout slug: posts
 * Fields: heading, count (number).
 *
 * @package co
 * @var array $args
 */

defined( 'ABSPATH' ) || exit;

$co_heading = co_sub_field( 'heading', __( 'Latest stories', 'co' ) );
$co_count   = (int) co_sub_field( 'count', 3 );

$co_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => max( 1, min( $co_count, 12 ) ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

if ( ! $co_query->have_posts() ) {
	return;
}
?>
<section class="<?php echo co_section_classes( 'posts' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>">
	<div class="container">
		<header class="section__intro">
			<h2><?php echo esc_html( $co_heading ); ?></h2>
		</header>

		<div class="posts-grid">
			<?php
			while ( $co_query->have_posts() ) :
				$co_query->the_post();
				co_component( 'card', array( 'post_id' => get_the_ID() ) );
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
