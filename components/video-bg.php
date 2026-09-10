<?php
/**
 * Component: full-bleed background video layer.
 *
 * Usage: co_component( 'video-bg', array( 'src' => 'https://.../bg.mp4' ) );
 * Renders absolutely positioned behind the parent (parent needs position:relative).
 * Hidden automatically for users with prefers-reduced-motion (see front-page.css).
 *
 * @package co
 * @var array $args
 */

defined( 'ABSPATH' ) || exit;

$co_src = $args['src'] ?? '';

if ( ! $co_src ) {
	return;
}
?>
<div class="co-video-bg" aria-hidden="true">
	<video
		src="<?php echo esc_url( $co_src ); ?>"
		autoplay
		muted
		loop
		playsinline
		preload="auto"
		tabindex="-1"
	></video>
</div>
