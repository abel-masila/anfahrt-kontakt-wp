<?php
/**
 * Richtet die Website mit allen Startinhalten ein.
 *
 * Aufruf:
 *   wp bernstein seed            – legt Fehlendes an, bestehende Inhalte bleiben unverändert
 *   wp bernstein seed --force    – überschreibt Seiten und Inhalte mit dem Demo-Stand
 * oder in wp-admin unter Werkzeuge → Bernstein-Inhalte.
 *
 * Mehrfaches Ausführen ist unbedenklich.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/markup.php';
require_once __DIR__ . '/pages.php';

/**
 * @return string[] Protokoll.
 */
function bm_run_seed( bool $force = false ): array {
	$log  = array();
	$data = require __DIR__ . '/data.php';

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	// Block-Markup und Inline-Stile unverändert speichern.
	kses_remove_filters();

	// 1. Grundeinstellungen.
	update_option( 'blogname', 'Bernstein-Motel Marchegg' );
	update_option( 'blogdescription', 'Übernachten im Marchfeld' );
	update_option( 'timezone_string', 'Europe/Vienna' );
	update_option( 'date_format', 'd.m.Y' );
	update_option( 'time_format', 'H:i' );
	update_option( 'start_of_week', 1 );
	update_option( 'default_comment_status', 'closed' );
	update_option( 'default_ping_status', 'closed' );
	if ( ! get_option( BM_OPTION ) ) {
		update_option( BM_OPTION, bm_settings_defaults() );
	}
	$log[] = 'Grundeinstellungen gesetzt.';

	// 2. Bilder in die Mediathek (ohne Zwischengrößen – die Originale sind 960 px breit).
	add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array', 99 );
	$media = array();
	foreach ( $data['images'] as $file => $alt ) {
		$media[ $file ] = bm_seed_image( $file, $alt );
	}
	// Zusätzlich verwendete Bilder (Zimmer/Aktivitäten), falls nicht in der Liste.
	foreach ( array_merge( array_column( $data['rooms'], 'image' ), array_column( $data['activities'], 4 ) ) as $file ) {
		if ( empty( $media[ $file ] ) ) {
			$media[ $file ] = bm_seed_image( $file, '' );
		}
	}
	remove_filter( 'intermediate_image_sizes_advanced', '__return_empty_array', 99 );
	$log[] = count( array_filter( $media ) ) . ' Bilder in der Mediathek.';

	// 3. Zimmer.
	foreach ( $data['rooms'] as $i => $room ) {
		bm_seed_post(
			'bm_room',
			$room['slug'],
			array( 'post_title' => $room['title'], 'menu_order' => $i ),
			array(
				'_bm_description'    => $room['description'],
				'_bm_capacity'       => (string) $room['capacity'],
				'_bm_capacity_label' => $room['capacity_label'],
				'_bm_price'          => (string) $room['price'],
				'_bm_price_note'     => $room['price_note'],
				'_bm_features'       => implode( "\n", $room['features'] ),
				'_bm_accessible'     => $room['accessible'] ? '1' : '',
				'_bm_count'          => (string) $room['count'],
				'_bm_image_alt'      => $room['image_alt'],
			),
			$media[ $room['image'] ] ?? 0,
			$force
		);
	}
	$log[] = count( $data['rooms'] ) . ' Zimmerkategorien.';

	// 4. Aktivitäten.
	foreach ( $data['activities'] as $i => [ $slug, $title, $tag, $description, $image, $alt ] ) {
		bm_seed_post(
			'bm_activity',
			$slug,
			array( 'post_title' => $title, 'menu_order' => $i ),
			array(
				'_bm_tag'         => $tag,
				'_bm_description' => $description,
				'_bm_image_alt'   => $alt,
			),
			$media[ $image ] ?? 0,
			$force
		);
	}
	$log[] = count( $data['activities'] ) . ' Aktivitäten.';

	// 5. FAQ.
	foreach ( $data['faq'] as $i => [ $question, $answer ] ) {
		bm_seed_post( 'bm_faq', 'faq-' . ( $i + 1 ), array( 'post_title' => $question, 'post_content' => $answer, 'menu_order' => $i ), array(), 0, $force );
	}
	$log[] = count( $data['faq'] ) . ' Fragen & Antworten.';

	// 6. Galerie.
	$i = 0;
	foreach ( $data['gallery'] as $file => $category ) {
		bm_seed_post(
			'bm_gallery',
			'bild-' . sanitize_title( pathinfo( $file, PATHINFO_FILENAME ) ),
			array( 'post_title' => $data['images'][ $file ] ?? $file, 'menu_order' => $i++ ),
			array( '_bm_category' => $category ),
			$media[ $file ] ?? 0,
			$force
		);
	}
	$log[] = count( $data['gallery'] ) . ' Galerie-Bilder.';

	// 7. Seiten.
	$page_ids = array();
	foreach ( bms_pages( $media ) as $slug => $page ) {
		$page_ids[ $slug ] = bm_seed_post(
			'page',
			$slug,
			array(
				'post_title'   => $page['title'],
				'post_excerpt' => $page['excerpt'],
				'post_content' => implode( "\n\n", $page['blocks'] ),
			),
			array(),
			isset( $page['thumb'] ) ? ( $media[ $page['thumb'] ] ?? 0 ) : 0,
			$force
		);
	}
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $page_ids['startseite'] );
	update_option( 'page_for_posts', 0 );
	update_option( 'wp_page_for_privacy_policy', $page_ids['datenschutz'] );
	$log[] = count( $page_ids ) . ' Seiten, Startseite gesetzt.';

	// Standard-Inhalte von WordPress entfernen.
	foreach ( array( array( 'hello-world', 'post' ), array( 'sample-page', 'page' ), array( 'beispiel-seite', 'page' ), array( 'hallo-welt', 'post' ) ) as [ $slug, $type ] ) {
		$default = get_page_by_path( $slug, OBJECT, $type );
		if ( $default ) {
			wp_delete_post( $default->ID, true );
		}
	}
	$privacy_draft = get_page_by_path( 'privacy-policy', OBJECT, 'page' );
	if ( $privacy_draft && 'draft' === $privacy_draft->post_status ) {
		wp_delete_post( $privacy_draft->ID, true );
	}

	// 8. Menüs.
	bm_seed_menu(
		'Hauptmenü',
		'primary',
		array(
			array( 'Startseite', $page_ids['startseite'] ),
			array( 'Zimmer & Preise', $page_ids['zimmer'] ),
			array( 'Ausstattung', $page_ids['ausstattung'] ),
			array( 'Aktivitäten', $page_ids['aktivitaeten'] ),
			array( 'Galerie', $page_ids['galerie'] ),
			array( 'Anfahrt & Kontakt', $page_ids['anfahrt-kontakt'] ),
		),
		$force
	);
	bm_seed_menu(
		'Rechtliches',
		'legal',
		array(
			array( 'Fragen & Antworten', $page_ids['fragen-antworten'] ),
			array( 'Impressum', $page_ids['impressum'] ),
			array( 'Datenschutz', $page_ids['datenschutz'] ),
			array( 'AGB', $page_ids['agb'] ),
		),
		$force
	);
	$log[] = 'Menüs angelegt.';

	// 9. Permalinks /%postname%/.
	global $wp_rewrite;
	$wp_rewrite->set_permalink_structure( '/%postname%/' );
	bm_register_post_types();
	flush_rewrite_rules( false );
	$log[] = 'Permalinks: /%postname%/.';

	return $log;
}

