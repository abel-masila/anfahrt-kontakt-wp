<?php
/**
 * Kleine Hilfsfunktionen für Ausgabe und Formatierung.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inline-SVG eines Lucide-Icons.
 */
function bm_icon( string $name, string $class = 'bm-icon' ): string {
	static $icons = null;
	if ( null === $icons ) {
		$icons = require BM_PATH . 'includes/icons-data.php';
	}
	if ( empty( $icons[ $name ] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="%s" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
		esc_attr( $class ),
		$icons[ $name ]
	);
}

/**
 * Betrag im österreichischen Format, z. B. „€ 40,00“.
 */
function bm_eur( float $value ): string {
	return '€ ' . number_format( $value, 2, ',', '.' );
}

/**
 * tel:-Link aus einer Telefonnummer.
 */
function bm_tel_href( string $phone ): string {
	return 'tel:' . preg_replace( '/[^\d+]/', '', $phone );
}

/**
 * Ziel aller „Zimmer anfragen“-Buttons, abhängig vom Reservierungs-Modus.
 */
function bm_reservation_url( string $room_slug = '' ): string {
	$path = 'booking-demo' === bm_setting( 'reservation_mode' ) ? '/buchung/' : '/reservierung/';
	$url  = home_url( $path );
	return $room_slug ? add_query_arg( 'zimmer', $room_slug, $url ) : $url;
}

/**
 * URL eines Bildes aus der Mediathek oder leerer String.
 */
function bm_image_html( int $attachment_id, string $alt, string $sizes, string $class = '' ): string {
	if ( ! $attachment_id ) {
		return '';
	}
	return wp_get_attachment_image(
		$attachment_id,
		'large',
		false,
		array(
			'alt'     => $alt,
			'sizes'   => $sizes,
			'class'   => $class,
			'loading' => 'lazy',
		)
	);
}
