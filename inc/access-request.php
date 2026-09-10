<?php
/**
 * "Request access" form on the gate page.
 *
 * Relays the request to the contact address so a token can be issued by
 * hand. Nothing is granted automatically.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_nopriv_co_access_request', 'co_handle_access_request' );
add_action( 'admin_post_co_access_request', 'co_handle_access_request' );

/**
 * Validate and forward an access request.
 */
function co_handle_access_request(): void {
	$redirect = isset( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : home_url( '/' );
	$redirect = remove_query_arg( array( 'access', 'request' ), $redirect );

	$fail = static function () use ( $redirect ): void {
		wp_safe_redirect( add_query_arg( 'request', 'error', $redirect ) . '#co-gate' );
		exit;
	};

	if (
		! isset( $_POST['co_request_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['co_request_nonce'] ) ), 'co_access_request' )
		|| ! empty( $_POST['co_hp'] )
	) {
		$fail();
	}

	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$app   = isset( $_POST['app'] ) ? sanitize_key( wp_unslash( $_POST['app'] ) ) : '';

	if ( ! is_email( $email ) || ! co_app_exists( $app ) ) {
		$fail();
	}

	$definition = co_app( $app );

	$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$org  = isset( $_POST['organization'] ) ? sanitize_text_field( wp_unslash( $_POST['organization'] ) ) : '';

	$body = sprintf(
		"A visitor has requested access to %s.\n\nName: %s\nOrganisation: %s\nEmail: %s\nRequested: %s\n\nIssue a token under Tools → App access, then reply with the unlock link.\n%s\n",
		$definition['title'],
		$name,
		$org,
		$email,
		wp_date( 'Y-m-d H:i T' ),
		admin_url( 'tools.php?page=co-access' )
	);

	$sent = wp_mail(
		co_access_contact_email(),
		sprintf(
			/* translators: 1: site name, 2: tool name. */
			'[%1$s] Access request: %2$s',
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			$definition['title']
		),
		$body,
		array( 'Reply-To: ' . $name . ' <' . $email . '>' )
	);

	if ( ! $sent ) {
		$fail();
	}

	wp_safe_redirect( add_query_arg( 'request', 'ok', $redirect ) . '#co-gate' );
	exit;
}
