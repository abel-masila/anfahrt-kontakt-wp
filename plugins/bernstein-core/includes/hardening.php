<?php
/**
 * Grundlegende Absicherung und Umgebungs-Verhalten.
 *
 * Umgebung über WP_ENVIRONMENT_TYPE in wp-config.php (local / development / staging / production):
 * - Alles außer „production“ wird für Suchmaschinen gesperrt (noindex), damit Test- und
 *   Staging-Kopien nicht bei Google landen.
 * - E-Mail-Benachrichtigungen sind nur in „production“ aktiv (siehe render-forms.php).
 */

defined( 'ABSPATH' ) || exit;

function bm_is_production(): bool {
	return 'production' === wp_get_environment_type();
}

/* Test-/Staging-Umgebungen nie indexieren. */
add_filter(
	'wp_robots',
	function ( array $robots ) {
		if ( ! bm_is_production() ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
		}
		return $robots;
	}
);
add_filter(
	'robots_txt',
	fn( $output ) => bm_is_production() ? $output : "User-agent: *\nDisallow: /\n",
	99
);

/* XML-RPC wird nicht gebraucht und ist ein beliebtes Ziel für Passwort-Angriffe. */
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', fn() => array() );
if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
	status_header( 403 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	exit( 'XML-RPC ist deaktiviert.' );
}
add_filter(
	'wp_headers',
	function ( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
);

/* Benutzernamen nicht preisgeben: REST-Benutzerliste nur für angemeldete Redakteure … */
add_filter(
	'rest_endpoints',
	function ( $endpoints ) {
		if ( current_user_can( 'list_users' ) ) {
			return $endpoints;
		}
		foreach ( array( '/wp/v2/users', '/wp/v2/users/(?P<id>[\d]+)' ) as $route ) {
			unset( $endpoints[ $route ] );
		}
		return $endpoints;
	}
);

/* … und keine Autorenseiten bzw. ?author=1-Weiterleitungen (die Website hat keine Beiträge). */
add_action(
	'template_redirect',
	function () {
		if ( is_author() || ( isset( $_GET['author'] ) && ! is_admin() ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	},
	1
);
add_filter( 'redirect_canonical', fn( $redirect, $requested ) => preg_match( '/[?&]author=\d/i', (string) $requested ) ? false : $redirect, 10, 2 );
add_filter( 'author_link', fn() => home_url( '/' ) );
add_filter( 'oembed_response_data', function ( $data ) {
	unset( $data['author_name'], $data['author_url'] );
	return $data;
} );

/* WordPress-Version nicht ausgeben. */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/* Sicherheits-Header für die öffentliche Website (HSTS & CSP gehören in die Server-Konfiguration). */
add_action(
	'send_headers',
	function () {
		if ( is_admin() || headers_sent() ) {
			return;
		}
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()' );
		// Schutz vor Clickjacking nur live – Test-Umgebungen wie WordPress Playground zeigen die
		// Website in einem iframe an.
		if ( bm_is_production() ) {
			header( 'X-Frame-Options: SAMEORIGIN' );
		}
	}
);
