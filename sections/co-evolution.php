<?php
/**
 * Front page: "The Evolution of Work" four-stage timeline.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

$co_stages = array(
	array( 'icon' => 'command', 'label' => __( 'AI Tools', 'co' ) ),
	array( 'icon' => 'people', 'label' => __( 'AI Agents', 'co' ) ),
	array( 'icon' => 'workflow', 'label' => __( 'AI-Native Workforce', 'co' ) ),
	array( 'icon' => 'monitor', 'label' => __( 'AI-Native Organization', 'co' ) ),
);
?>
<section class="co-evolution">
	<div class="container">
		<div class="co-glass co-evolution__panel">
			<h2 class="co-panel-title"><?php esc_html_e( 'The Evolution of Work', 'co' ); ?></h2>
			<ol class="co-evolution__track">
				<?php foreach ( $co_stages as $co_i => $co_stage ) : ?>
					<?php if ( $co_i > 0 ) : ?>
						<li class="co-evolution__arrow" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h15M13 6l6 6-6 6"/></svg>
						</li>
					<?php endif; ?>
					<li class="co-evolution__stage">
						<span class="co-icon co-icon--halo"><?php co_component( 'icon', array( 'name' => $co_stage['icon'] ) ); ?></span>
						<strong><?php echo esc_html( $co_stage['label'] ); ?></strong>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</div>
</section>
