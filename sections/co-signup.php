<?php
/**
 * Front page: launch-list signup on the dark band.
 * Posts to admin-post.php (handler in inc/launch-form.php).
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

$co_heading = co_field( 'co_signup_heading', __( 'Stay Posted on our Launch', 'co' ) );
$co_status  = isset( $_GET['launch-list'] ) ? sanitize_key( wp_unslash( $_GET['launch-list'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag.
?>
<section class="co-signup" id="launch-list">
	<div class="co-signup__card">
		<h2><?php echo esc_html( $co_heading ); ?></h2>

		<?php if ( 'ok' === $co_status ) : ?>
			<p class="co-signup__notice co-signup__notice--ok" role="status">
				<?php esc_html_e( "You're on the list. We'll be in touch.", 'co' ); ?>
			</p>
		<?php elseif ( 'error' === $co_status ) : ?>
			<p class="co-signup__notice co-signup__notice--error" role="alert">
				<?php esc_html_e( 'Please check your details and try again.', 'co' ); ?>
			</p>
		<?php endif; ?>

		<form class="co-signup__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="co_launch_signup">
			<?php wp_nonce_field( 'co_launch_signup', 'co_launch_nonce' ); ?>
			<div class="co-signup__hp" aria-hidden="true">
				<label>Leave this field empty<input type="text" name="co_hp" tabindex="-1" autocomplete="off"></label>
			</div>

			<div class="co-signup__cols">
				<p class="co-field">
					<label for="co-first"><?php esc_html_e( 'First Name', 'co' ); ?></label>
					<input type="text" id="co-first" name="first_name" autocomplete="given-name" placeholder="<?php esc_attr_e( 'Enter your first name', 'co' ); ?>">
				</p>
				<p class="co-field">
					<label for="co-last"><?php esc_html_e( 'Last Name', 'co' ); ?></label>
					<input type="text" id="co-last" name="last_name" autocomplete="family-name" placeholder="<?php esc_attr_e( 'Enter your last name', 'co' ); ?>">
				</p>
			</div>

			<p class="co-field">
				<label for="co-org"><?php esc_html_e( 'Organization', 'co' ); ?></label>
				<input type="text" id="co-org" name="organization" autocomplete="organization" placeholder="<?php esc_attr_e( 'Enter your organization name', 'co' ); ?>">
			</p>

			<p class="co-field">
				<label for="co-email"><?php esc_html_e( 'Email', 'co' ); ?></label>
				<input type="email" id="co-email" name="email" required autocomplete="email" placeholder="<?php esc_attr_e( 'Enter your email to stay up to date.', 'co' ); ?>">
			</p>

			<button type="submit" class="co-signup__submit"><?php esc_html_e( 'Join the Launch List', 'co' ); ?></button>
		</form>
	</div>
</section>
