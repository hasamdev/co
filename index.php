<?php
/**
 * Fallback template & blog index.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="container">
	<header class="page-header">
		<h1><?php is_home() ? bloginfo( 'name' ) : the_archive_title(); ?></h1>
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
		<p><?php esc_html_e( 'Nothing found. Try a search, or start writing.', 'co' ); ?></p>
		<?php get_search_form(); ?>
	<?php endif; ?>
</div>

<?php
get_footer();
