<?php
/**
 * Component: inline SVG icon.
 *
 * Usage: co_component( 'icon', array( 'name' => 'shield' ) );
 *
 * @package co
 * @var array $args
 */

defined( 'ABSPATH' ) || exit;

$co_icons = array(
	'cursor'       => '<path d="M5 4l7 16 2.2-6.2L20 11.5 5 4z"/><path d="M13 13l5 5"/>',
	'shield'       => '<path d="M12 3l7 3v6c0 4.4-3 7.5-7 9-4-1.5-7-4.6-7-9V6l7-3z"/>',
	'people'       => '<circle cx="9" cy="9" r="3"/><circle cx="16.5" cy="10.5" r="2.4"/><path d="M4 19c.6-3 2.7-4.5 5-4.5s4.4 1.5 5 4.5"/><path d="M15 15.2c2 .2 3.5 1.5 4 3.8"/>',
	'spark'        => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3z"/><path d="M18.5 15.5l.8 2.2 2.2.8-2.2.8-.8 2.2-.8-2.2-2.2-.8 2.2-.8.8-2.2z"/>',
	'command'      => '<path d="M9 9V6.5A2.5 2.5 0 1 0 6.5 9H9zm0 0h6m-6 0v6m6-6V6.5A2.5 2.5 0 1 1 17.5 9H15zm0 0v6m0 0h2.5a2.5 2.5 0 1 1-2.5 2.5V15zm-6 0H6.5A2.5 2.5 0 1 0 9 17.5V15z"/>',
	'workflow'     => '<circle cx="8" cy="7" r="2.4"/><circle cx="16" cy="12" r="2.4"/><circle cx="9" cy="17" r="2.4"/><path d="M10.2 8.2l3.6 2.4m-.4 3l-2.8 2"/>',
	'monitor'      => '<rect x="4" y="5" width="16" height="11" rx="1.6"/><path d="M9 20h6m-3-4v4"/>',
	'shield-check' => '<path d="M12 3l7 3v6c0 4.4-3 7.5-7 9-4-1.5-7-4.6-7-9V6l7-3z"/><path d="M9 12l2 2 4-4"/>',
);

$co_name = $args['name'] ?? '';

if ( ! isset( $co_icons[ $co_name ] ) ) {
	return;
}
?>
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?php echo $co_icons[ $co_name ]; // phpcs:ignore WordPress.Security.EscapeOutput -- static trusted markup. ?></svg>