/**
 * Bild aus seed/images in die Mediathek übernehmen (einmalig).
 */
function bm_seed_image( string $file, string $alt ): int {
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'meta_key'       => '_bm_seed_file', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $file, // phpcs:ignore WordPress.DB.SlowDBQuery
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( $existing ) {
		return (int) $existing[0];
	}

	$source = __DIR__ . '/images/' . $file;
	if ( ! file_exists( $source ) ) {
		return 0;
	}
	$tmp = wp_tempnam( $file );
	copy( $source, $tmp );

	$id = media_handle_sideload(
		array(
			'name'     => $file,
			'tmp_name' => $tmp,
		),
		0,
		$alt ? $alt : null
	);
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return 0;
	}
	update_post_meta( $id, '_bm_seed_file', $file );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	return (int) $id;
}

/**
 * Beitrag anlegen oder (mit $force) aktualisieren. Gibt die ID zurück.
 */
function bm_seed_post( string $type, string $slug, array $fields, array $meta, int $thumbnail, bool $force ): int {
	$existing = get_page_by_path( $slug, OBJECT, $type );
	if ( $existing && ! $force ) {
		return (int) $existing->ID;
	}

	$postarr = array_merge(
		array(
			'post_type'      => $type,
			'post_name'      => $slug,
			'post_status'    => 'publish',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		),
		$fields
	);
	if ( $existing ) {
		$postarr['ID'] = $existing->ID;
	}
	$id = wp_insert_post( wp_slash( $postarr ), true );
	if ( is_wp_error( $id ) ) {
		return 0;
	}
	foreach ( $meta as $key => $value ) {
		update_post_meta( $id, $key, $value );
	}
	if ( $thumbnail ) {
		set_post_thumbnail( $id, $thumbnail );
	}
	return (int) $id;
}

function bm_seed_menu( string $name, string $location, array $items, bool $force ): void {
	$menu = wp_get_nav_menu_object( $name );
	if ( $menu && $force ) {
		wp_delete_nav_menu( $menu->term_id );
		$menu = false;
	}
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $name );
		if ( is_wp_error( $menu_id ) ) {
			return;
		}
		foreach ( $items as $position => [ $title, $page_id ] ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $title,
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $page_id,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
					'menu-item-position'  => $position + 1,
				)
			);
		}
	} else {
		$menu_id = $menu->term_id;
	}
	$locations              = get_theme_mod( 'nav_menu_locations', array() );
	$locations[ $location ] = (int) $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}
