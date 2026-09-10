<?php
/**
 * Front page: ROI calculator with live sliders.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

$co_heading = co_field( 'co_calc_heading', __( "What's the True Cost of Missing Out on AI Training, Workflow Optimization & Governance?", 'co' ) );
$co_sub     = co_field( 'co_calc_subheading', __( 'Estimate the impact of better skills, smarter workflows, and strong governance.', 'co' ) );
?>
<section class="co-calc">
	<div class="container">
		<h2 class="co-section-title"><?php echo esc_html( $co_heading ); ?></h2>
		<p class="co-section-sub"><?php echo esc_html( $co_sub ); ?></p>

		<div class="co-glass co-calc__panel" data-roi-calculator>
			<div class="co-calc__row">
				<div class="co-calc__labels">
					<label for="co-team"><?php esc_html_e( 'Team size', 'co' ); ?></label>
					<output for="co-team" data-roi-out="team">12</output>
				</div>
				<input type="range" id="co-team" data-roi="team" min="1" max="100" step="1" value="12">
			</div>

			<div class="co-calc__row">
				<div class="co-calc__labels">
					<label for="co-hours"><?php esc_html_e( 'Hours', 'co' ); ?></label>
					<output for="co-hours" data-roi-out="hours">10</output>
				</div>
				<p class="co-calc__hint"><?php esc_html_e( 'How many hours per week each team member spends on manual tasks (estimated).', 'co' ); ?></p>
				<input type="range" id="co-hours" data-roi="hours" min="1" max="40" step="1" value="10">
			</div>

			<div class="co-calc__row">
				<div class="co-calc__labels">
					<label for="co-cost"><?php esc_html_e( 'Avg. hourly cost of team member', 'co' ); ?></label>
					<output for="co-cost">$&nbsp;<span data-roi-out="cost">40</span></output>
				</div>
				<input type="range" id="co-cost" data-roi="cost" min="10" max="250" step="5" value="40">
			</div>

			<div class="co-calc__results" aria-live="polite">
				<p>
					<?php esc_html_e( 'Your team is spending', 'co' ); ?>
					~<strong data-roi-out="monthly-hours">480</strong> <?php esc_html_e( 'hours/month', 'co' ); ?>
					<?php esc_html_e( 'on work that could be streamlined with better workflows, AI skills, and clearer governance.', 'co' ); ?>
				</p>
				<p>
					<?php esc_html_e( "That's", 'co' ); ?>
					<strong data-roi-out="monthly-cost">$19,200</strong><?php esc_html_e( '/month in productivity being lost due to inefficient processes and limited AI adoption.', 'co' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'With the right training, workflow redesign, and governance in place, your team could reclaim up to 80% of that time — saving', 'co' ); ?>
					~<strong data-roi-out="savings">$15,360</strong><?php esc_html_e( '/month.', 'co' ); ?>
				</p>
			</div>
		</div>
	</div>
</section>
