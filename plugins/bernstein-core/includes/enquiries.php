<?php
/**
 * Zimmer-Anfragen: werden über das Anfrage-Formular (/reservierung/) in der Datenbank
 * gespeichert und sind in wp-admin unter „Anfragen“ einsehbar.
 *
 * Datenschutz: Gespeichert werden nur die Formularfelder und der Zeitpunkt der
 * Einwilligung – keine IP-Adressen. Die Datenschutzerklärung muss vor dem Launch um
 * einen Hinweis auf diese Speicherung ergänzt werden.
 */

defined( 'ABSPATH' ) || exit;

const BM_ENQUIRY_STATUSES = array(
	'neu'        => 'Neu',
	'bestaetigt' => 'Bestätigt',
	'abgelehnt'  => 'Abgelehnt',
);

/**
 * Eigene Berechtigungen: Anfragen enthalten personenbezogene Daten und sind nur für
 * Administratoren und Redakteure sichtbar – nicht für Autoren oder Mitarbeiter.
 */
const BM_ENQUIRY_CAPS = array( 'edit_bm_enquiries', 'edit_others_bm_enquiries', 'edit_published_bm_enquiries', 'delete_bm_enquiries', 'delete_others_bm_enquiries', 'delete_published_bm_enquiries', 'read_private_bm_enquiries', 'publish_bm_enquiries' );
const BM_ENQUIRY_ROLES = array( 'administrator', 'editor' );

