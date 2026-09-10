<?php
/**
 * Gated apps: registry, routing and serving.
 *
 * Each app is a self-contained HTML document in /apps/. They are served
 * byte-for-byte rather than folded into theme templates: both ship their own
 * fonts, global CSS and (in the governance app's case) a React runtime, so
 * merging them into header.php/footer.php would mean re-doing the work on
 * every new export. Dropping in a newer file is the whole update process.
 *
 * A page is turned into an app by setting the _co_app meta (Page → "Gated
 * app" box). The theme then takes the request over entirely.
 *
 * The app files carry a .php extension and open with a one-line guard:
 *
 *     <?php http_response_code( 403 ); exit; ... ?>
 *
 * so a direct hit on the file is answered with 403 by PHP itself, on any web
 * server, with no host configuration. The guard is unconditional: these
 * files are never meant to serve themselves. Everything after that line is
 * the untouched export, and co_app_serve() strips the guard and streams the
 * rest verbatim, so the body is never parsed or executed as PHP.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registry of gated apps.
 *
 * @return array<string, array> Keyed by app slug.
 */
function co_apps(): array {
	return (array) apply_filters(
		'co_apps',
		array(
			'smart-ai-readiness'  => array(
				'title' => __( 'Smart AI Readiness', 'co' ),
				'file'  => 'apps/smart-ai-readiness.php',
				'blurb' => __( 'A board-ready view of how prepared your organisation is to adopt AI — across strategy, leadership, people, data, technology, process, value and trust. About ten minutes.', 'co' ),
			),
			'smart-ai-governance' => array(
				'title' => __( 'Smart AI Governance', 'co' ),
				'file'  => 'apps/smart-ai-governance.php',
				'blurb' => __( 'Assess and structure the governance your AI programme needs — controls, accountability, risk posture and the evidence a regulator or board will ask for.', 'co' ),
			),
		)
	);
}

/**
 * Whether an app key is registered.
 *
 * @param string $key App key.
 * @return bool
 */
function co_app_exists( string $key ): bool {
	return array_key_exists( $key, co_apps() );
}

/**
 * Fetch one app definition.
 *
 * @param string $key App key.
 * @return array|null
 */
function co_app( string $key ): ?array {
	$apps = co_apps();

	if ( ! isset( $apps[ $key ] ) ) {
		return null;
	}

	return $apps[ $key ] + array( 'key' => $key );
}

/**
 * The app attached to the page currently being viewed, if any.
 *
 * @return array|null
 */
function co_app_for_query(): ?array {
	if ( ! is_page() ) {
		return null;
	}

	$key = (string) get_post_meta( get_queried_object_id(), '_co_app', true );

	return $key ? co_app( $key ) : null;
}

/**
 * Permalink of the page hosting an app.
 *
 * @param string $key App key.
 * @return string Empty when no page is attached.
 */
function co_app_url( string $key ): string {
	$pages = get_option( 'co_app_pages', array() );

	if ( empty( $pages[ $key ] ) ) {
		return '';
	}

	$link = get_permalink( (int) $pages[ $key ] );

	return $link ? $link : '';
}

/* -------------------------------------------------------------------------
 * Routing
 * ---------------------------------------------------------------------- */

add_action( 'template_redirect', 'co_app_handle_redemption', 5 );
/**
 * Handle a token submission before anything is rendered.
 *
 * Both the emailed magic link (?token=...) and the gate form land here, and
 * both end in a redirect so the token never survives in the address bar or
 * in a resubmitted POST.
 */
