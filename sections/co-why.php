<?php
/**
 * Front page: "Why Cypher-One" glass trust bar.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

$co_items = array(
	array(
		'icon'  => 'cursor',
		'title' => __( 'Strategy First', 'co' ),
		'text'  => __( 'Business-first approach focused on outcomes.', 'co' ),
	),
	array(
		'icon'  => 'shield',
		'title' => __( 'Governance by Design', 'co' ),
		'text'  => __( 'Embedding responsible AI from day one.', 'co' ),
	),
	array(
		'icon'  => 'people',
		'title' => __( 'People & Capability', 'co' ),
		'text'  => __( 'Building the skills and confidence to scale AI.', 'co' ),
	),
	array(
		'icon'  => 'spark',
		'title' => __( 'Transformation That Lasts', 'co' ),
		'text'  => __( 'Driving measurable impact across the organization.', 'co' ),
	),
);
?>
<section class="co-why">
	<div class="container">
		<div class="co-glass co-why__panel">
			<h2 class="co-panel-title"><?php esc_html_e( 'Why Cypher-One', 'co' ); ?></h2>
			<ul class="co-why__grid">
				<?php foreach ( $co_items as $co_item ) : ?>
					<li class="co-why__item">
						<span class="co-icon co-icon--ink"><?php co_component( 'icon', array( 'name' => $co_item['icon'] ) ); ?></span>
						<span>
							<strong><?php echo esc_html( $co_item['title'] ); ?></strong>
							<small><?php echo esc_html( $co_item['text'] ); ?></small>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>
