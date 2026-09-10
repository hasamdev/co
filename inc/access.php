<?php
/**
 * Time-limited token access for the gated apps.
 *
 * Tokens are issued by an administrator (Tools → App access) and emailed to
 * the requester. Only an HMAC of the token is stored, so a token is shown to
 * the issuer exactly once and can never be recovered afterwards.
 *
 * A redeemed token sets a signed, HttpOnly cookie whose lifetime is the
 * token's own expiry — the session can never outlive the token. Every gated
 * request re-reads the row, so revoking a token takes effect immediately.
 *
 * The app files live in /apps/ and must never be served directly by the web
 * server. Apache is handled by apps/.htaccess; on nginx add:
 *
 *     location ~* /wp-content/themes/co/apps/.*\.html$ { deny all; }
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

const CO_ACCESS_TABLE_VERSION = 1;
const CO_ACCESS_DEFAULT_HOURS = 24;

/**
 * Characters used in issued tokens.
 *
 * Excludes I, O, 0 and 1 so tokens survive being read aloud or retyped.
 */
const CO_ACCESS_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

/**
 * Address prospects are asked to email for a token.
 *
 * @return string
 */
function co_access_contact_email(): string {
	return (string) apply_filters( 'co_access_contact_email', 'contact@cypher-one.ai' );
}

/**
 * Fully-qualified token table name.
 *
 * @return string
 */
function co_access_table(): string {
	global $wpdb;

	return $wpdb->prefix . 'co_access_tokens';
}

/**
 * Create or update the token table.
 */
function co_access_install_table(): void {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table   = co_access_table();
	$collate = $wpdb->get_charset_collate();

	// dbDelta is whitespace-sensitive: two spaces after PRIMARY KEY, one
	// field per line, KEY names spelled out.
	$sql = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		token_hash char(64) NOT NULL,
		app varchar(64) NOT NULL DEFAULT '*',
		email varchar(190) NOT NULL DEFAULT '',
		label varchar(190) NOT NULL DEFAULT '',
		created_at datetime NOT NULL,
		expires_at datetime NOT NULL,
		first_used_at datetime DEFAULT NULL,
		last_used_at datetime DEFAULT NULL,
		uses int(10) unsigned NOT NULL DEFAULT 0,
		max_uses int(10) unsigned NOT NULL DEFAULT 0,
		revoked tinyint(1) NOT NULL DEFAULT 0,
		created_by bigint(20) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		UNIQUE KEY token_hash (token_hash),
		KEY expires_at (expires_at)
	) {$collate};";

	dbDelta( $sql );

	update_option( 'co_access_db_version', CO_ACCESS_TABLE_VERSION, false );
}

add_action( 'after_switch_theme', 'co_access_install_table' );

add_action( 'init', 'co_access_maybe_upgrade' );
/**
 * Install the table when the theme is updated rather than switched.
 */
function co_access_maybe_upgrade(): void {
	if ( (int) get_option( 'co_access_db_version' ) !== CO_ACCESS_TABLE_VERSION ) {
		co_access_install_table();
	}
}

/* -------------------------------------------------------------------------
 * Token issuing
 * ---------------------------------------------------------------------- */

/**
 * Generate a fresh human-readable token, e.g. "ABCD-EFGH-JKLM".
 *
 * Twelve characters from a 32-symbol alphabet is 60 bits of entropy, which
 * is far beyond what a rate-limited 24-hour window can be walked through.
 *
 * @return string
 */
function co_access_generate_token(): string {
	$max  = strlen( CO_ACCESS_ALPHABET ) - 1;
	$raw  = '';

	for ( $i = 0; $i < 12; $i++ ) {
		$raw .= CO_ACCESS_ALPHABET[ random_int( 0, $max ) ];
	}

	return implode( '-', str_split( $raw, 4 ) );
}

/**
 * Strip formatting so "abcd efgh-jklm" and "ABCD-EFGH-JKLM" match.
 *
 * @param string $token Raw user input.
 * @return string
 */
