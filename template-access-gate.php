<?php
/**
 * Access gate shown in place of a gated app.
 *
 * The gate is a modal: the visitor lands on a short holding page and the
 * dialog opens over it. Step one takes their email and issues a 24-hour code
 * automatically; step two takes that code. Which step opens is decided here
 * on the server, so the flow survives a page reload. The dialog starts
 * closed and gate.js opens it as a true modal; a <noscript> stylesheet
 * forces it visible in the flow when scripting is off. Rendering it `open`
 * instead would flash an unstyled panel before the deferred script runs.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

$co_app     = co_app_for_query();
$co_contact = co_access_contact_email();
$co_url     = get_permalink( get_queried_object_id() );

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of a redirect result.
$co_code = isset( $_GET['access'] ) ? sanitize_key( wp_unslash( $_GET['access'] ) ) : '';

/*
 * Messages fall into two groups: those about requesting a code (which leave
 * the visitor on step one, except for the success case) and those about
 * redeeming one (which put them on step two).
 */
$co_messages = array(
	'sent'          => array( 'ok', __( 'Check your inbox — your access code is on its way. It is valid for 24 hours.', 'co' ), 'token' ),
	'req_email'     => array( 'bad', __( 'That email address does not look right. Please check it and try again.', 'co' ), 'email' ),
	'req_throttled' => array( 'bad', __( 'That is a lot of requests. Please wait an hour, or use the code already in your inbox.', 'co' ), 'token' ),
	'req_mail'      => array( 'bad', '', 'email' ),
	'req_failed'    => array( 'bad', '', 'email' ),
	'invalid'       => array( 'bad', __( 'That code was not recognised. Check for typos, or request a new one.', 'co' ), 'token' ),
	'expired'       => array( 'bad', __( 'That code has expired. Codes last 24 hours — request a fresh one below.', 'co' ), 'token' ),
	'revoked'       => array( 'bad', __( 'That code has been revoked. Please request a new one.', 'co' ), 'token' ),
	'spent'         => array( 'bad', __( 'That code has already been used. Please request a new one.', 'co' ), 'token' ),
	'wrong_app'     => array( 'bad', __( 'That code is valid, but it does not cover this tool. Request one for this tool below.', 'co' ), 'token' ),
	'throttled'     => array( 'bad', __( 'Too many attempts. Please wait 15 minutes and try again.', 'co' ), 'token' ),
	'empty'         => array( 'bad', __( 'Please enter your access code.', 'co' ), 'token' ),
);

// The two failures that need the contact address are built here so the
// address is not repeated through the table above.
$co_messages['req_mail'][1] = sprintf(
	/* translators: %s: contact email address. */
	__( 'Your code was created but the email could not be sent. Please contact %s and we will send it by hand.', 'co' ),
	$co_contact
);

$co_messages['req_failed'][1] = sprintf(
	/* translators: %s: contact email address. */
	__( 'That request could not be completed. Please try again, or contact %s.', 'co' ),
	$co_contact
);

$co_message = $co_messages[ $co_code ] ?? null;
$co_step    = $co_message ? $co_message[2] : 'email';

get_header();
?>

