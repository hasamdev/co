<?php
/**
 * Self-service access: a visitor enters their email and is emailed a token.
 *
 * The token is generated and delivered automatically — nobody has to be at a
 * desk for a prospect to get into a tool. A copy of every request is sent to
 * the contact address so the lead is captured either way.
 *
 * Because this hands out credentials to anyone who asks, it is rate limited
 * twice over: by IP, so one visitor cannot mint tokens in bulk, and by email
 * address, so the form cannot be pointed at someone else's inbox as a way of
 * mail-bombing them.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

const CO_ACCESS_MAX_PER_IP    = 5;
const CO_ACCESS_MAX_PER_EMAIL = 3;

add_action( 'admin_post_nopriv_co_access_request', 'co_handle_access_request' );
add_action( 'admin_post_co_access_request', 'co_handle_access_request' );

/**
 * Issue a token to the submitted address and email it out.
 */
function co_handle_access_request(): void {
	$redirect = isset( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : home_url( '/' );
	$redirect = remove_query_arg( array( 'access' ), $redirect );

	$bounce = static function ( string $code ) use ( $redirect ): void {
		wp_safe_redirect( add_query_arg( 'access', $code, $redirect ) . '#co-gate' );
		exit;
	};

	if (
		! isset( $_POST['co_request_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['co_request_nonce'] ) ), 'co_access_request' )
		|| ! empty( $_POST['co_hp'] )
	) {
		$bounce( 'req_failed' );
	}

	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$app   = isset( $_POST['app'] ) ? sanitize_key( wp_unslash( $_POST['app'] ) ) : '';

	if ( ! is_email( $email ) ) {
		$bounce( 'req_email' );
	}

	if ( ! co_app_exists( $app ) ) {
		$bounce( 'req_failed' );
	}

	if ( ! co_access_request_allowed( $email ) ) {
		$bounce( 'req_throttled' );
	}

	$definition = co_app( $app );
	$name       = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$org        = isset( $_POST['organization'] ) ? sanitize_text_field( wp_unslash( $_POST['organization'] ) ) : '';

	$issued = co_access_issue_token(
		array(
			'app'   => $app,
			'email' => $email,
			'label' => trim( $name . ( $org ? ' · ' . $org : '' ) ) ?: __( 'Self-service', 'co' ),
			'hours' => CO_ACCESS_DEFAULT_HOURS,
		)
	);

	if ( is_wp_error( $issued ) ) {
		$bounce( 'req_failed' );
	}

	co_access_record_request( $email );

	if ( ! co_access_send_token_email( $email, $name, $issued, $definition ) ) {
		$bounce( 'req_mail' );
	}

	co_access_notify_contact( $email, $name, $org, $definition, $issued );

	$bounce( 'sent' );
}

/**
 * Whether another request may be made right now.
 *
 * @param string $email Requested address.
 * @return bool
 */
function co_access_request_allowed( string $email ): bool {
	$by_ip    = (int) get_transient( co_access_request_key( 'ip' ) );
	$by_email = (int) get_transient( co_access_request_key( 'email', $email ) );

	return $by_ip < CO_ACCESS_MAX_PER_IP && $by_email < CO_ACCESS_MAX_PER_EMAIL;
}

/**
 * Count a request against both budgets.
 *
 * @param string $email Requested address.
 */
function co_access_record_request( string $email ): void {
	$ip_key    = co_access_request_key( 'ip' );
	$email_key = co_access_request_key( 'email', $email );

	set_transient( $ip_key, (int) get_transient( $ip_key ) + 1, HOUR_IN_SECONDS );
	set_transient( $email_key, (int) get_transient( $email_key ) + 1, HOUR_IN_SECONDS );
}

/**
 * Transient key for a request budget.
 *
 * @param string $kind  'ip' or 'email'.
 * @param string $value Address, when keying by email.
 * @return string
 */
function co_access_request_key( string $kind, string $value = '' ): string {
	if ( 'email' === $kind ) {
		return 'co_access_req_e_' . md5( strtolower( $value ) );
	}

	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';

	return 'co_access_req_i_' . md5( $ip );
}

/**
 * Sender headers, so token emails come from the contact address.
 *
 * @return array
 */
function co_access_mail_headers(): array {
	$from = co_access_contact_email();
	$name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	return array(
		'Content-Type: text/html; charset=UTF-8',
		sprintf( 'From: %s <%s>', $name, $from ),
		'Reply-To: ' . $from,
	);
}

/**
 * Email the token and its one-click link to the requester.
 *
 * @param string $email      Recipient.
 * @param string $name       Recipient name, may be empty.
 * @param array  $issued     Result of co_access_issue_token().
 * @param array  $definition App definition.
 * @return bool Whether the mail was accepted for delivery.
 */
function co_access_send_token_email( string $email, string $name, array $issued, array $definition ): bool {
	$link    = co_app_url( $definition['key'] );
	$link    = $link ? add_query_arg( 'token', rawurlencode( $issued['token'] ), $link ) : '';
	$expires = wp_date( 'l j F Y, H:i T', $issued['expires_at'] );

	$subject = sprintf(
		/* translators: %s: tool name. */
		__( 'Your access code for %s', 'co' ),
		$definition['title']
	);

	ob_start();
	?>
	<div style="font-family:Segoe UI,Helvetica,Arial,sans-serif;font-size:16px;line-height:1.6;color:#0e2237;max-width:560px">
		<p><?php echo esc_html( $name ? sprintf( /* translators: %s: name. */ __( 'Hello %s,', 'co' ), $name ) : __( 'Hello,', 'co' ) ); ?></p>

		<p>
			<?php
			printf(
				/* translators: %s: tool name. */
				esc_html__( 'Here is your access code for %s.', 'co' ),
				'<strong>' . esc_html( $definition['title'] ) . '</strong>'
			);
			?>
		</p>

		<p style="margin:28px 0;padding:18px 22px;background:#f4f7fb;border:1px solid #cbd6e4;border-radius:10px;
			font-family:SFMono-Regular,Menlo,Consolas,monospace;font-size:24px;font-weight:700;letter-spacing:.1em;text-align:center">
			<?php echo esc_html( $issued['token'] ); ?>
		</p>

		<?php if ( $link ) : ?>
			<?php // Kept on one line: a wrapped tag is one more thing for an email client's HTML rewriter to mangle. ?>
			<p style="margin:28px 0"><a href="<?php echo esc_url( $link ); ?>" style="display:inline-block;padding:13px 26px;background:#1b6fc4;color:#fff;border-radius:8px;text-decoration:none;font-weight:600"><?php esc_html_e( 'Open the tool', 'co' ); ?></a></p>

			<?php
			/*
			 * The address in full, as text as well as a link. Plenty of
			 * clients strip styled anchors or render the message as plain
			 * text, which leaves a button that looks real and goes nowhere.
			 * A visible URL still gets the recipient in.
			 */
			?>
			<p style="font-size:14px;line-height:1.5;color:#6a7a8c;word-break:break-all">
				<?php esc_html_e( 'If the button does not work, copy this address into your browser:', 'co' ); ?><br>
				<a href="<?php echo esc_url( $link ); ?>" style="color:#1b6fc4"><?php echo esc_html( $link ); ?></a>
			</p>
		<?php else : ?>
			<p style="font-size:14px;line-height:1.5;color:#6a7a8c">
				<?php esc_html_e( 'Open the tool on our website and enter the code above.', 'co' ); ?>
			</p>
		<?php endif; ?>

		<p>
			<?php
			printf(
				/* translators: %s: expiry date and time. */
				esc_html__( 'Your access is valid until %s.', 'co' ),
				'<strong>' . esc_html( $expires ) . '</strong>'
			);
			?>
			<?php esc_html_e( 'After that, request a new code from the same page.', 'co' ); ?>
		</p>

		<p style="margin-top:32px;padding-top:18px;border-top:1px solid #e4dfd6;font-size:14px;color:#6a7a8c">
			<?php esc_html_e( 'Cypher-One — Enable · Empower · Excel', 'co' ); ?><br>
			<a href="<?php echo esc_url( 'mailto:' . co_access_contact_email() ); ?>" style="color:#1b6fc4">
				<?php echo esc_html( co_access_contact_email() ); ?>
			</a>
		</p>
	</div>
	<?php
	$body = (string) ob_get_clean();

	return wp_mail( $email, $subject, $body, co_access_mail_headers() );
}

/**
 * Copy the request to the contact address so the lead is captured.
 *
 * The token itself is deliberately not included — it belongs to the
 * requester, and the admin screen can always issue another.
 *
 * @param string $email      Requester address.
 * @param string $name       Requester name.
 * @param string $org        Requester organisation.
 * @param array  $definition App definition.
 * @param array  $issued     Result of co_access_issue_token().
 */
function co_access_notify_contact( string $email, string $name, string $org, array $definition, array $issued ): void {
	$body = sprintf(
		'<p>%s</p><ul><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ul><p><a href="%s">%s</a></p>',
		esc_html(
			sprintf(
				/* translators: %s: tool name. */
				__( 'Someone requested access to %s. A 24-hour code was issued and emailed to them automatically.', 'co' ),
				$definition['title']
			)
		),
		esc_html( sprintf( /* translators: %s: name. */ __( 'Name: %s', 'co' ), $name ? $name : '—' ) ),
		esc_html( sprintf( /* translators: %s: organisation. */ __( 'Organisation: %s', 'co' ), $org ? $org : '—' ) ),
		esc_html( sprintf( /* translators: %s: email address. */ __( 'Email: %s', 'co' ), $email ) ),
		esc_html( sprintf( /* translators: %s: tool name. */ __( 'Tool: %s', 'co' ), $definition['title'] ) ),
		esc_html( sprintf( /* translators: %s: date and time. */ __( 'Expires: %s', 'co' ), wp_date( 'j M Y, H:i T', $issued['expires_at'] ) ) ),
		esc_url( admin_url( 'tools.php?page=co-access' ) ),
		esc_html__( 'Manage tokens', 'co' )
	);

	wp_mail(
		co_access_contact_email(),
		sprintf(
			/* translators: 1: site name, 2: tool name. */
			'[%1$s] Access request: %2$s',
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			$definition['title']
		),
		$body,
		array_merge( co_access_mail_headers(), array( 'Reply-To: ' . ( $name ? $name . ' <' . $email . '>' : $email ) ) )
	);
}