function co_access_normalize_token( string $token ): string {
	return preg_replace( '/[^A-Z0-9]/', '', strtoupper( $token ) );
}

/**
 * One-way token fingerprint. The plaintext token is never stored.
 *
 * @param string $token Raw or formatted token.
 * @return string 64-character hex digest.
 */
function co_access_hash_token( string $token ): string {
	return hash_hmac( 'sha256', co_access_normalize_token( $token ), wp_salt( 'co_access_token' ) );
}

/**
 * Issue a token and store its fingerprint.
 *
 * @param array $args {
 *     @type string $app      App key the token unlocks, or '*' for every app.
 *     @type string $email    Recipient address, for your records.
 *     @type string $label    Free-text note (organisation, campaign...).
 *     @type int    $hours    Lifetime in hours. Default 24.
 *     @type int    $max_uses Redemption cap. 0 = unlimited within the window.
 * }
 * @return array|WP_Error { token, id, expires_at } or an error.
 */
function co_access_issue_token( array $args ) {
	global $wpdb;

	$args = wp_parse_args(
		$args,
		array(
			'app'      => '*',
			'email'    => '',
			'label'    => '',
			'hours'    => CO_ACCESS_DEFAULT_HOURS,
			'max_uses' => 0,
		)
	);

	$hours = max( 1, min( 720, (int) $args['hours'] ) );
	$app   = (string) $args['app'];

	if ( '*' !== $app && ! co_app_exists( $app ) ) {
		return new WP_Error( 'co_access_bad_app', __( 'Unknown app.', 'co' ) );
	}

	$token   = co_access_generate_token();
	$now     = time();
	$expires = $now + ( $hours * HOUR_IN_SECONDS );

	$inserted = $wpdb->insert(
		co_access_table(),
		array(
			'token_hash' => co_access_hash_token( $token ),
			'app'        => $app,
			'email'      => sanitize_email( $args['email'] ),
			'label'      => sanitize_text_field( $args['label'] ),
			'created_at' => gmdate( 'Y-m-d H:i:s', $now ),
			'expires_at' => gmdate( 'Y-m-d H:i:s', $expires ),
			'max_uses'   => max( 0, (int) $args['max_uses'] ),
			'created_by' => get_current_user_id(),
		),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d' )
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- custom table.

	if ( ! $inserted ) {
		return new WP_Error( 'co_access_insert_failed', __( 'Could not store the token.', 'co' ) );
	}

	return array(
		'token'      => $token,
		'id'         => (int) $wpdb->insert_id,
		'expires_at' => $expires,
	);
}

/**
 * Revoke a token immediately.
 *
 * @param int $id Token row ID.
 * @return bool
 */
function co_access_revoke_token( int $id ): bool {
	global $wpdb;

	return (bool) $wpdb->update( co_access_table(), array( 'revoked' => 1 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/**
 * Fetch tokens for the admin screen, newest first.
 *
 * @param int $limit Row cap.
 * @return array
 */
function co_access_get_tokens( int $limit = 200 ): array {
	global $wpdb;

	$table = co_access_table();

	// phpcs:disable WordPress.DB -- custom table, interpolated name is not user input.
	return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit ) );
	// phpcs:enable WordPress.DB
}

/**
 * Look up a live token row by its plaintext token.
 *
 * @param string $token Token as typed by the visitor.
 * @return object|null Row, or null when unknown.
 */
function co_access_find_token( string $token ): ?object {
	global $wpdb;

	$table = co_access_table();

	// phpcs:disable WordPress.DB
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE token_hash = %s", co_access_hash_token( $token ) ) );
	// phpcs:enable WordPress.DB

	return $row ? $row : null;
}

/**
 * Fetch a token row by ID.
 *
 * @param int $id Row ID.
 * @return object|null
 */
function co_access_get_token( int $id ): ?object {
	global $wpdb;

	$table = co_access_table();

	// phpcs:disable WordPress.DB
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
	// phpcs:enable WordPress.DB

	return $row ? $row : null;
}

/**
 * Whether a row is still usable.
 *
 * The use-count cap is only meaningful while redeeming: once a token has
 * been redeemed its use is already recorded, and re-checking the cap on
 * every page load would lock the visitor out of the session they just
 * opened. Session checks therefore pass $redeeming = false.
 *
 * @param object|null $row       Token row.
 * @param bool        $redeeming Whether this is a redemption attempt.
 * @return true|WP_Error True when usable, otherwise the reason it is not.
 */
function co_access_validate_row( ?object $row, bool $redeeming = false ) {
	if ( ! $row ) {
		return new WP_Error( 'invalid', __( 'That token was not recognised. Check for typos, or request a new one.', 'co' ) );
	}

	if ( (int) $row->revoked ) {
		return new WP_Error( 'revoked', __( 'That token has been revoked. Please request a new one.', 'co' ) );
	}

	if ( strtotime( $row->expires_at . ' UTC' ) <= time() ) {
		return new WP_Error( 'expired', __( 'That token has expired. Tokens are valid for 24 hours — please request a new one.', 'co' ) );
	}

	if ( $redeeming && (int) $row->max_uses > 0 && (int) $row->uses >= (int) $row->max_uses ) {
		return new WP_Error( 'spent', __( 'That token has already been used. Please request a new one.', 'co' ) );
	}

	return true;
}

/**
 * Whether a token row grants a particular app.
 *
 * @param object $row     Token row.
 * @param string $app_key App key.
 * @return bool
 */
function co_access_row_grants( object $row, string $app_key ): bool {
	return '*' === $row->app || $row->app === $app_key;
}

/* -------------------------------------------------------------------------
 * Session cookie
 * ---------------------------------------------------------------------- */

/**
 * Session cookie name, namespaced per install.
 *
 * @return string
 */
function co_access_cookie_name(): string {
	return 'co_access_' . ( defined( 'COOKIEHASH' ) ? COOKIEHASH : 'co' );
}

/**
 * Sign a cookie payload.
 *
 * @param int $id      Token row ID.
 * @param int $expires Expiry timestamp.
 * @return string
 */
function co_access_sign( int $id, int $expires ): string {
	return hash_hmac( 'sha256', $id . '|' . $expires, wp_salt( 'co_access_cookie' ) );
}

/**
 * Start a session for a redeemed token.
 *
 * The cookie expires exactly when the token does, so a session can never
 * outlive its token even if the row is later deleted.
 *
 * @param object $row Token row.
 */
function co_access_start_session( object $row ): void {
	$expires = strtotime( $row->expires_at . ' UTC' );
	$value   = implode( '|', array( (int) $row->id, $expires, co_access_sign( (int) $row->id, $expires ) ) );

	setcookie(
		co_access_cookie_name(),
		$value,
		array(
			'expires'  => $expires,
			'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);

	$_COOKIE[ co_access_cookie_name() ] = $value;
}

/**
 * Clear the session cookie.
 */
function co_access_end_session(): void {
	setcookie(
		co_access_cookie_name(),
		'',
		array(
			'expires'  => time() - DAY_IN_SECONDS,
			'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);

	unset( $_COOKIE[ co_access_cookie_name() ] );
}

/**
 * Resolve the current visitor's token row from their cookie.
 *
 * Re-reads the row on every call so revocation is immediate.
 *
 * @return object|null
 */
function co_access_current_token(): ?object {
	static $cache = false;

	if ( false !== $cache ) {
		return $cache;
	}

	$cache  = null;
	$cookie = isset( $_COOKIE[ co_access_cookie_name() ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ co_access_cookie_name() ] ) ) : '';

	if ( ! $cookie || substr_count( $cookie, '|' ) !== 2 ) {
		return $cache;
	}

	list( $id, $expires, $signature ) = explode( '|', $cookie );

	$id      = (int) $id;
	$expires = (int) $expires;

	if ( ! hash_equals( co_access_sign( $id, $expires ), (string) $signature ) || $expires <= time() ) {
		return $cache;
	}

	$row = co_access_get_token( $id );

	if ( $row && true === co_access_validate_row( $row ) ) {
		$cache = $row;
	}

	return $cache;
}

/**
 * Whether the current visitor may view an app.
 *
 * Logged-in editors always pass, so the site owner can preview without
 * burning a token.
 *
 * @param string $app_key App key.
 * @return bool
 */
function co_access_can_view( string $app_key ): bool {
	if ( current_user_can( 'edit_pages' ) ) {
		return true;
	}

	$row = co_access_current_token();

	return $row && co_access_row_grants( $row, $app_key );
}

/**
 * Expiry timestamp of the current session, or 0 when there is none.
 *
 * @return int
 */
function co_access_session_expires(): int {
	$row = co_access_current_token();

	return $row ? (int) strtotime( $row->expires_at . ' UTC' ) : 0;
}

/* -------------------------------------------------------------------------
 * Redemption + rate limiting
 * ---------------------------------------------------------------------- */

/**
 * Coarse client fingerprint for throttling.
 *
 * @return string
 */
function co_access_client_key(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';

	return 'co_access_try_' . md5( $ip );
}

/**
 * Failed attempts recorded for this client.
 *
 * @return int
 */
function co_access_attempts(): int {
	return (int) get_transient( co_access_client_key() );
}

/**
 * Record a failed redemption.
 */
function co_access_record_attempt(): void {
	set_transient( co_access_client_key(), co_access_attempts() + 1, 15 * MINUTE_IN_SECONDS );
}

/**
 * Clear the throttle after a success.
 */
function co_access_clear_attempts(): void {
	delete_transient( co_access_client_key() );
}

/**
 * Whether this client is currently locked out.
 *
 * @return bool
 */
function co_access_is_throttled(): bool {
	return co_access_attempts() >= (int) apply_filters( 'co_access_max_attempts', 8 );
}

/**
 * Redeem a token for an app, starting a session on success.
 *
 * @param string $token   Token as typed or passed in the URL.
 * @param string $app_key App the visitor is trying to reach.
 * @return true|WP_Error
 */
function co_access_redeem( string $token, string $app_key ) {
	global $wpdb;

	if ( co_access_is_throttled() ) {
		return new WP_Error( 'throttled', __( 'Too many attempts. Please wait 15 minutes and try again.', 'co' ) );
	}

	if ( '' === co_access_normalize_token( $token ) ) {
		return new WP_Error( 'empty', __( 'Please enter your access token.', 'co' ) );
	}

	$row   = co_access_find_token( $token );
	$valid = co_access_validate_row( $row, true );

	if ( is_wp_error( $valid ) ) {
		co_access_record_attempt();

		return $valid;
	}

	if ( ! co_access_row_grants( $row, $app_key ) ) {
		co_access_record_attempt();

		return new WP_Error( 'wrong_app', __( 'That token is valid, but it does not cover this tool. Please request access to this one.', 'co' ) );
	}

	$now = gmdate( 'Y-m-d H:i:s' );

	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			'UPDATE `' . co_access_table() . '` SET uses = uses + 1, last_used_at = %s, first_used_at = COALESCE(first_used_at, %s) WHERE id = %d',
			$now,
			$now,
			(int) $row->id
		)
	);

	co_access_clear_attempts();
	co_access_start_session( $row );

	return true;
}

add_action( 'co_access_prune', 'co_access_prune_expired' );
/**
 * Delete tokens that expired more than 30 days ago.
 */
function co_access_prune_expired(): void {
	global $wpdb;

	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			'DELETE FROM `' . co_access_table() . '` WHERE expires_at < %s',
			gmdate( 'Y-m-d H:i:s', time() - ( 30 * DAY_IN_SECONDS ) )
		)
	);
}

add_action( 'init', 'co_access_schedule_prune' );
/**
 * Keep the daily prune event scheduled.
 */
function co_access_schedule_prune(): void {
	if ( ! wp_next_scheduled( 'co_access_prune' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'co_access_prune' );
	}
}
