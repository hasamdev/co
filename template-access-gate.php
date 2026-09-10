<?php
/**
 * Access gate shown in place of a gated app.
 *
 * Loaded by co_app_template_include() when the visitor has no live token.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

$co_app     = co_app_for_query();
$co_contact = co_access_contact_email();
$co_url     = get_permalink( get_queried_object_id() );

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display of a redirect result.
$co_code    = isset( $_GET['access'] ) ? sanitize_key( wp_unslash( $_GET['access'] ) ) : '';
$co_request = isset( $_GET['request'] ) ? sanitize_key( wp_unslash( $_GET['request'] ) ) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

$co_errors = array(
	'invalid'   => __( 'That token was not recognised. Check for typos, or request a new one.', 'co' ),
	'expired'   => __( 'That token has expired. Tokens are valid for 24 hours — please request a new one.', 'co' ),
	'revoked'   => __( 'That token has been revoked. Please request a new one.', 'co' ),
	'spent'     => __( 'That token has already been used. Please request a new one.', 'co' ),
	'wrong_app' => __( 'That token is valid, but it does not cover this tool. Please request access to this one.', 'co' ),
	'throttled' => __( 'Too many attempts. Please wait 15 minutes and try again.', 'co' ),
	'empty'     => __( 'Please enter your access token.', 'co' ),
);

$co_error = $co_errors[ $co_code ] ?? '';

// Prefilled request email, used by the mailto fallback.
$co_subject = sprintf(
	/* translators: %s: tool name. */
	__( 'Access request: %s', 'co' ),
	$co_app['title']
);

$co_body = sprintf(
	/* translators: 1: tool name, 2: site name. */
	__( "Hello Cypher-One,\n\nPlease send me a 24-hour access token for %1\$s.\n\nName:\nOrganisation:\nRole:\n\nThank you.", 'co' ),
	$co_app['title']
);

$co_mailto = 'mailto:' . rawurlencode( $co_contact )
	. '?subject=' . rawurlencode( $co_subject )
	. '&body=' . rawurlencode( $co_body );

get_header();
?>

<article class="co-gate" id="co-gate">
	<div class="container container--narrow">

		<p class="co-gate__eyebrow"><?php esc_html_e( 'Access by invitation', 'co' ); ?></p>
		<h1 class="co-gate__title"><?php echo esc_html( $co_app['title'] ); ?></h1>

		<?php if ( ! empty( $co_app['blurb'] ) ) : ?>
			<p class="co-gate__lede"><?php echo esc_html( $co_app['blurb'] ); ?></p>
		<?php endif; ?>

		<?php if ( 'ok' === $co_request ) : ?>
			<div class="co-gate__notice co-gate__notice--ok" role="status">
				<?php
				printf(
					/* translators: %s: contact email address. */
					esc_html__( 'Request sent. Our team will email your access token to you shortly. If you do not hear back, write to %s directly.', 'co' ),
					esc_html( $co_contact )
				);
				?>
			</div>
		<?php elseif ( 'error' === $co_request ) : ?>
			<div class="co-gate__notice co-gate__notice--bad" role="alert">
				<?php
				printf(
					/* translators: %s: contact email address. */
					esc_html__( 'That request could not be sent. Please email %s directly.', 'co' ),
					esc_html( $co_contact )
				);
				?>
			</div>
		<?php endif; ?>

		<div class="co-gate__grid">

			<section class="co-gate__panel co-gate__panel--unlock">
				<h2><?php esc_html_e( 'I have a token', 'co' ); ?></h2>
				<p><?php esc_html_e( 'Enter the access token from your invitation email. Tokens are valid for 24 hours from the moment they are issued.', 'co' ); ?></p>

				<?php if ( $co_error ) : ?>
					<div class="co-gate__notice co-gate__notice--bad" role="alert"><?php echo esc_html( $co_error ); ?></div>
				<?php endif; ?>

				<form method="post" action="<?php echo esc_url( $co_url ); ?>#co-gate" class="co-gate__form">
					<?php wp_nonce_field( 'co_access_unlock', 'co_access_nonce' ); ?>
					<p class="co-gate__hp" aria-hidden="true">
						<label>
							<?php esc_html_e( 'Leave this field empty', 'co' ); ?>
							<input type="text" name="co_hp" value="" tabindex="-1" autocomplete="off">
						</label>
					</p>

					<label class="co-gate__label" for="co-token"><?php esc_html_e( 'Access token', 'co' ); ?></label>
					<input
						class="co-gate__input"
						id="co-token"
						name="co_token"
						type="text"
						inputmode="latin"
						autocomplete="off"
						autocapitalize="characters"
						spellcheck="false"
						placeholder="XXXX-XXXX-XXXX"
						required
						<?php echo $co_error ? 'autofocus' : ''; ?>
					>

					<button type="submit" class="button co-gate__submit"><?php esc_html_e( 'Unlock', 'co' ); ?></button>
				</form>
			</section>

			<section class="co-gate__panel">
				<h2><?php esc_html_e( 'I need a token', 'co' ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: %s: contact email address, already linked. */
						esc_html__( 'Email %s and we will send you a 24-hour token. You can also send the request straight from here.', 'co' ),
						'<a href="' . esc_url( 'mailto:' . $co_contact ) . '">' . esc_html( $co_contact ) . '</a>'
					);
					?>
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

					<label class="co-gate__label" for="co-req-name"><?php esc_html_e( 'Name', 'co' ); ?></label>
					<input class="co-gate__input" id="co-req-name" name="name" type="text" autocomplete="name" required>

					<label class="co-gate__label" for="co-req-org"><?php esc_html_e( 'Organisation', 'co' ); ?></label>
					<input class="co-gate__input" id="co-req-org" name="organization" type="text" autocomplete="organization">

					<label class="co-gate__label" for="co-req-email"><?php esc_html_e( 'Work email', 'co' ); ?></label>
					<input class="co-gate__input" id="co-req-email" name="email" type="email" autocomplete="email" required>

					<button type="submit" class="button button--ghost co-gate__submit"><?php esc_html_e( 'Request access', 'co' ); ?></button>
				</form>

				<p class="co-gate__alt">
					<a href="<?php echo esc_url( $co_mailto ); ?>"><?php esc_html_e( 'Or open a prefilled email instead', 'co' ); ?></a>
				</p>
			</section>

		</div>
	</div>
</article>

<?php
get_footer();
