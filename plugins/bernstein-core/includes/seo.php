<?php
/**
 * SEO: Meta-Description, Open Graph, strukturierte Daten (Motel, Zimmer, FAQ, Breadcrumbs),
 * Favicon, Robots-Regeln. Die XML-Sitemap liefert WordPress selbst (/wp-sitemap.xml).
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
	return 'Familiengeführtes Motel in Marchegg zwischen Wien und Bratislava: moderne Zimmer zu fairen Preisen, gratis Parkplatz & WLAN, Fahrradgarage. Jetzt anfragen!';
}

add_action(
	'wp_head',
	function () {
		$description = bm_meta_description();
		$title       = wp_get_document_title();
		$url         = is_singular() ? get_permalink() : home_url( '/' );
		$image       = bm_share_image();

		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
		printf( '<meta property="og:type" content="website">' . "\n" );
		printf( '<meta property="og:locale" content="de_AT">' . "\n" );
		printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( bm_setting( 'name' ) ) );
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( (string) $url ) );
		if ( $image ) {
			printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image['url'] ) );
			printf( '<meta property="og:image:width" content="%d">' . "\n", $image['width'] );
			printf( '<meta property="og:image:height" content="%d">' . "\n", $image['height'] );
			if ( $image['alt'] ) {
				printf( '<meta property="og:image:alt" content="%s">' . "\n", esc_attr( $image['alt'] ) );
			}
		}
		printf( '<meta name="twitter:card" content="summary_large_image">' . "\n" );
		printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
		printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $description ) );
		if ( $image ) {
			printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image['url'] ) );
		}

		// Favicon nur, wenn kein Website-Icon in WordPress gesetzt ist.
		if ( ! has_site_icon() ) {
			printf( '<link rel="icon" type="image/png" href="%s">' . "\n", esc_url( BM_URL . 'assets/logo-bm.png' ) );
			printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( BM_URL . 'assets/logo-bm.png' ) );
		}
	},
	2
);

/** Buchungs-Vorschau, Suchergebnisse und 404: noindex (die Vorschau fehlt auch in der Sitemap). */
add_filter(
	'wp_robots',
	function ( array $robots ) {
		if ( bm_is_booking_page() || is_search() || is_404() ) {
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

/* Die Website hat keine Beiträge – leere RSS-Feeds nicht im <head> verlinken. */
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );

/** Startseite: „Name – Untertitel“, alle anderen Seiten: „Seite | Name“ (WordPress-Standard). */
add_filter(
	'document_title_parts',
	function ( array $parts ) {
		if ( is_front_page() ) {
			$parts['title'] = bm_setting( 'name' );
		}
		return $parts;
	}
);

/**
 * Bild für Link-Vorschauen: Beitragsbild der Seite, sonst das der Startseite.
 *
 * @return array{url:string,width:int,height:int,alt:string}|null
 */
function bm_share_image(): ?array {
	$id = is_singular() && has_post_thumbnail() ? (int) get_post_thumbnail_id() : 0;
	if ( ! $id ) {
		$front = (int) get_option( 'page_on_front' );
		$id    = $front ? (int) get_post_thumbnail_id( $front ) : 0;
	}
	$src = $id ? wp_get_attachment_image_src( $id, 'large' ) : false;
	if ( ! $src ) {
		return null;
	}
	return array(
		'url'    => (string) $src[0],
		'width'  => (int) $src[1],
		'height' => (int) $src[2],
		'alt'    => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
	);
}

/** Titel ohne HTML-Entities (get_the_title() liefert z. B. „&#038;“ statt „&“) – für JSON-LD. */
function bm_plain_title( WP_Post $post ): string {
	return html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
}

/** Uhrzeit „14:00“ aus Texten wie „14:00 – 21:00 Uhr“ oder „bis 10:00 Uhr“. */
function bm_first_time( string $text ): string {
	return preg_match( '/\b(\d{1,2}):(\d{2})\b/', $text, $m ) ? sprintf( '%02d:%s', $m[1], $m[2] ) : '';
}

/**
 * Strukturierte Daten (schema.org, JSON-LD): das Motel mit Adresse, Lage, Zimmern und Preisen,
 * die Website, Breadcrumbs und – auf der FAQ-Seite – die Fragen & Antworten.
 */
function bm_schema_graph(): array {
	$home  = home_url( '/' );
	$motel = $home . '#motel';
	$share = bm_share_image();

	$rooms = array();
	$total = 0;
	$min   = bm_setting_float( 'single_price' );
	foreach ( bm_get_posts( 'bm_room' ) as $post ) {
		$price  = (float) get_post_meta( $post->ID, '_bm_price', true );
		$count  = (int) get_post_meta( $post->ID, '_bm_count', true );
		$total += $count;
		$min    = $price > 0 ? min( $min, $price ) : $min;
		$room   = array(
			'@type'     => 'HotelRoom',
			'name'      => bm_plain_title( $post ),
			'occupancy' => array(
				'@type'    => 'QuantitativeValue',
				'maxValue' => max( 1, (int) get_post_meta( $post->ID, '_bm_capacity', true ) ),
			),
		);
		$text = (string) get_post_meta( $post->ID, '_bm_description', true );
		if ( $text ) {
			$room['description'] = $text;
		}
		if ( $price > 0 ) {
			$room['offers'] = array(
				'@type'              => 'Offer',
				'priceCurrency'      => 'EUR',
				'price'              => number_format( $price, 2, '.', '' ),
				'priceSpecification' => array(
					'@type'         => 'UnitPriceSpecification',
					'price'         => number_format( $price, 2, '.', '' ),
					'priceCurrency' => 'EUR',
					'unitText'      => 'pro Person und Nacht',
				),
			);
		}
		$rooms[] = $room;
	}

	$business = array(
		'@type'              => 'Motel',
		'@id'                => $motel,
		'name'               => bm_setting( 'name' ),
		'legalName'          => bm_setting( 'legal_name' ),
		'url'                => $home,
		'logo'               => BM_URL . 'assets/logo-bm.png',
		'description'        => wp_strip_all_tags( (string) get_post_field( 'post_excerpt', (int) get_option( 'page_on_front' ) ) ),
		'telephone'          => bm_setting( 'phone' ),
		'email'              => bm_setting( 'email' ),
		'address'            => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => bm_setting( 'street' ),
			'postalCode'      => bm_setting( 'zip' ),
			'addressLocality' => bm_setting( 'city' ),
			'addressRegion'   => 'Niederösterreich',
			'addressCountry'  => 'AT',
		),
		'checkinTime'        => bm_first_time( bm_setting( 'check_in' ) ),
		'checkoutTime'       => bm_first_time( bm_setting( 'check_out' ) ),
		'priceRange'         => bm_eur( $min ) . ' – ' . bm_eur( bm_setting_float( 'single_price' ) ),
		'currenciesAccepted' => 'EUR',
		'paymentAccepted'    => 'Bargeld, Bankomatkarte, Kreditkarte',
		'petsAllowed'        => true,
		'smokingAllowed'     => false,
		'availableLanguage'  => array( 'de', 'en' ),
		'amenityFeature'     => array_map(
			fn( array $item ) => array(
				'@type' => 'LocationFeatureSpecification',
				'name'  => $item[1],
				'value' => true,
			),
			bm_amenities()
		),
		'containsPlace'      => $rooms,
	);
	if ( $total ) {
		$business['numberOfRooms'] = $total;
	}
	if ( $share ) {
		$business['image'] = $share['url'];
	}
	// Koordinaten stammen aus dem Google-Maps-Link in den Einstellungen („…/@48.26,16.91,17z…“).
	if ( preg_match( '/@(-?\d+\.\d+),(-?\d+\.\d+)/', bm_setting( 'maps_url' ), $m ) ) {
		$business['geo']    = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) $m[1],
			'longitude' => (float) $m[2],
		);
		$business['hasMap'] = bm_setting( 'maps_url' );
	}

	$graph = array(
		$business,
		array(
			'@type'      => 'WebSite',
			'@id'        => $home . '#website',
			'url'        => $home,
			'name'       => bm_setting( 'name' ),
			'inLanguage' => 'de-AT',
			'publisher'  => array( '@id' => $motel ),
		),
	);

	if ( is_singular( 'page' ) && ! is_front_page() ) {
		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => array(
				array(
					'@type'    => 'ListItem',
					'position' => 1,
					'name'     => 'Startseite',
					'item'     => $home,
				),
				array(
					'@type'    => 'ListItem',
					'position' => 2,
					'name'     => bm_plain_title( get_post() ),
					'item'     => get_permalink(),
				),
			),
		);
	}

	if ( is_singular() && has_block( 'bernstein/faq' ) ) {
		$graph[] = array(
			'@type'      => 'FAQPage',
			'mainEntity' => array_map(
				fn( WP_Post $faq ) => array(
					'@type'          => 'Question',
					'name'           => bm_plain_title( $faq ),
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => wp_strip_all_tags( $faq->post_content ),
					),
				),
				bm_get_posts( 'bm_faq' )
			),
		);
	}

	return array(
		'@context' => 'https://schema.org',
		'@graph'   => array_map( fn( array $node ) => array_filter( $node, fn( $value ) => '' !== $value && array() !== $value ), $graph ),
	);
}

add_action(
	'wp_head',
	function () {
		if ( is_404() || is_search() || bm_is_booking_page() ) {
			return;
		}
		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode( bm_schema_graph(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG )
		);
	},
	3
);
