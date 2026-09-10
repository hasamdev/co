<?php
/**
 * Component: post card.
 *
 * Usage: co_component( 'card', array( 'post_id' => 123 ) );
 *
 * @package co
 * @var array $args
 */

defined( 'ABSPATH' ) || exit;

$co_post_id = $args['post_id'] ?? get_the_ID();

if ( ! $co_post_id ) {
	return;
}
?>
<article class="card">
	<?php if ( has_post_thumbnail( $co_post_id ) ) : ?>
		<a class="card__media" href="<?php echo esc_url( get_permalink( $co_post_id ) ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo get_the_post_thumbnail( $co_post_id, 'co-card', array( 'loading' => 'lazy' ) ); ?>
		</a>
	<?php endif; ?>

	<div class="card__body">
		<p class="card__meta"><?php echo esc_html( get_the_date( '', $co_post_id ) ); ?></p>
		<h2 class="card__title">
			<a href="<?php echo esc_url( get_permalink( $co_post_id ) ); ?>">
				<?php echo esc_html( get_the_title( $co_post_id ) ); ?>
			</a>
		</h2>
		<p><?php echo esc_html( wp_trim_words( get_the_excerpt( $co_post_id ), 22 ) ); ?></p>
	</div>
</article>
