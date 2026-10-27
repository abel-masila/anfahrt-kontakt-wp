<?php
/**
 * Inhaltstypen: Zimmer, Aktivitäten, FAQ, Galerie-Bilder – mit einfachen Meta-Boxen.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Feld-Definitionen je Inhaltstyp: key => [Label, Typ, Hilfetext, Optionen].
 */
function bm_meta_fields(): array {
	return array(
		'bm_room'     => array(
			'_bm_description'    => array( 'Kurzbeschreibung', 'textarea', '' ),
			'_bm_capacity'       => array( 'Max. Personen', 'number', 'Wird in der Buchungs-Vorschau verwendet.' ),
			'_bm_capacity_label' => array( 'Belegung (Anzeige)', 'text', 'z. B. „1–2 Personen“' ),
			'_bm_price'          => array( 'Preis in € pro Person / Nacht', 'price', '' ),
			'_bm_price_note'     => array( 'Preis-Zusatz', 'text', 'z. B. „pro Person / Nacht“' ),
			'_bm_features'       => array( 'Ausstattung', 'textarea', 'Ein Merkmal pro Zeile.' ),
			'_bm_accessible'     => array( 'Barrierefreies Zimmer in dieser Kategorie', 'checkbox', '' ),
			'_bm_count'          => array( 'Anzahl Zimmer', 'number', '' ),
			'_bm_image_alt'      => array( 'Bildbeschreibung (Alt-Text)', 'text', 'Leer lassen, um den Alt-Text aus der Mediathek zu verwenden.' ),
		),
		'bm_activity' => array(
			'_bm_tag'         => array( 'Kurz-Untertitel', 'text', 'z. B. „Storchenstadt Marchegg“' ),
			'_bm_description' => array( 'Beschreibung', 'textarea', '' ),
			'_bm_image_alt'   => array( 'Bildbeschreibung (Alt-Text)', 'text', 'Leer lassen, um den Alt-Text aus der Mediathek zu verwenden.' ),
		),
		'bm_gallery'  => array(
			'_bm_category' => array(
				'Kategorie',
				'select',
				'',
				bm_gallery_categories(),
			),
		),
	);
}

function bm_gallery_categories(): array {
	return array(
		'aussen'       => 'Außenansicht',
		'zimmer'       => 'Zimmer & Bad',
		'gemeinschaft' => 'Eingang & Gemeinschaft',
		'umgebung'     => 'Umgebung',
	);
}

function bm_register_post_types(): void {
	$common = array(
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'has_archive'         => false,
		'rewrite'             => false,
		'hierarchical'        => false,
	);

	register_post_type(
		'bm_room',
		$common + array(
			'labels'        => array(
				'name'          => 'Zimmer',
				'singular_name' => 'Zimmer',
				'add_new_item'  => 'Neue Zimmerkategorie',
				'edit_item'     => 'Zimmerkategorie bearbeiten',
				'all_items'     => 'Alle Zimmer',
				'menu_name'     => 'Zimmer & Preise',
			),
			'menu_icon'     => 'dashicons-building',
			'menu_position' => 21,
			'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
		)
	);

	register_post_type(
		'bm_activity',
		$common + array(
			'labels'        => array(
				'name'          => 'Aktivitäten',
				'singular_name' => 'Aktivität',
				'add_new_item'  => 'Neue Aktivität',
				'edit_item'     => 'Aktivität bearbeiten',
				'all_items'     => 'Alle Aktivitäten',
			),
			'menu_icon'     => 'dashicons-palmtree',
			'menu_position' => 22,
			'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
		)
	);

	register_post_type(
		'bm_faq',
		$common + array(
			'labels'        => array(
				'name'          => 'Fragen & Antworten',
				'singular_name' => 'Frage',
				'add_new_item'  => 'Neue Frage',
				'edit_item'     => 'Frage bearbeiten',
				'all_items'     => 'Alle Fragen',
				'menu_name'     => 'FAQ',
			),
			'menu_icon'     => 'dashicons-editor-help',
			'menu_position' => 23,
			'supports'      => array( 'title', 'editor', 'page-attributes' ),
		)
	);

	register_post_type(
		'bm_gallery',
		$common + array(
			'labels'        => array(
				'name'          => 'Galerie',
				'singular_name' => 'Galerie-Bild',
				'add_new_item'  => 'Neues Galerie-Bild',
				'edit_item'     => 'Galerie-Bild bearbeiten',
				'all_items'     => 'Alle Bilder',
			),
			'menu_icon'     => 'dashicons-format-gallery',
			'menu_position' => 24,
			'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
		)
	);
}
add_action( 'init', 'bm_register_post_types' );

/** Klassischer Editor für die eigenen Inhaltstypen – einfacher für Formularfelder. */
add_filter(
	'use_block_editor_for_post_type',
	fn( $use, $type ) => in_array( $type, array( 'bm_room', 'bm_activity', 'bm_faq', 'bm_gallery' ), true ) ? false : $use,
	10,
	2
);

/** Titel-Platzhalter. */
add_filter(
	'enter_title_here',
	function ( $text, $post ) {
		return match ( $post->post_type ) {
			'bm_room'    => 'Name der Zimmerkategorie',
			'bm_faq'     => 'Frage',
			'bm_gallery' => 'Bildbeschreibung (wird als Alt-Text verwendet)',
			default      => $text,
		};
	},
	10,
	2
);

add_action(
	'add_meta_boxes',
	function () {
		foreach ( array_keys( bm_meta_fields() ) as $type ) {
			add_meta_box( 'bm_details', 'Details', 'bm_render_meta_box', $type, 'normal', 'high' );
		}
	}
);

