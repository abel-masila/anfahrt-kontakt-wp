<?php
/**
 * Grundlegendes SEO: Meta-Description, Open Graph, Favicon, Buchungs-Vorschau nicht indexieren.
 * Die XML-Sitemap liefert WordPress selbst (/wp-sitemap.xml).
 */

defined( 'ABSPATH' ) || exit;

/** Seiten bekommen ein Textauszug-Feld – es dient als Meta-Description. */
add_action( 'init', fn() => add_post_type_support( 'page', 'excerpt' ) );

function bm_is_booking_page(): bool {
	return is_page( 'buchung' );
}

function bm_meta_description(): string {
	if ( is_singular() ) {
		$excerpt = get_post_field( 'post_excerpt', get_queried_object_id() );
		if ( $excerpt ) {
			return wp_strip_all_tags( $excerpt );
		}
	}
	return 'Modernes, familiengeführtes Motel in Marchegg – zwischen Wien und Bratislava. Faire Preise, gratis Parkplatz & WLAN, absperrbare Fahrradgarage. Jetzt Zimmer anfragen!';
}

add_action(
	'wp_head',
	function () {
		$description = bm_meta_description();
		$title       = wp_get_document_title();
		$url         = is_singular() ? get_permalink() : home_url( '/' );
		$image       = '';
		$front_id    = (int) get_option( 'page_on_front' );
		if ( $front_id && has_post_thumbnail( $front_id ) ) {
			$image = (string) get_the_post_thumbnail_url( $front_id, 'full' );
		}

		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
		printf( '<meta property="og:type" content="website">' . "\n" );
		printf( '<meta property="og:locale" content="de_AT">' . "\n" );
		printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( bm_setting( 'name' ) ) );
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( (string) $url ) );
		if ( $image ) {
			printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
		}
		printf( '<meta name="twitter:card" content="summary_large_image">' . "\n" );

		// Favicon nur, wenn kein Website-Icon in WordPress gesetzt ist.
		if ( ! has_site_icon() ) {
			printf( '<link rel="icon" type="image/svg+xml" href="%s">' . "\n", esc_url( BM_URL . 'assets/icon.svg' ) );
		}
	},
	2
);

/** Buchungs-Vorschau: noindex und nicht in der Sitemap. */
add_filter(
	'wp_robots',
	function ( array $robots ) {
		if ( bm_is_booking_page() ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
		}
		return $robots;
	}
);

add_filter(
	'wp_sitemaps_posts_query_args',
	function ( array $args, string $post_type ) {
		if ( 'page' === $post_type ) {
			$booking = get_page_by_path( 'buchung' );
			if ( $booking ) {
				$args['post__not_in'] = array_merge( $args['post__not_in'] ?? array(), array( $booking->ID ) );
			}
		}
		return $args;
	},
	10,
	2
);

/** Keine Sitemaps für Benutzer und Kategorien – die Website hat nur Seiten. */
add_filter(
	'wp_sitemaps_add_provider',
	fn( $provider, $name ) => in_array( $name, array( 'users', 'taxonomies' ), true ) ? false : $provider,
	10,
	2
);

add_filter( 'document_title_separator', fn() => is_front_page() ? '–' : '|' );
