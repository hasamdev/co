<?php
/**
 * Front page — Cypher-One launch layout.
 *
 * Section order mirrors the approved design:
 * hero → why bar → ROI calculator → services → evolution → signup.
 * Copy is editable via ACF ("Front page" group) with the design
 * copy as fallback, so the page renders pixel-perfect out of the box.
 *
 * @package co
 */

defined( 'ABSPATH' ) || exit;

get_header();

/*
 * Background video behind the whole main area (mirrors the Framer
 * "2nd video hero bg" layer). Editable via the "Front page" ACF group —
 * self-hosting the file in the Media Library is recommended; the
 * original Framer asset is only the fallback.
 */
co_component(
	'video-bg',
	array(
		'src' => co_field( 'co_bg_video', 'https://framerusercontent.com/assets/BnKoklTgS8pZqrl9ACopqRuriE.mp4' ),
	)
);

co_section( 'co-hero' );
co_section( 'co-why' );
co_section( 'co-calculator' );
co_section( 'co-services' );
co_section( 'co-evolution' );
co_section( 'co-signup' );

get_footer();