<article class="co-gate" id="co-gate">
	<div class="container container--narrow">

		<?php if ( current_user_can( 'edit_pages' ) ) : ?>
			<div class="co-gate__notice co-gate__notice--info" role="status">
				<?php esc_html_e( 'Preview — you are signed in, so you normally skip this and go straight to the tool. This is what a visitor sees.', 'co' ); ?>
			</div>
		<?php endif; ?>

		<p class="co-gate__eyebrow"><?php esc_html_e( 'Protected tool', 'co' ); ?></p>
		<h1 class="co-gate__title"><?php echo esc_html( $co_app['title'] ); ?></h1>

		<?php if ( ! empty( $co_app['blurb'] ) ) : ?>
			<p class="co-gate__lede"><?php echo esc_html( $co_app['blurb'] ); ?></p>
		<?php endif; ?>

		<p class="co-gate__reopen">
			<button type="button" class="button" data-co-gate-open>
				<?php esc_html_e( 'Get access', 'co' ); ?>
			</button>
		</p>
	</div>

	<noscript>
		<style>
			/* Author styles beat the UA's dialog:not([open]){display:none}. */
			.co-modal { display: block; position: static; }
			.co-modal__close, .co-modal__switch { display: none; }
			.co-modal[data-step] .co-modal__step { display: block; }
		</style>
	</noscript>

	<dialog class="co-modal" id="co-gate-dialog" data-step="<?php echo esc_attr( $co_step ); ?>">
		<div class="co-modal__inner">

			<button type="button" class="co-modal__close" data-co-gate-close aria-label="<?php esc_attr_e( 'Close', 'co' ); ?>">&times;</button>

			<p class="co-modal__eyebrow"><?php echo esc_html( $co_app['title'] ); ?></p>

			<?php if ( $co_message && $co_message[1] ) : ?>
				<div class="co-gate__notice co-gate__notice--<?php echo esc_attr( $co_message[0] ); ?>"
					role="<?php echo 'ok' === $co_message[0] ? 'status' : 'alert'; ?>">
					<?php echo esc_html( $co_message[1] ); ?>
				</div>
			<?php endif; ?>

			<!-- Step one: ask for an email, issue a code automatically. -->
			<section class="co-modal__step" data-step-panel="email">
				<h2 id="co-modal-title"><?php esc_html_e( 'Get your access code', 'co' ); ?></h2>
				<p class="co-modal__lede">
					<?php esc_html_e( 'Enter your email and we will send you a code straight away. It unlocks this tool for 24 hours.', 'co' ); ?>
				</p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="co-gate__form">
					<input type="hidden" name="action" value="co_access_request">
					<input type="hidden" name="app" value="<?php echo esc_attr( $co_app['key'] ); ?>">
					<input type="hidden" name="redirect" value="<?php echo esc_url( $co_url ); ?>">
					<?php wp_nonce_field( 'co_access_request', 'co_request_nonce' ); ?>
					<p class="co-gate__hp" aria-hidden="true">
						<label>
							<?php esc_html_e( 'Leave this field empty', 'co' ); ?>
							<input type="text" name="co_hp" value="" tabindex="-1" autocomplete="off">
						</label>
					</p>

					<label class="co-gate__label" for="co-req-email"><?php esc_html_e( 'Work email', 'co' ); ?></label>
					<input class="co-gate__input" id="co-req-email" name="email" type="email" autocomplete="email" required
						<?php echo 'email' === $co_step ? 'autofocus' : ''; ?>>

					<label class="co-gate__label" for="co-req-name"><?php esc_html_e( 'Name', 'co' ); ?></label>
					<input class="co-gate__input" id="co-req-name" name="name" type="text" autocomplete="name">

					<label class="co-gate__label" for="co-req-org"><?php esc_html_e( 'Organisation', 'co' ); ?></label>
					<input class="co-gate__input" id="co-req-org" name="organization" type="text" autocomplete="organization">

					<button type="submit" class="button co-gate__submit"><?php esc_html_e( 'Email me a code', 'co' ); ?></button>
				</form>

				<p class="co-modal__switch">
					<button type="button" class="co-linkish" data-co-step="token">
						<?php esc_html_e( 'I already have a code', 'co' ); ?>
					</button>
				</p>
			</section>

			<!-- Step two: redeem the code. -->
			<section class="co-modal__step" data-step-panel="token">
				<h2><?php esc_html_e( 'Enter your access code', 'co' ); ?></h2>
				<p class="co-modal__lede">
					<?php esc_html_e( 'Paste the code from your email. Codes are valid for 24 hours from the moment they are sent.', 'co' ); ?>
				</p>

				<form method="post" action="<?php echo esc_url( $co_url ); ?>#co-gate" class="co-gate__form">
					<?php wp_nonce_field( 'co_access_unlock', 'co_access_nonce' ); ?>
					<p class="co-gate__hp" aria-hidden="true">
						<label>
							<?php esc_html_e( 'Leave this field empty', 'co' ); ?>
							<input type="text" name="co_hp" value="" tabindex="-1" autocomplete="off">
						</label>
					</p>

					<label class="co-gate__label" for="co-token"><?php esc_html_e( 'Access code', 'co' ); ?></label>
					<input
						class="co-gate__input"
						id="co-token"
						name="co_token"
						type="text"
						inputmode="latin"
						autocomplete="one-time-code"
						autocapitalize="characters"
						spellcheck="false"
						maxlength="14"
						placeholder="XXXX-XXXX-XXXX"
						required
						<?php echo 'token' === $co_step ? 'autofocus' : ''; ?>
					>

					<button type="submit" class="button co-gate__submit"><?php esc_html_e( 'Unlock', 'co' ); ?></button>
				</form>

				<p class="co-modal__switch">
					<button type="button" class="co-linkish" data-co-step="email">
						<?php esc_html_e( 'Send me a new code', 'co' ); ?>
					</button>
				</p>
			</section>

			<p class="co-modal__foot">
				<?php
				printf(
					/* translators: %s: contact email address, already linked. */
					esc_html__( 'Trouble getting in? Contact %s', 'co' ),
					'<a href="' . esc_url( 'mailto:' . $co_contact ) . '">' . esc_html( $co_contact ) . '</a>'
				);
				?>
			</p>
		</div>
	</dialog>
</article>

<?php
get_footer();
