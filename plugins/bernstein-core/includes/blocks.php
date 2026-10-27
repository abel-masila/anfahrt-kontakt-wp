<?php
/**
 * Dynamische Blöcke. Alle Blöcke werden serverseitig gerendert; Front-End-JS
 * wird nur geladen, wenn der jeweilige Block auf der Seite vorkommt.
 */

defined( 'ABSPATH' ) || exit;

require_once BM_PATH . 'includes/render-layout.php';
require_once BM_PATH . 'includes/render-content.php';
require_once BM_PATH . 'includes/render-forms.php';

add_filter(
	'block_categories_all',
	function ( $categories ) {
		array_unshift( $categories, array( 'slug' => 'bernstein', 'title' => 'Bernstein-Motel' ) );
		return $categories;
	}
);

add_action( 'init', 'bm_register_blocks' );

function bm_register_blocks(): void {
	$asset = fn( $file ) => BM_URL . 'assets/' . $file;
	$ver   = fn( $file ) => (string) filemtime( BM_PATH . 'assets/' . $file );

	wp_register_style( 'bernstein-blocks', $asset( 'css/blocks.css' ), array(), $ver( 'css/blocks.css' ) );
	wp_register_script( 'bernstein-header', $asset( 'js/header.js' ), array(), $ver( 'js/header.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_register_script( 'bernstein-gallery', $asset( 'js/gallery.js' ), array(), $ver( 'js/gallery.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_register_script( 'bernstein-reservation', $asset( 'js/reservation.js' ), array(), $ver( 'js/reservation.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_register_script( 'bernstein-booking', $asset( 'js/booking.js' ), array(), $ver( 'js/booking.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_register_script(
		'bernstein-editor',
		$asset( 'js/editor.js' ),
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n' ),
		$ver( 'js/editor.js' ),
		true
	);

	$blocks = array(
		'header'              => array( 'Kopfbereich', 'bm_render_header', array(), 'bernstein-header', false ),
		'footer'              => array( 'Fußbereich', 'bm_render_footer', array(), '', false ),
		'trust-bar'           => array( 'Vorteile auf einen Blick', 'bm_render_trust_bar', array(), '', true ),
		'rooms'               => array(
			'Zimmer-Karten',
			'bm_render_rooms',
			array(
				'compact' => array( 'type' => 'boolean', 'default' => false ),
				'limit'   => array( 'type' => 'number', 'default' => 0 ),
			),
			'',
			true,
		),
		'price-table'         => array( 'Preisübersicht', 'bm_render_price_table', array(), '', true ),
		'price-conditions'    => array( 'Preis-Konditionen', 'bm_render_price_conditions', array(), '', true ),
		'amenities'           => array(
			'Ausstattung',
			'bm_render_amenities',
			array( 'limit' => array( 'type' => 'number', 'default' => 0 ) ),
			'',
			true,
		),
		'location-highlights' => array( 'Lage-Highlights', 'bm_render_location_highlights', array(), '', true ),
		'activities'          => array(
			'Aktivitäten-Karten',
			'bm_render_activities',
			array( 'limit' => array( 'type' => 'number', 'default' => 0 ) ),
			'',
			true,
		),
		'gallery'             => array(
			'Galerie',
			'bm_render_gallery',
			array(
				'preview' => array( 'type' => 'boolean', 'default' => false ),
				'limit'   => array( 'type' => 'number', 'default' => 0 ),
			),
			'bernstein-gallery',
			true,
		),
		'faq'                 => array( 'Fragen & Antworten', 'bm_render_faq', array(), '', true ),
		'contact-card'        => array( 'Kontakt-Karte', 'bm_render_contact_card', array(), '', true ),
		'directions'          => array( 'Anfahrt', 'bm_render_directions', array(), '', true ),
		'cta-banner'          => array( 'Reservierungs-Aufruf', 'bm_render_cta_banner', array(), '', true ),
		'reservation-form'    => array( 'Anfrage-Formular', 'bm_render_reservation_form', array(), 'bernstein-reservation', true ),
		'booking-demo'        => array( 'Buchungs-Vorschau', 'bm_render_booking_demo', array(), 'bernstein-booking', true ),
	);

	foreach ( $blocks as $slug => [ $title, $callback, $attributes, $view_script, $inserter ] ) {
		register_block_type(
			'bernstein/' . $slug,
			array(
				'api_version'         => 3,
				'title'               => $title,
				'category'            => 'bernstein',
				'icon'                => 'building',
				'attributes'          => $attributes,
				'supports'            => array(
					'html'     => false,
					'inserter' => $inserter,
					'multiple' => true,
				),
				'render_callback'     => $callback,
				'editor_script_handles' => array( 'bernstein-editor' ),
				'style_handles'       => array( 'bernstein-blocks' ),
				'view_script_handles' => $view_script ? array( $view_script ) : array(),
			)
		);
	}
}

/**
 * „#reservieren“ als Link-Ziel in normalen Buttons/Links wird zur Reservierungs-URL
 * des aktuellen Modus. So können Redakteure in jedem Button „#reservieren“ eintragen.
 */
add_filter(
	'render_block',
	function ( $content ) {
		if ( ! is_string( $content ) || '' === $content ) {
			return $content;
		}
		if ( str_contains( $content, '#reservieren' ) ) {
			$content = str_replace( 'href="#reservieren"', 'href="' . esc_url( bm_reservation_url() ) . '"', $content );
		}
		// Seiten-relative Links („/zimmer/“) relativ zur WordPress-Adresse auflösen.
		// So bleiben Inhalte beim Umzug und in Unterverzeichnissen (z. B. Playground) gültig.
		if ( str_contains( $content, 'href="/' ) ) {
			$content = preg_replace( '#href="/(?!/)#', 'href="' . esc_url( home_url( '/' ) ), $content );
		}
		return $content;
	}
);

/**
 * Einheitlicher Wrapper mit den Block-Attributen von WordPress.
 */
function bm_wrap( string $class, string $inner, string $tag = 'div', array $extra = array() ): string {
	$attrs = get_block_wrapper_attributes( array_merge( array( 'class' => $class ), $extra ) );
	return sprintf( '<%1$s %2$s>%3$s</%1$s>', $tag, $attrs, $inner );
}