function bm_grant_enquiry_caps(): void {
	foreach ( BM_ENQUIRY_ROLES as $role_name ) {
		$role = get_role( $role_name );
		if ( $role && ! $role->has_cap( 'edit_bm_enquiries' ) ) {
			foreach ( BM_ENQUIRY_CAPS as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}
}

add_action(
	'init',
	function () {
		bm_grant_enquiry_caps();
		register_post_type(
			'bm_enquiry',
			array(
				'labels'              => array(
					'name'               => 'Anfragen',
					'singular_name'      => 'Anfrage',
					'edit_item'          => 'Anfrage ansehen',
					'all_items'          => 'Alle Anfragen',
					'search_items'       => 'Anfragen durchsuchen',
					'not_found'          => 'Noch keine Anfragen.',
					'not_found_in_trash' => 'Keine Anfragen im Papierkorb.',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'show_in_nav_menus'   => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'query_var'           => false,
				'rewrite'             => false,
				'menu_icon'           => 'dashicons-email-alt',
				'menu_position'       => 20,
				'supports'            => array( 'title' ),
				'capability_type'     => array( 'bm_enquiry', 'bm_enquiries' ),
				'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'        => true,
			)
		);
	}
);

add_filter( 'use_block_editor_for_post_type', fn( $use, $type ) => 'bm_enquiry' === $type ? false : $use, 10, 2 );

/* ---------- Umgebungs-Einstellungen (wp-config.php) ----------
 *
 * BERNSTEIN_ENQUIRY_MAIL            true/false – Benachrichtigung per E-Mail bei neuen Anfragen.
 *                                   Ohne Angabe: nur in der Umgebung „production“ aktiv.
 * BERNSTEIN_ENQUIRY_RATE_LIMIT      Anfragen pro Absender und 10 Minuten (Standard 10).
 * BERNSTEIN_TRUSTED_PROXY_HEADER    Nur hinter einem Proxy/CDN setzen, z. B. 'HTTP_CF_CONNECTING_IP'
 *                                   (Cloudflare) oder 'HTTP_X_REAL_IP'. Sonst wird REMOTE_ADDR verwendet.
 */

/** IP-Adresse des Absenders – nur für die kurzzeitige, gehashte Spam-Begrenzung. */
function bm_client_ip(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( defined( 'BERNSTEIN_TRUSTED_PROXY_HEADER' ) && ! empty( $_SERVER[ BERNSTEIN_TRUSTED_PROXY_HEADER ] ) ) {
		// Bei Listen („client, proxy1, proxy2“) zählt der erste Eintrag.
		$ip = trim( explode( ',', (string) $_SERVER[ BERNSTEIN_TRUSTED_PROXY_HEADER ] )[0] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	}
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
}

/* ---------- REST-Endpunkt ---------- */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'bernstein/v1',
			'/enquiry',
			array(
				'methods'             => 'POST',
				'callback'            => 'bm_rest_create_enquiry',
				'permission_callback' => '__return_true', // Öffentliches Formular – Schutz siehe unten.
			)
		);
	}
);

/** Nur Anfragen vom eigenen Formular annehmen (Browser senden bei POST immer „Origin“). */
function bm_same_origin_request( WP_REST_Request $request ): bool {
	$origin = (string) $request->get_header( 'origin' );
	if ( '' === $origin ) {
		return true; // z. B. Server-zu-Server oder ältere Browser – Validierung und Limit greifen trotzdem.
	}
	$home = wp_parse_url( home_url() );
	$from = wp_parse_url( $origin );
	return isset( $from['host'], $home['host'] )
		&& strtolower( $from['host'] ) === strtolower( $home['host'] )
		&& ( $from['port'] ?? null ) === ( $home['port'] ?? null );
}

function bm_rest_create_enquiry( WP_REST_Request $request ) {
	if ( ! bm_same_origin_request( $request ) ) {
		return new WP_Error( 'bm_forbidden', 'Nicht erlaubt.', array( 'status' => 403 ) );
	}

	$data = $request->get_json_params();
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'bm_bad_request', 'Ungültige Anfrage.', array( 'status' => 400 ) );
	}

	// Spam-Schutz: verstecktes Feld muss leer bleiben. Bots bekommen eine Erfolgsmeldung, gespeichert wird nichts.
	if ( ! empty( $data['website'] ) ) {
		return new WP_REST_Response( array( 'success' => true ), 201 );
	}

	// Begrenzung je Absender (gehashte IP, nur 10 Minuten im Zwischenspeicher, nie in der Anfrage gespeichert).
	$limit   = defined( 'BERNSTEIN_ENQUIRY_RATE_LIMIT' ) ? max( 1, (int) BERNSTEIN_ENQUIRY_RATE_LIMIT ) : 10;
	$ip_hash = 'bm_enq_' . hash_hmac( 'sha256', bm_client_ip(), wp_salt( 'nonce' ) );
	$count   = (int) get_transient( $ip_hash );
	if ( $count >= $limit ) {
		return new WP_Error( 'bm_rate_limited', 'Zu viele Anfragen. Bitte versuchen Sie es später erneut oder rufen Sie uns an.', array( 'status' => 429 ) );
	}

	// Nur skalare Werte annehmen – Arrays/Objekte werden als leer behandelt.
	$scalar = fn( $key ) => isset( $data[ $key ] ) && is_scalar( $data[ $key ] ) ? (string) $data[ $key ] : '';
	$text   = fn( $key, $max ) => mb_substr( sanitize_text_field( $scalar( $key ) ), 0, $max );
	$rooms  = wp_list_pluck( bm_rooms(), 'name', 'id' );
	$fields = array(
		'first_name' => $text( 'firstName', 100 ),
		'last_name'  => $text( 'lastName', 100 ),
		'email'      => mb_substr( sanitize_email( $scalar( 'email' ) ), 0, 200 ),
		'phone'      => $text( 'phone', 50 ),
		'arrival'    => $text( 'arrival', 10 ),
		'departure'  => $text( 'departure', 10 ),
		'guests'     => absint( $scalar( 'guests' ) ),
		'room'       => $text( 'room', 100 ),
		'room_count' => absint( $scalar( 'roomCount' ) ),
		'message'    => mb_substr( sanitize_textarea_field( $scalar( 'message' ) ), 0, 2000 ),
	);
	$is_date  = fn( $d ) => (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) && checkdate( (int) substr( $d, 5, 2 ), (int) substr( $d, 8, 2 ), (int) substr( $d, 0, 4 ) );
	$today    = wp_date( 'Y-m-d' );
	$max_date = wp_date( 'Y-m-d', strtotime( '+2 years' ) );

	$errors = array();
	if ( '' === $fields['first_name'] ) {
		$errors['firstName'] = 'Bitte geben Sie Ihren Vornamen an.';
	}
	if ( '' === $fields['last_name'] ) {
		$errors['lastName'] = 'Bitte geben Sie Ihren Nachnamen an.';
	}
	if ( ! is_email( $fields['email'] ) ) {
		$errors['email'] = 'Bitte geben Sie eine gültige E-Mail-Adresse an.';
	}
	if ( mb_strlen( $fields['phone'] ) < 6 || ! preg_match( '/^[\d\s+()\/.-]+$/', $fields['phone'] ) ) {
		$errors['phone'] = 'Bitte geben Sie Ihre Telefonnummer an.';
	}
	if ( ! $is_date( $fields['arrival'] ) || $fields['arrival'] < $today || $fields['arrival'] > $max_date ) {
		$errors['arrival'] = 'Bitte wählen Sie ein Anreisedatum.';
	}
	if ( ! $is_date( $fields['departure'] ) ) {
		$errors['departure'] = 'Bitte wählen Sie ein Abreisedatum.';
	} elseif ( $fields['departure'] <= $fields['arrival'] ) {
		$errors['departure'] = 'Die Abreise muss nach der Anreise liegen.';
	} elseif ( ( strtotime( $fields['departure'] ) - strtotime( $fields['arrival'] ) ) / DAY_IN_SECONDS > 90 ) {
		$errors['departure'] = 'Für Aufenthalte über 90 Nächte kontaktieren Sie uns bitte direkt.';
	}
	if ( $fields['guests'] < 1 || $fields['guests'] > 6 ) {
		$errors['guests'] = 'Bitte wählen Sie die Anzahl der Personen.';
	}
	if ( ! isset( $rooms[ $fields['room'] ] ) ) {
		$errors['room'] = 'Bitte wählen Sie eine Zimmerkategorie.';
	}
	if ( $fields['room_count'] < 1 || $fields['room_count'] > 4 ) {
		$errors['roomCount'] = 'Bitte wählen Sie die Anzahl der Zimmer.';
	}
	if ( true !== ( $data['privacy'] ?? null ) ) {
		$errors['privacy'] = 'Bitte stimmen Sie der Datenschutzerklärung zu.';
	}
	if ( $errors ) {
		return new WP_REST_Response( array( 'success' => false, 'errors' => $errors ), 400 );
	}

	$format = fn( $d ) => wp_date( 'd.m.Y', strtotime( $d . ' 12:00:00' ) );
	$id     = wp_insert_post(
		wp_slash(
			array(
				'post_type'   => 'bm_enquiry',
				'post_status' => 'publish',
				'post_title'  => sprintf( '%s %s – %s bis %s', $fields['first_name'], $fields['last_name'], $format( $fields['arrival'] ), $format( $fields['departure'] ) ),
			)
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		return new WP_Error( 'bm_save_failed', 'Die Anfrage konnte nicht gespeichert werden.', array( 'status' => 500 ) );
	}

	foreach ( $fields as $key => $value ) {
		update_post_meta( $id, '_bm_' . $key, wp_slash( $value ) );
	}
	update_post_meta( $id, '_bm_room_name', wp_slash( $rooms[ $fields['room'] ] ) );
	update_post_meta( $id, '_bm_privacy_consent', current_time( 'mysql' ) );
	update_post_meta( $id, '_bm_status', 'neu' );
	set_transient( $ip_hash, $count + 1, 10 * MINUTE_IN_SECONDS );

	if ( bm_enquiry_server_mail_enabled() ) {
		wp_mail(
			bm_setting( 'email' ),
			'Neue Zimmeranfrage: ' . get_the_title( $id ),
			bm_enquiry_text( $id ) . "\n\nIn WordPress ansehen: " . admin_url( 'post.php?post=' . $id . '&action=edit' ),
			// Nur die geprüfte Adresse – keine Namen aus dem Formular in Mail-Kopfzeilen.
			array( 'Reply-To: ' . $fields['email'] )
		);
	}

	return new WP_REST_Response( array( 'success' => true ), 201 );
}