function bm_render_meta_box( WP_Post $post ): void {
	$fields = bm_meta_fields()[ $post->post_type ] ?? array();
	wp_nonce_field( 'bm_save_meta', 'bm_meta_nonce' );
	echo '<table class="form-table" role="presentation">';
	foreach ( $fields as $key => $field ) {
		$id    = 'bm' . $key;
		$value = get_post_meta( $post->ID, $key, true );
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $field[0] ) . '</label></th><td>';
		switch ( $field[1] ) {
			case 'textarea':
				echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" rows="5" class="large-text">' . esc_textarea( $value ) . '</textarea>';
				break;
			case 'checkbox':
				echo '<input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" value="1" ' . checked( $value, '1', false ) . '>';
				break;
			case 'select':
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '">';
				foreach ( $field[3] as $opt => $label ) {
					echo '<option value="' . esc_attr( $opt ) . '" ' . selected( $value, $opt, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select>';
				break;
			case 'number':
				echo '<input type="number" min="0" step="1" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="small-text">';
				break;
			case 'price':
				echo '<input type="number" min="0" step="0.01" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="small-text">';
				break;
			default:
				echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="regular-text">';
		}
		if ( $field[2] ) {
			echo '<p class="description">' . esc_html( $field[2] ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</table>';
}

add_action(
	'save_post',
	function ( int $post_id, WP_Post $post ) {
		$fields = bm_meta_fields()[ $post->post_type ] ?? null;
		if ( ! $fields || ! isset( $_POST['bm_meta_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_POST['bm_meta_nonce'] ), 'bm_save_meta' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( $fields as $key => $field ) {
			$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$value = match ( $field[1] ) {
				'textarea' => sanitize_textarea_field( $raw ),
				'checkbox' => $raw ? '1' : '',
				'number'   => (string) absint( $raw ),
				'price'    => (string) max( 0, (float) str_replace( ',', '.', $raw ) ),
				'select'   => array_key_exists( $raw, $field[3] ) ? $raw : '',
				default    => sanitize_text_field( $raw ),
			};
			update_post_meta( $post_id, $key, $value );
		}
	},
	10,
	2
);

/** Admin-Listen: Reihenfolge nach „Reihenfolge“-Feld, Spalten für Bild und Preis. */
add_action(
	'pre_get_posts',
	function ( WP_Query $query ) {
		if ( is_admin() && $query->is_main_query() && in_array( $query->get( 'post_type' ), array( 'bm_room', 'bm_activity', 'bm_faq', 'bm_gallery' ), true ) && ! $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', 'ASC' );
		}
	}
);

foreach ( array( 'bm_room', 'bm_activity', 'bm_gallery' ) as $bm_type ) {
	add_filter(
		"manage_{$bm_type}_posts_columns",
		function ( $columns ) use ( $bm_type ) {
			$new = array( 'cb' => $columns['cb'], 'bm_image' => 'Bild', 'title' => $columns['title'] );
			if ( 'bm_room' === $bm_type ) {
				$new['bm_price'] = 'Preis';
			}
			if ( 'bm_gallery' === $bm_type ) {
				$new['bm_category'] = 'Kategorie';
			}
			return $new;
		}
	);
	add_action(
		"manage_{$bm_type}_posts_custom_column",
		function ( $column, $post_id ) {
			if ( 'bm_image' === $column ) {
				echo get_the_post_thumbnail( $post_id, array( 80, 50 ), array( 'style' => 'width:80px;height:auto;border-radius:4px' ) );
			} elseif ( 'bm_price' === $column ) {
				echo esc_html( bm_eur( (float) get_post_meta( $post_id, '_bm_price', true ) ) );
			} elseif ( 'bm_category' === $column ) {
				echo esc_html( bm_gallery_categories()[ get_post_meta( $post_id, '_bm_category', true ) ] ?? '–' );
			}
		},
		10,
		2
	);
}

/**
 * Daten-Abfragen für die Blöcke.
 */
function bm_get_posts( string $type, int $limit = -1 ): array {
	return get_posts(
		array(
			'post_type'      => $type,
			'post_status'    => 'publish',
			'posts_per_page' => $limit > 0 ? $limit : -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
			'no_found_rows'  => true,
		)
	);
}

function bm_rooms( int $limit = -1 ): array {
	return array_map(
		function ( WP_Post $p ) {
			$thumb = (int) get_post_thumbnail_id( $p );
			$alt   = get_post_meta( $p->ID, '_bm_image_alt', true );
			return array(
				'id'             => $p->post_name,
				'name'           => get_the_title( $p ),
				'description'    => (string) get_post_meta( $p->ID, '_bm_description', true ),
				'capacity'       => max( 1, (int) get_post_meta( $p->ID, '_bm_capacity', true ) ),
				'capacity_label' => (string) get_post_meta( $p->ID, '_bm_capacity_label', true ),
				'price'          => (float) get_post_meta( $p->ID, '_bm_price', true ),
				'price_note'     => (string) get_post_meta( $p->ID, '_bm_price_note', true ),
				'features'       => array_values( array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $p->ID, '_bm_features', true ) ) ) ) ),
				'accessible'     => '1' === get_post_meta( $p->ID, '_bm_accessible', true ),
				'image_id'       => $thumb,
				'image_alt'      => $alt ? $alt : (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true ),
			);
		},
		bm_get_posts( 'bm_room', $limit )
	);
}
