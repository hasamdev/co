<?php
/**
 * Launch-list signup handler (front page form).
 *
 * Stores entries in the co_launch_list option and notifies the admin.
 * Swap the storage block for your ESP/CRM API when one is chosen.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_nopriv_co_launch_signup', 'co_handle_launch_signup' );
add_action( 'admin_post_co_launch_signup', 'co_handle_launch_signup' );

/**
 * Validate, store and acknowledge a launch-list signup.
 */
function co_handle_launch_signup(): void {
	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$redirect = remove_query_arg( 'launch-list', $redirect );

	$fail = static function () use ( $redirect ): void {
		wp_safe_redirect( add_query_arg( 'launch-list', 'error', $redirect ) . '#launch-list' );
		exit;
	};

	// Nonce + honeypot.
	if (
		! isset( $_POST['co_launch_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['co_launch_nonce'] ) ), 'co_launch_signup' )
		|| ! empty( $_POST['co_hp'] )
	) {
		$fail();
	}

	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( ! is_email( $email ) ) {
		$fail();
	}

	$entry = array(
		'first_name'   => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '',
		'last_name'    => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '',
		'organization' => isset( $_POST['organization'] ) ? sanitize_text_field( wp_unslash( $_POST['organization'] ) ) : '',
		'email'        => $email,
		'date'         => current_time( 'mysql' ),
	);

	// Store (deduped by email, capped at 5000 entries).
	$list = get_option( 'co_launch_list', array() );

	if ( ! isset( $list[ $email ] ) && count( $list ) < 5000 ) {
		$list[ $email ] = $entry;
		update_option( 'co_launch_list', $list, false );
	}

	// Notify the site admin.
	wp_mail(
		get_option( 'admin_email' ),
		sprintf( '[%s] New launch-list signup', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ),
		sprintf(
			"Name: %s %s\nOrganization: %s\nEmail: %s\nDate: %s\n",
			$entry['first_name'],
			$entry['last_name'],
			$entry['organization'],
			$entry['email'],
			$entry['date']
		)
	);

	wp_safe_redirect( add_query_arg( 'launch-list', 'ok', $redirect ) . '#launch-list' );
	exit;
}

add_action( 'admin_menu', 'co_launch_list_admin_page' );
/**
 * Read-only admin page listing signups (Tools → Launch list).
 */
function co_launch_list_admin_page(): void {
	add_management_page(
		__( 'Launch list', 'co' ),
		__( 'Launch list', 'co' ),
		'manage_options',
		'co-launch-list',
		static function (): void {
			$list = get_option( 'co_launch_list', array() );
			echo '<div class="wrap"><h1>' . esc_html__( 'Launch list', 'co' ) . '</h1>';
			echo '<p>' . esc_html( sprintf( /* translators: %d: count. */ __( '%d signups.', 'co' ), count( $list ) ) ) . '</p>';
			echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Name', 'co' ) . '</th><th>' . esc_html__( 'Organization', 'co' ) . '</th><th>' . esc_html__( 'Email', 'co' ) . '</th><th>' . esc_html__( 'Date', 'co' ) . '</th></tr></thead><tbody>';
			foreach ( array_reverse( $list ) as $row ) {
				printf(
					'<tr><td>%s %s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
					esc_html( $row['first_name'] ),
					esc_html( $row['last_name'] ),
					esc_html( $row['organization'] ),
					esc_html( $row['email'] ),
					esc_html( $row['date'] )
				);
			}
			echo '</tbody></table></div>';
		}
	);
}