/* ---------- Aufbewahrung (DSGVO) ----------
 * Anfragen werden nach der in Einstellungen → Bernstein-Motel festgelegten Frist
 * automatisch endgültig gelöscht (Standard 180 Tage, 0 = nie).
 */

add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'bm_enquiry_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'bm_enquiry_cleanup' );
		}
	}
);

add_action( 'bm_enquiry_cleanup', 'bm_delete_old_enquiries' );

function bm_delete_old_enquiries(): int {
	$days = (int) bm_setting( 'enquiry_retention_days' );
	if ( $days <= 0 ) {
		return 0;
	}
	$old = get_posts(
		array(
			'post_type'      => 'bm_enquiry',
			'post_status'    => 'any',
			'posts_per_page' => 200,
			'fields'         => 'ids',
			'date_query'     => array( array( 'before' => gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ), 'column' => 'post_date_gmt' ) ),
		)
	);
	foreach ( $old as $id ) {
		wp_delete_post( $id, true );
	}
	return count( $old );
}

/* ---------- Datenschutz-Werkzeuge von WordPress (Auskunft & Löschung per E-Mail) ---------- */

function bm_enquiries_by_email( string $email ): array {
	return get_posts(
		array(
			'post_type'      => 'bm_enquiry',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'meta_key'       => '_bm_email', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $email, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
}

add_filter(
	'wp_privacy_personal_data_exporters',
	function ( $exporters ) {
		$exporters['bernstein-enquiries'] = array(
			'exporter_friendly_name' => 'Zimmer-Anfragen',
			'callback'               => function ( $email ) {
				$items = array();
				foreach ( bm_enquiries_by_email( $email ) as $post ) {
					$data = array();
					foreach ( bm_enquiry_rows( $post->ID ) as $name => $value ) {
						$data[] = array( 'name' => $name, 'value' => $value );
					}
					$items[] = array( 'group_id' => 'bernstein-enquiries', 'group_label' => 'Zimmer-Anfragen', 'item_id' => 'enquiry-' . $post->ID, 'data' => $data );
				}
				return array( 'data' => $items, 'done' => true );
			},
		);
		return $exporters;
	}
);

add_filter(
	'wp_privacy_personal_data_erasers',
	function ( $erasers ) {
		$erasers['bernstein-enquiries'] = array(
			'eraser_friendly_name' => 'Zimmer-Anfragen',
			'callback'             => function ( $email ) {
				$posts = bm_enquiries_by_email( $email );
				foreach ( $posts as $post ) {
					wp_delete_post( $post->ID, true );
				}
				return array( 'items_removed' => count( $posts ) > 0, 'items_retained' => false, 'messages' => array(), 'done' => true );
			},
		);
		return $erasers;
	}
);

/** Lesbare Zusammenfassung einer Anfrage. */
function bm_enquiry_rows( int $id ): array {
	$m      = fn( $k ) => (string) get_post_meta( $id, '_bm_' . $k, true );
	$format = fn( $d ) => $d ? wp_date( 'D, d.m.Y', strtotime( $d . ' 12:00:00' ) ) : '–';
	$nights = $m( 'arrival' ) && $m( 'departure' ) ? (int) round( ( strtotime( $m( 'departure' ) ) - strtotime( $m( 'arrival' ) ) ) / DAY_IN_SECONDS ) : 0;
	return array(
		'Name'           => $m( 'first_name' ) . ' ' . $m( 'last_name' ),
		'E-Mail'         => $m( 'email' ),
		'Telefon'        => $m( 'phone' ),
		'Anreise'        => $format( $m( 'arrival' ) ),
		'Abreise'        => $format( $m( 'departure' ) ),
		'Nächte'         => (string) $nights,
		'Personen'       => $m( 'guests' ),
		'Zimmer'         => $m( 'room_name' ),
		'Anzahl Zimmer'  => $m( 'room_count' ),
		'Nachricht'      => $m( 'message' ),
		'Eingegangen am' => get_the_date( 'd.m.Y, H:i', $id ) . ' Uhr',
		'Einwilligung'   => $m( 'privacy_consent' ) ? 'Datenschutz zugestimmt am ' . mysql2date( 'd.m.Y, H:i', $m( 'privacy_consent' ) ) . ' Uhr' : '–',
	);
}

function bm_enquiry_text( int $id ): string {
	$lines = array();
	foreach ( bm_enquiry_rows( $id ) as $label => $value ) {
		$lines[] = $label . ': ' . $value;
	}
	return implode( "\n", $lines );
}

/* ---------- Admin: Ansicht, Status, Liste ---------- */

add_action(
	'add_meta_boxes_bm_enquiry',
	function () {
		add_meta_box( 'bm_enquiry_details', 'Anfrage', 'bm_render_enquiry_box', 'bm_enquiry', 'normal', 'high' );
		add_meta_box( 'bm_enquiry_status', 'Status', 'bm_render_enquiry_status', 'bm_enquiry', 'side', 'high' );
	}
);

function bm_render_enquiry_box( WP_Post $post ): void {
	$email = (string) get_post_meta( $post->ID, '_bm_email', true );
	$phone = (string) get_post_meta( $post->ID, '_bm_phone', true );
	echo '<table class="widefat striped" style="border:0"><tbody>';
	foreach ( bm_enquiry_rows( $post->ID ) as $label => $value ) {
		echo '<tr><th style="width:12rem">' . esc_html( $label ) . '</th><td>' . nl2br( esc_html( $value ) ) . '</td></tr>';
	}
	echo '</tbody></table>';
	printf(
		'<p style="margin-top:1rem"><a class="button button-primary" href="%s">Antworten per E-Mail</a> <a class="button" href="%s">Anrufen</a></p>',
		esc_attr( 'mailto:' . $email . '?subject=' . rawurlencode( 'Ihre Zimmeranfrage im ' . bm_setting( 'name' ) ) ),
		esc_attr( bm_tel_href( $phone ) )
	);
	echo '<p class="description">Die Anfrage ist noch keine Buchung. Bitte bestätigen Sie die Verfügbarkeit direkt beim Gast und setzen Sie danach den Status.</p>';
}

function bm_render_enquiry_status( WP_Post $post ): void {
	$status = get_post_meta( $post->ID, '_bm_status', true ) ?: 'neu';
	wp_nonce_field( 'bm_enquiry_status', 'bm_enquiry_nonce' );
	echo '<select name="bm_status" style="width:100%">';
	foreach ( BM_ENQUIRY_STATUSES as $key => $label ) {
		echo '<option value="' . esc_attr( $key ) . '" ' . selected( $status, $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';
}

add_action(
	'save_post_bm_enquiry',
	function ( int $post_id ) {
		if ( ! isset( $_POST['bm_enquiry_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['bm_enquiry_nonce'] ), 'bm_enquiry_status' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$status = sanitize_key( $_POST['bm_status'] ?? 'neu' );
		if ( isset( BM_ENQUIRY_STATUSES[ $status ] ) ) {
			update_post_meta( $post_id, '_bm_status', $status );
		}
	}
);

/* Felder der Anfrage sind schreibgeschützt: Titel-Feld ausblenden. */
add_action(
	'admin_head-post.php',
	function () {
		if ( 'bm_enquiry' === get_current_screen()->post_type ) {
			echo '<style>#titlediv,#minor-publishing{display:none}</style>';
		}
	}
);

add_filter(
	'manage_bm_enquiry_posts_columns',
	fn( $c ) => array(
		'cb'         => $c['cb'],
		'title'      => 'Gast & Zeitraum',
		'bm_room'    => 'Zimmer',
		'bm_guests'  => 'Personen',
		'bm_contact' => 'Kontakt',
		'bm_status'  => 'Status',
		'date'       => 'Eingang',
	)
);

add_action(
	'manage_bm_enquiry_posts_custom_column',
	function ( $column, $id ) {
		$m = fn( $k ) => (string) get_post_meta( $id, '_bm_' . $k, true );
		switch ( $column ) {
			case 'bm_room':
				echo esc_html( $m( 'room_count' ) . ' × ' . $m( 'room_name' ) );
				break;
			case 'bm_guests':
				echo esc_html( $m( 'guests' ) );
				break;
			case 'bm_contact':
				printf( '<a href="%s">%s</a><br>%s', esc_attr( 'mailto:' . $m( 'email' ) ), esc_html( $m( 'email' ) ), esc_html( $m( 'phone' ) ) );
				break;
			case 'bm_status':
				$status = $m( 'status' ) ?: 'neu';
				$color  = array( 'neu' => '#b8862b', 'bestaetigt' => '#1e3a2d', 'abgelehnt' => '#75806f' )[ $status ] ?? '#75806f';
				printf( '<span style="display:inline-block;padding:2px 8px;border-radius:999px;background:%s;color:#fff;font-size:12px">%s</span>', esc_attr( $color ), esc_html( BM_ENQUIRY_STATUSES[ $status ] ?? $status ) );
				break;
		}
	},
	10,
	2
);

/* Neueste Anfragen zuerst; Zähler für neue Anfragen im Menü. */
add_action(
	'pre_get_posts',
	function ( WP_Query $q ) {
		if ( is_admin() && $q->is_main_query() && 'bm_enquiry' === $q->get( 'post_type' ) && ! $q->get( 'orderby' ) ) {
			$q->set( 'orderby', 'date' );
			$q->set( 'order', 'DESC' );
		}
	}
);

add_action(
	'admin_menu',
	function () {
		global $menu;
		$new = count(
			get_posts(
				array(
					'post_type'      => 'bm_enquiry',
					'posts_per_page' => 99,
					'fields'         => 'ids',
					'meta_key'       => '_bm_status', // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_value'     => 'neu', // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			)
		);
		if ( ! $new ) {
			return;
		}
		foreach ( $menu as $i => $item ) {
			if ( 'edit.php?post_type=bm_enquiry' === $item[2] ) {
				$menu[ $i ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . (int) $new . '</span></span>'; // phpcs:ignore
			}
		}
	},
	99
);