function co_app_handle_redemption(): void {
	$app = co_app_for_query();

	if ( ! $app ) {
		return;
	}

	$target = get_permalink( get_queried_object_id() );
	$token  = '';

	if ( isset( $_POST['co_token'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified immediately below.
		if (
			! isset( $_POST['co_access_nonce'] )
			|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['co_access_nonce'] ) ), 'co_access_unlock' )
			|| ! empty( $_POST['co_hp'] )
		) {
			co_app_redirect_with( $target, 'invalid' );
		}

		$token = sanitize_text_field( wp_unslash( $_POST['co_token'] ) );
	} elseif ( isset( $_GET['token'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the token is itself the credential, validated below.
		// Reloading a magic link on a session that already covers this app
		// should not spend another use; just strip the token from the URL.
		if ( co_access_can_view( $app['key'] ) ) {
			wp_safe_redirect( $target );
			exit;
		}

		$token = sanitize_text_field( wp_unslash( $_GET['token'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} else {
		return;
	}

	$result = co_access_redeem( $token, $app['key'] );

	if ( is_wp_error( $result ) ) {
		co_app_redirect_with( $target, $result->get_error_code() );
	}

	wp_safe_redirect( $target );
	exit;
}

/**
 * Redirect back to the gate carrying an error code, then stop.
 *
 * @param string $target Page permalink.
 * @param string $code   Error code from co_access_redeem().
 */
function co_app_redirect_with( string $target, string $code ): void {
	wp_safe_redirect( add_query_arg( 'access', $code, $target ) . '#co-gate' );
	exit;
}

add_filter( 'template_include', 'co_app_template_include' );
/**
 * Serve the app to authorised visitors, or hand everyone else the gate.
 *
 * @param string $template Template WordPress resolved.
 * @return string
 */
function co_app_template_include( string $template ): string {
	$app = co_app_for_query();

	if ( ! $app ) {
		return $template;
	}

	if ( co_access_can_view( $app['key'] ) ) {
		co_app_serve( $app ); // Exits.
	}

	$gate = get_theme_file_path( 'template-access-gate.php' );

	return file_exists( $gate ) ? $gate : $template;
}

add_filter( 'wp_robots', 'co_app_robots' );
/**
 * Keep gated pages out of search results.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function co_app_robots( array $robots ): array {
	if ( co_app_for_query() ) {
		$robots = wp_robots_no_robots( $robots );
	}

	return $robots;
}

/**
 * Stream an app document and stop WordPress.
 *
 * @param array $app App definition.
 */
function co_app_serve( array $app ): void {
	$file = get_theme_file_path( $app['file'] );

	if ( ! file_exists( $file ) ) {
		wp_die(
			esc_html(
				sprintf(
					/* translators: %s: expected file path. */
					__( 'This tool is not installed yet. Expected the file at %s.', 'co' ),
					$app['file']
				)
			),
			esc_html__( 'Tool unavailable', 'co' ),
			array( 'response' => 503 )
		);
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	nocache_headers();
	header( 'Content-Type: text/html; charset=UTF-8' );
	header( 'X-Robots-Tag: noindex, nofollow', true );
	header( 'Referrer-Policy: no-referrer' );

	// Read rather than include: the guard's whole purpose is served by the
	// file extension, and reading keeps a 600KB export out of the PHP parser.
	$html = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local theme file, not a remote request.

	echo co_app_inject_bar( co_app_strip_guard( $html ), $app ); // phpcs:ignore WordPress.Security.EscapeOutput -- the app document is trusted theme source.
	exit;
}

/**
 * Remove the leading PHP guard so the response is the original export.
 *
 * Only a guard on the very first line is removed, and only up to its first
 * closing tag, so nothing in the document body can be swallowed.
 *
 * @param string $html Raw file contents.
 * @return string
 */
function co_app_strip_guard( string $html ): string {
	if ( 0 !== strncmp( $html, '<?php', 5 ) ) {
		return $html;
	}

	$close = strpos( $html, '?>' );

	if ( false === $close ) {
		return $html;
	}

	$html = substr( $html, $close + 2 );

	// PHP itself eats a single newline after a closing tag; match that so the
	// served bytes are identical to the export.
	if ( 0 === strncmp( $html, "\r\n", 2 ) ) {
		return substr( $html, 2 );
	}

	if ( "\n" === substr( $html, 0, 1 ) || "\r" === substr( $html, 0, 1 ) ) {
		return substr( $html, 1 );
	}

	return $html;
}

/**
 * Protection state of an app's file on disk.
 *
 * A re-exported tool dropped in without its guard line would be directly
 * downloadable, bypassing the gate, so the admin screen surfaces this.
 *
 * @param array $app App definition.
 * @return string 'missing', 'unguarded' or 'ok'.
 */
function co_app_file_status( array $app ): string {
	$file = get_theme_file_path( $app['file'] );

	if ( ! file_exists( $file ) ) {
		return 'missing';
	}

	$head = (string) file_get_contents( $file, false, null, 0, 256 ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	return str_contains( $head, 'CO_APP_GUARD' ) ? 'ok' : 'unguarded';
}

/**
 * Drop a small "back to site" pill into an app document.
 *
 * Injected rather than baked in, so the app files stay byte-identical to
 * the exports they came from. Disable with:
 *
 *     add_filter( 'co_app_show_bar', '__return_false' );
 *
 * @param string $html App markup.
 * @param array  $app  App definition.
 * @return string
 */
function co_app_inject_bar( string $html, array $app ): string {
	if ( ! apply_filters( 'co_app_show_bar', true, $app ) ) {
		return $html;
	}

	$pos = stripos( $html, '<body' );

	if ( false === $pos ) {
		return $html;
	}

	$open_end = strpos( $html, '>', $pos );

	if ( false === $open_end ) {
		return $html;
	}

	$expires = co_access_session_expires();
	$note    = '';

	if ( $expires ) {
		$note = sprintf(
			/* translators: %s: local time the access expires. */
			__( 'Access expires %s', 'co' ),
			wp_date( get_option( 'time_format' ) . ', ' . get_option( 'date_format' ), $expires )
		);
	}

	ob_start();
	?>
<div id="co-appbar" role="complementary">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html__( '← Cypher-One', 'co' ); ?></a>
	<?php if ( $note ) : ?>
		<span><?php echo esc_html( $note ); ?></span>
	<?php endif; ?>
</div>
<style>
	#co-appbar{position:fixed;left:14px;bottom:14px;z-index:2147483000;display:flex;align-items:center;gap:10px;
		padding:7px 13px;border-radius:999px;background:rgba(11,31,58,.92);color:#fff;
		font:500 12px/1.45 'Inter','Segoe UI',system-ui,-apple-system,Helvetica,Arial,sans-serif;
		box-shadow:0 4px 18px rgba(11,31,58,.28);backdrop-filter:saturate(140%) blur(6px);}
	#co-appbar a{color:#fff;text-decoration:none;font-weight:600;white-space:nowrap;}
	#co-appbar a:hover{text-decoration:underline;}
	#co-appbar span{color:rgba(255,255,255,.72);white-space:nowrap;}
	@media print{#co-appbar{display:none!important;}}
	@media (max-width:600px){#co-appbar span{display:none;}}
</style>
	<?php
	$bar = (string) ob_get_clean();

	return substr_replace( $html, $bar, $open_end + 1, 0 );
}

/* -------------------------------------------------------------------------
 * Attaching an app to a page
 * ---------------------------------------------------------------------- */

add_action( 'add_meta_boxes_page', 'co_app_meta_box' );
/**
 * Register the "Gated app" picker on the page edit screen.
 */
function co_app_meta_box(): void {
	add_meta_box( 'co-app', __( 'Gated app', 'co' ), 'co_app_meta_box_render', 'page', 'side', 'default' );
}

/**
 * Render the app picker.
 *
 * @param WP_Post $post Page being edited.
 */
function co_app_meta_box_render( WP_Post $post ): void {
	$current = (string) get_post_meta( $post->ID, '_co_app', true );

	wp_nonce_field( 'co_app_save', 'co_app_nonce' );

	echo '<select name="co_app" style="width:100%">';
	echo '<option value="">' . esc_html__( '— Not an app page —', 'co' ) . '</option>';

	foreach ( co_apps() as $key => $app ) {
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( $key ),
			selected( $current, $key, false ),
			esc_html( $app['title'] )
		);
	}

	echo '</select>';
	echo '<p class="description">' . esc_html__( 'When an app is selected this page shows the access gate to visitors, and the tool itself once they unlock it. The page content below is ignored.', 'co' ) . '</p>';
}

add_action( 'save_post_page', 'co_app_meta_box_save' );
/**
 * Persist the app picker.
 *
 * @param int $post_id Page ID.
 */
function co_app_meta_box_save( int $post_id ): void {
	if (
		! isset( $_POST['co_app_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['co_app_nonce'] ) ), 'co_app_save' )
		|| defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE
		|| ! current_user_can( 'edit_page', $post_id )
	) {
		return;
	}

	$key = isset( $_POST['co_app'] ) ? sanitize_key( wp_unslash( $_POST['co_app'] ) ) : '';

	if ( $key && co_app_exists( $key ) ) {
		update_post_meta( $post_id, '_co_app', $key );
	} else {
		delete_post_meta( $post_id, '_co_app' );
	}
}

/* -------------------------------------------------------------------------
 * First-run setup
 * ---------------------------------------------------------------------- */

const CO_APP_SETUP_VERSION = 1;

add_action( 'after_switch_theme', 'co_app_create_pages' );
add_action( 'admin_init', 'co_app_maybe_setup' );
/**
 * Run first-run setup once, even when the theme was already active.
 *
 * after_switch_theme only fires on an actual switch, so a site already
 * running this theme when the apps landed would never get its pages.
 */
function co_app_maybe_setup(): void {
	if ( (int) get_option( 'co_app_setup_version' ) === CO_APP_SETUP_VERSION ) {
		return;
	}

	co_app_create_pages();
	update_option( 'co_app_setup_version', CO_APP_SETUP_VERSION, false );
}

/**
 * Create a published page per app and add it to the primary menu.
 *
 * Runs once. Pages are tracked in the co_app_pages option so re-activating
 * the theme never duplicates them; delete a page and it will be recreated.
 */
function co_app_create_pages(): void {
	$pages = (array) get_option( 'co_app_pages', array() );
	$menu  = wp_get_nav_menu_object( get_nav_menu_locations()['primary'] ?? 0 );

	foreach ( co_apps() as $key => $app ) {
		if ( ! empty( $pages[ $key ] ) && get_post_status( (int) $pages[ $key ] ) ) {
			continue;
		}

		$existing = get_page_by_path( $key );

		$page_id = $existing
			? $existing->ID
			: wp_insert_post(
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'post_title'     => $app['title'],
					'post_name'      => $key,
					'post_content'   => '',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				)
			);

		if ( is_wp_error( $page_id ) || ! $page_id ) {
			continue;
		}

		update_post_meta( $page_id, '_co_app', $key );
		$pages[ $key ] = (int) $page_id;

		if ( $menu && ! co_app_menu_has_page( $menu->term_id, (int) $page_id ) ) {
			wp_update_nav_menu_item(
				$menu->term_id,
				0,
				array(
					'menu-item-object-id' => (int) $page_id,
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
					'menu-item-title'     => $app['title'],
					'menu-item-status'    => 'publish',
				)
			);
		}
	}

	update_option( 'co_app_pages', $pages, false );
}

/**
 * Whether a menu already links to a page.
 *
 * @param int $menu_id Menu term ID.
 * @param int $page_id Page ID.
 * @return bool
 */
function co_app_menu_has_page( int $menu_id, int $page_id ): bool {
	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
		if ( 'page' === $item->object && (int) $item->object_id === $page_id ) {
			return true;
		}
	}

	return false;
}
