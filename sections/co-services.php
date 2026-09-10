<?php
/**
 * Front page: "How We Help Organizations" five-column grid.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

$co_items = array(
	array(
		'icon'  => 'command',
		'title' => __( 'AI Strategy', 'co' ),
		'text'  => __( 'Roadmaps and priorities aligned to business outcomes.', 'co' ),
	),
	array(
		'icon'  => 'people',
		'title' => __( 'Capability Building', 'co' ),
		'text'  => __( 'Develop AI-ready teams and leaders.', 'co' ),
	),
	array(
		'icon'  => 'workflow',
		'title' => __( 'Workflow Transformation', 'co' ),
		'text'  => __( 'Redesign workflows for efficiency, quality, and scale.', 'co' ),
	),
	array(
		'icon'  => 'monitor',
		'title' => __( 'AI Governance', 'co' ),
		'text'  => __( 'Manage risk, ensure compliance, and enable responsible AI.', 'co' ),
	),
	array(
		'icon'  => 'shield-check',
		'title' => __( 'Executive Advisory', 'co' ),
		'text'  => __( 'Partner with leadership to drive change and deliver impact.', 'co' ),
	),
);
?>
<section class="co-services">
	<div class="container">
		<div class="co-glass co-services__panel">
			<h2 class="co-panel-title"><?php esc_html_e( 'How We Help Organizations', 'co' ); ?></h2>
			<ul class="co-services__grid">
				<?php foreach ( $co_items as $co_item ) : ?>
					<li class="co-services__item">
						<span class="co-icon co-icon--round"><?php co_component( 'icon', array( 'name' => $co_item['icon'] ) ); ?></span>
						<strong><?php echo esc_html( $co_item['title'] ); ?></strong>
						<small><?php echo esc_html( $co_item['text'] ); ?></small>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>
