<?php
/**
 * Archives (category, tag, date, author, CPT).
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="container">
	<header class="page-header">
		<h1><?php the_archive_title(); ?></h1>
		<?php the_archive_description( '<div class="section__intro">', '</div>' ); ?>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="posts-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				co_component( 'card', array( 'post_id' => get_the_ID() ) );
			endwhile;
			?>
		</div>

		<nav class="pagination" aria-label="<?php esc_attr_e( 'Posts navigation', 'co' ); ?>">
			<?php echo wp_kses_post( paginate_links( array( 'type' => 'plain' ) ) ); ?>
		</nav>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing found in this archive.', 'co' ); ?></p>
	<?php endif; ?>
</div>

<?php
get_footer();
