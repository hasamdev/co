<?php
/**
 * Tools → App access.
 *
 * Issue, review and revoke the 24-hour tokens that unlock the gated apps.
 * A token's plaintext exists only in the response that creates it — after
 * that only its HMAC is stored, so copy the email snippet before leaving.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'co_access_admin_menu' );
/**
 * Register the admin screen.
 */
function co_access_admin_menu(): void {
	add_management_page(
		__( 'App access', 'co' ),
		__( 'App access', 'co' ),
		'manage_options',
		'co-access',
		'co_access_admin_page'
	);
}

/**
 * Handle issue/revoke submissions and render the screen.
 */
function co_access_admin_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage access tokens.', 'co' ) );
	}

	$issued = null;
	$notice = '';

	if ( isset( $_POST['co_access_action'] ) ) {
		check_admin_referer( 'co_access_admin' );

		$action = sanitize_key( wp_unslash( $_POST['co_access_action'] ) );

		if ( 'issue' === $action ) {
			$result = co_access_issue_token(
				array(
					'app'      => isset( $_POST['app'] ) ? sanitize_text_field( wp_unslash( $_POST['app'] ) ) : '*',
					'email'    => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
					'label'    => isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '',
					'hours'    => isset( $_POST['hours'] ) ? (int) $_POST['hours'] : CO_ACCESS_DEFAULT_HOURS,
					'max_uses' => isset( $_POST['max_uses'] ) ? (int) $_POST['max_uses'] : 0,
				)
			);

			if ( is_wp_error( $result ) ) {
				$notice = $result->get_error_message();
			} else {
				$issued = $result + array( 'app' => isset( $_POST['app'] ) ? sanitize_text_field( wp_unslash( $_POST['app'] ) ) : '*' );
			}
		}

		if ( 'revoke' === $action && isset( $_POST['token_id'] ) ) {
			co_access_revoke_token( (int) $_POST['token_id'] );
			$notice = __( 'Token revoked. Any open session using it is now closed.', 'co' );
		}
	}

	$apps = co_apps();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'App access', 'co' ); ?></h1>
		<p class="description">
			<?php
			printf(
				/* translators: %s: contact email address. */
				esc_html__( 'Visitors are told to email %s for access. Issue a token here, then reply to them with the unlock link below.', 'co' ),
				esc_html( co_access_contact_email() )
			);
			?>
		</p>

		<?php if ( $notice ) : ?>
			<div class="notice notice-info"><p><?php echo esc_html( $notice ); ?></p></div>
		<?php endif; ?>

		<?php if ( $issued ) : ?>
			<?php co_access_render_issued( $issued, $apps ); ?>
		<?php endif; ?>

		<?php co_access_render_file_status( $apps ); ?>

		<h2><?php esc_html_e( 'Issue a token', 'co' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'co_access_admin' ); ?>
			<input type="hidden" name="co_access_action" value="issue">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="co-app"><?php esc_html_e( 'Unlocks', 'co' ); ?></label></th>
					<td>
						<select name="app" id="co-app">
							<?php foreach ( $apps as $key => $app ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $app['title'] ); ?></option>
							<?php endforeach; ?>
							<option value="*"><?php esc_html_e( 'Both tools', 'co' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="co-email"><?php esc_html_e( 'Recipient email', 'co' ); ?></label></th>
					<td>
						<input type="email" name="email" id="co-email" class="regular-text" placeholder="name@example.com">
						<p class="description"><?php esc_html_e( 'For your records only — the token is not emailed automatically.', 'co' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="co-label"><?php esc_html_e( 'Note', 'co' ); ?></label></th>
					<td><input type="text" name="label" id="co-label" class="regular-text" placeholder="<?php esc_attr_e( 'Organisation, campaign, who asked…', 'co' ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="co-hours"><?php esc_html_e( 'Valid for', 'co' ); ?></label></th>
					<td>
						<input type="number" name="hours" id="co-hours" value="<?php echo esc_attr( (string) CO_ACCESS_DEFAULT_HOURS ); ?>" min="1" max="720" class="small-text">
						<?php esc_html_e( 'hours', 'co' ); ?>
						<p class="description"><?php esc_html_e( 'Counted from now. The visitor’s session ends at the same moment.', 'co' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="co-max-uses"><?php esc_html_e( 'Use limit', 'co' ); ?></label></th>
					<td>
						<input type="number" name="max_uses" id="co-max-uses" value="0" min="0" max="999" class="small-text">
						<p class="description"><?php esc_html_e( '0 = unlimited within the window, so they can reload or switch device. Set 1 to make it strictly single-use.', 'co' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Issue token', 'co' ) ); ?>
		</form>

		<h2><?php esc_html_e( 'Issued tokens', 'co' ); ?></h2>
		<?php co_access_render_table( co_access_get_tokens(), $apps ); ?>
	</div>
	<?php
}

/**
 * Report each app file's presence and direct-access protection.
 *
 * The guard line is what stops the raw document being downloaded straight
 * from wp-content, so a re-export dropped in without it silently reopens
 * that hole. Loud rather than subtle on purpose.
 *
 * @param array $apps App registry.
 */
function co_access_render_file_status( array $apps ): void {
	$rows = array();

	foreach ( $apps as $key => $app ) {
		$rows[ $key ] = co_app_file_status( $app + array( 'key' => $key ) );
	}

	if ( ! in_array( 'missing', $rows, true ) && ! in_array( 'unguarded', $rows, true ) ) {
		return;
	}

	foreach ( $rows as $key => $status ) {
		if ( 'ok' === $status ) {
			continue;
		}

		$title = $apps[ $key ]['title'];
		$file  = $apps[ $key ]['file'];
		?>
		<div class="notice notice-error">
			<?php if ( 'missing' === $status ) : ?>
				<p>
					<strong><?php echo esc_html( $title ); ?></strong> —
					<?php
					printf(
						/* translators: %s: file path inside the theme. */
						esc_html__( 'the file %s is missing, so this tool cannot be served.', 'co' ),
						'<code>' . esc_html( $file ) . '</code>'
					);
					?>
				</p>
			<?php else : ?>
				<p>
					<strong><?php echo esc_html( $title ); ?></strong> —
					<?php
					printf(
						/* translators: %s: file path inside the theme. */
						esc_html__( '%s is missing its guard line, so anyone can download it directly and skip the token gate.', 'co' ),
						'<code>' . esc_html( $file ) . '</code>'
					);
					?>
				</p>
				<p><?php esc_html_e( 'Add this as the very first line of the file, then reload:', 'co' ); ?></p>
				<p><code>&lt;?php http_response_code( 403 ); exit; /* CO_APP_GUARD */ ?&gt;</code></p>
			<?php endif; ?>
		</div>
		<?php
	}
}

/**
 * Show a freshly created token with a ready-to-send reply.
 *
 * @param array $issued Result of co_access_issue_token() plus the app key.
 * @param array $apps   App registry.
 */
function co_access_render_issued( array $issued, array $apps ): void {
	$app_key = $issued['app'];
	$title   = '*' === $app_key ? __( 'both tools', 'co' ) : ( $apps[ $app_key ]['title'] ?? $app_key );

	// Magic link for a single app; for a combined token, point at readiness.
	$link_key = '*' === $app_key ? array_key_first( $apps ) : $app_key;
	$base     = co_app_url( $link_key );
	$link     = $base ? add_query_arg( 'token', rawurlencode( $issued['token'] ), $base ) : '';

	$expires = wp_date( 'l j F Y, H:i T', $issued['expires_at'] );

	$snippet = sprintf(
		"Thank you for your interest in %s.\n\nYour access token is:  %s\n\nOpen the tool here:\n%s\n\nThe link signs you in automatically. Your access is valid until %s.\n\nCypher-One — Enable · Empower · Excel\n%s",
		$title,
		$issued['token'],
		$link ? $link : __( '(no page is attached to this tool yet)', 'co' ),
		$expires,
		co_access_contact_email()
	);
	?>
	<div class="notice notice-success">
		<h2 style="margin-bottom:.4em"><?php esc_html_e( 'Token issued', 'co' ); ?></h2>
		<p style="font:700 22px/1.3 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;letter-spacing:.06em">
			<?php echo esc_html( $issued['token'] ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Copy this now.', 'co' ); ?></strong>
			<?php esc_html_e( 'Only a fingerprint is stored, so the token cannot be shown again. If it is lost, issue another.', 'co' ); ?>
		</p>
		<p>
			<?php
			printf(
				/* translators: 1: tool name, 2: expiry date. */
				esc_html__( 'Unlocks %1$s until %2$s.', 'co' ),
				esc_html( $title ),
				esc_html( $expires )
			);
			?>
		</p>
		<p><label for="co-snippet"><strong><?php esc_html_e( 'Ready-to-send reply', 'co' ); ?></strong></label></p>
		<textarea id="co-snippet" class="large-text code" rows="11" readonly onclick="this.select()"><?php echo esc_textarea( $snippet ); ?></textarea>
	</div>
	<?php
}

/**
 * Render the issued-token table.
 *
 * @param array $tokens Token rows.
 * @param array $apps   App registry.
 */
function co_access_render_table( array $tokens, array $apps ): void {
	if ( ! $tokens ) {
		echo '<p>' . esc_html__( 'No tokens issued yet.', 'co' ) . '</p>';

		return;
	}
	?>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Status', 'co' ); ?></th>
				<th><?php esc_html_e( 'Unlocks', 'co' ); ?></th>
				<th><?php esc_html_e( 'Recipient', 'co' ); ?></th>
				<th><?php esc_html_e( 'Note', 'co' ); ?></th>
				<th><?php esc_html_e( 'Issued', 'co' ); ?></th>
				<th><?php esc_html_e( 'Expires', 'co' ); ?></th>
				<th><?php esc_html_e( 'Uses', 'co' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $tokens as $row ) : ?>
			<?php
			$state   = co_access_validate_row( $row, true );
			$live    = true === $state;
			$expires = strtotime( $row->expires_at . ' UTC' );
			?>
			<tr>
				<td>
					<?php if ( $live ) : ?>
						<span style="color:#1a7f37;font-weight:600"><?php esc_html_e( 'Live', 'co' ); ?></span>
					<?php else : ?>
						<span style="color:#8a8f94"><?php echo esc_html( ucfirst( $state->get_error_code() ) ); ?></span>
					<?php endif; ?>
				</td>
				<td><?php echo esc_html( '*' === $row->app ? __( 'Both tools', 'co' ) : ( $apps[ $row->app ]['title'] ?? $row->app ) ); ?></td>
				<td><?php echo esc_html( $row->email ); ?></td>
				<td><?php echo esc_html( $row->label ); ?></td>
				<td><?php echo esc_html( wp_date( 'j M, H:i', strtotime( $row->created_at . ' UTC' ) ) ); ?></td>
				<td><?php echo esc_html( wp_date( 'j M, H:i', $expires ) ); ?></td>
				<td>
					<?php
					echo esc_html(
						(int) $row->max_uses
							? $row->uses . ' / ' . $row->max_uses
							: (string) $row->uses
					);
					?>
				</td>
				<td>
					<?php if ( $live ) : ?>
						<form method="post" style="margin:0">
							<?php wp_nonce_field( 'co_access_admin' ); ?>
							<input type="hidden" name="co_access_action" value="revoke">
							<input type="hidden" name="token_id" value="<?php echo esc_attr( (string) $row->id ); ?>">
							<button type="submit" class="button-link delete"><?php esc_html_e( 'Revoke', 'co' ); ?></button>
						</form>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}
