<?php
/**
 * Inhalts-Blöcke: Zimmer, Preise, Ausstattung, Aktivitäten, Galerie, FAQ.
 */

defined( 'ABSPATH' ) || exit;

function bm_render_rooms( array $attributes ): string {
	$compact = ! empty( $attributes['compact'] );
	$rooms   = bm_rooms( (int) ( $attributes['limit'] ?? 0 ) );
	if ( ! $rooms ) {
		return bm_wrap( 'bm-empty', '<p>Derzeit sind keine Zimmer hinterlegt.</p>' );
	}

	$html = '';
	foreach ( $rooms as $room ) {
		$features = '';
		if ( ! $compact ) {
			foreach ( $room['features'] as $feature ) {
				$features .= '<li>' . bm_icon( 'check', 'bm-icon bm-icon--sm bm-icon--accent' ) . esc_html( $feature ) . '</li>';
			}
			if ( $room['accessible'] ) {
				$features .= '<li>' . bm_icon( 'accessibility', 'bm-icon bm-icon--sm bm-icon--accent' ) . 'Ein Zimmer barrierefrei verfügbar</li>';
			}
			$features = '<ul class="bm-checklist">' . $features . '</ul>';
		}

		$html .= sprintf(
			'<article class="bm-card" data-bm-reveal>
				<div class="bm-card__media">%1$s<span class="bm-badge">%2$s%3$s</span></div>
				<div class="bm-card__body">
					<h3 class="bm-card__title">%4$s</h3>
					<p class="bm-card__text">%5$s</p>
					%6$s
					<div class="bm-card__footer">
						<p class="bm-price"><span class="bm-price__value">%7$s</span><span class="bm-price__note">%8$s</span></p>
						<a class="bm-btn bm-btn--sm" href="%9$s">Zimmer anfragen</a>
					</div>
				</div>
			</article>',
			bm_image_html( $room['image_id'], $room['image_alt'], '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw' ),
			bm_icon( 'users', 'bm-icon bm-icon--xs' ),
			esc_html( $room['capacity_label'] ),
			esc_html( $room['name'] ),
			esc_html( $room['description'] ),
			$features,
			esc_html( bm_eur( $room['price'] ) ),
			esc_html( $room['price_note'] ),
			esc_url( bm_reservation_url( $room['id'] ) )
		);
	}
	return bm_wrap( 'bm-grid bm-grid--3', $html );
}

function bm_render_price_table(): string {
	$rows = sprintf(
		'<tr><th scope="row">Einzelzimmer</th><td>1 Person (jede Kategorie)</td><td class="bm-table__price">%s <span>/ Nacht</span></td></tr>',
		esc_html( bm_eur( bm_setting_float( 'single_price' ) ) )
	);
	foreach ( bm_rooms() as $room ) {
		$rows .= sprintf(
			'<tr><th scope="row">%s</th><td>%s</td><td class="bm-table__price">%s <span>/ Person / Nacht</span></td></tr>',
			esc_html( $room['name'] ),
			esc_html( $room['capacity_label'] ),
			esc_html( bm_eur( $room['price'] ) )
		);
	}

	$html = sprintf(
		'<div class="bm-table__head"><h2>%sPreisübersicht</h2><p>%s · %s</p></div>
		<div class="bm-table__scroll"><table>
			<thead><tr><th scope="col">Kategorie</th><th scope="col">Belegung</th><th scope="col" class="bm-table__price">Preis</th></tr></thead>
			<tbody>%s</tbody>
		</table></div>',
		bm_icon( 'badge-euro', 'bm-icon bm-icon--accent' ),
		esc_html( bm_setting( 'prices_valid' ) ),
		esc_html( bm_setting( 'prices_includes' ) ),
		$rows
	);
	return bm_wrap( 'bm-table bm-panel', $html, 'div', array( 'data-bm-reveal' => '' ) );
}

function bm_render_price_conditions(): string {
	$items = array(
		array( 'info', 'Nächtigungstaxe', bm_tourist_tax_text() ),
		array( 'dog', 'Hunde willkommen', bm_dog_fee_text() ),
		array( 'clock', 'Check-in & Check-out', 'Check-in: ' . bm_setting( 'check_in' ) . ' · Check-out: ' . bm_setting( 'check_out' ) . '. ' . bm_setting( 'check_in_note' ) ),
		array( 'coffee', 'Frühstück', bm_setting( 'breakfast' ) ),
		array( 'credit-card', 'Bezahlung', bm_setting( 'payment' ) ),
	);
	return bm_wrap( 'bm-tiles bm-tiles--2', bm_tiles_html( $items ), 'ul' );
}

/**
 * Icon-Kacheln (Ausstattung, Konditionen).
 */
function bm_tiles_html( array $items ): string {
	$html = '';
	foreach ( $items as [ $icon, $title, $text ] ) {
		$html .= sprintf(
			'<li class="bm-tile" data-bm-reveal><span class="bm-icon-tile">%s</span><div><h3>%s</h3><p>%s</p></div></li>',
			bm_icon( $icon ),
			esc_html( $title ),
			esc_html( $text )
		);
	}
	return $html;
}

function bm_amenities(): array {
	return array(
		array( 'cigarette-off', '100 % rauchfrei', 'Alle Zimmer sind Nichtraucherzimmer.' ),
		array( 'wifi', 'Gratis WLAN', 'Kostenloses WLAN in allen Zimmern.' ),
		array( 'tv', 'Sat-TV', 'Flatscreen mit Satellitenempfang.' ),
		array( 'shower-head', 'Dusche & WC', 'Eigenes Bad mit Pflegeprodukten.' ),
		array( 'circle-parking', 'Gratis Parkplatz', 'Ein kostenloser Stellplatz pro Zimmer.' ),
		array( 'bike', 'Fahrradgarage', 'Absperrbar – ideal für Radreisende.' ),
		array( 'cooking-pot', 'Gemeinschaftsküche', 'Küche zur freien Benützung.' ),
		array( 'dog', 'Hunde willkommen', bm_eur( bm_setting_float( 'dog_fee' ) ) . ' pro Nacht.' ),
		array( 'credit-card', 'Kartenzahlung', 'Bankomat, VISA und Mastercard.' ),
		array( 'accessibility', 'Barrierefrei', 'Ein barrierefreies Doppelzimmer.' ),
		array( 'clock', 'Check-in & Check-out', 'Check-in ' . bm_setting( 'check_in' ) . ', Check-out ' . bm_setting( 'check_out' ) . '.' ),
		array( 'coffee', 'Frühstück vis-à-vis', 'Café Bernstein, ab 6:30 Uhr (So/Ft ab 8:00).' ),
	);
}

function bm_render_amenities( array $attributes ): string {
	$items = bm_amenities();
	$limit = (int) ( $attributes['limit'] ?? 0 );
	if ( $limit > 0 ) {
		$items = array_slice( $items, 0, $limit );
	}
	return bm_wrap( 'bm-tiles bm-tiles--3', bm_tiles_html( $items ), 'ul' );
}

function bm_render_activities( array $attributes ): string {
	$posts = bm_get_posts( 'bm_activity', (int) ( $attributes['limit'] ?? 0 ) );
	if ( ! $posts ) {
		return bm_wrap( 'bm-empty', '<p>Derzeit sind keine Aktivitäten hinterlegt.</p>' );
	}
	$html = '';
	foreach ( $posts as $post ) {
		$thumb = (int) get_post_thumbnail_id( $post );
		$alt   = get_post_meta( $post->ID, '_bm_image_alt', true );
		$html .= sprintf(
			'<article class="bm-card" data-bm-reveal>
				<div class="bm-card__media">%s</div>
				<div class="bm-card__body">
					<p class="bm-card__tag">%s</p>
					<h3 class="bm-card__title">%s</h3>
					<p class="bm-card__text">%s</p>%s
				</div>
			</article>',
			bm_image_html( $thumb, $alt ? $alt : (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true ), '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw' ),
			esc_html( (string) get_post_meta( $post->ID, '_bm_tag', true ) ),
			esc_html( get_the_title( $post ) ),
			esc_html( (string) get_post_meta( $post->ID, '_bm_description', true ) ),
			bm_activity_links( (string) get_post_meta( $post->ID, '_bm_links', true ) )
		);
	}
	// Bleiben in der letzten Dreierreihe zwei Karten übrig, stehen diese zwei breit in der ersten Reihe (z. B. 2 + 3).
	$class = count( $posts ) % 3 === 2 ? 'bm-grid bm-grid--3 bm-grid--lead-2' : 'bm-grid bm-grid--3';
	return bm_wrap( $class, $html );
}

/** Weiterführende Links einer Aktivität – eine Zeile pro Link: „Name | URL“. */
function bm_activity_links( string $raw ): string {
	$items = '';
	foreach ( preg_split( '/\R/', $raw ) as $line ) {
		[ $label, $url ] = array_map( 'trim', explode( '|', $line, 2 ) ) + array( '', '' );
		if ( '' === $label || ! wp_http_validate_url( $url ) ) {
			continue;
		}
		$items .= sprintf(
			'<li><a href="%s" target="_blank" rel="noopener">%s%s<span class="screen-reader-text"> (öffnet in neuem Tab)</span></a></li>',
			esc_url( $url ),
			esc_html( $label ),
			bm_icon( 'external-link', 'bm-icon bm-icon--sm' )
		);
	}
	return $items ? '<ul class="bm-card__links" aria-label="Weiterführende Links">' . $items . '</ul>' : '';
}

function bm_render_gallery( array $attributes ): string {
	$preview = ! empty( $attributes['preview'] );
	$posts   = array_values( array_filter( bm_get_posts( 'bm_gallery', (int) ( $attributes['limit'] ?? 0 ) ), 'has_post_thumbnail' ) );
	if ( ! $posts ) {
		return bm_wrap( 'bm-empty', '<p>Derzeit sind keine Bilder hinterlegt.</p>' );
	}

	if ( $preview ) {
		$html = '';
		foreach ( $posts as $post ) {
			$html .= '<li data-bm-reveal>' . bm_image_html( (int) get_post_thumbnail_id( $post ), get_the_title( $post ), '(max-width: 640px) 50vw, 25vw' ) . '</li>';
		}
		return bm_wrap( 'bm-gallery-preview', $html, 'ul' );
	}

	$filters = '<button type="button" class="bm-chip is-active" data-bm-filter="alle" aria-pressed="true">Alle</button>';
	$used    = array_unique( array_map( fn( $p ) => get_post_meta( $p->ID, '_bm_category', true ), $posts ) );
	foreach ( bm_gallery_categories() as $key => $label ) {
		if ( in_array( $key, $used, true ) ) {
			$filters .= sprintf( '<button type="button" class="bm-chip" data-bm-filter="%s" aria-pressed="false">%s</button>', esc_attr( $key ), esc_html( $label ) );
		}
	}

	$items = '';
	foreach ( $posts as $post ) {
		$thumb = (int) get_post_thumbnail_id( $post );
		$alt   = get_the_title( $post );
		$full  = wp_get_attachment_image_url( $thumb, 'full' );
		$items .= sprintf(
			'<li data-bm-category="%s"><button type="button" class="bm-gallery__item" data-bm-full="%s" data-bm-alt="%s" aria-label="%s">%s</button></li>',
			esc_attr( (string) get_post_meta( $post->ID, '_bm_category', true ) ),
			esc_url( (string) $full ),
			esc_attr( $alt ),
			esc_attr( 'Bild vergrößern: ' . $alt ),
			bm_image_html( $thumb, $alt, '(max-width: 640px) 50vw, 33vw' )
		);
	}

	$lightbox = sprintf(
		'<dialog class="bm-lightbox" aria-label="Bildansicht">
			<button type="button" class="bm-lightbox__btn bm-lightbox__close" data-bm-close aria-label="Schließen">%s</button>
			<button type="button" class="bm-lightbox__btn bm-lightbox__prev" data-bm-prev aria-label="Vorheriges Bild">%s</button>
			<figure><img src="" alt=""><figcaption></figcaption></figure>
			<button type="button" class="bm-lightbox__btn bm-lightbox__next" data-bm-next aria-label="Nächstes Bild">%s</button>
		</dialog>',
		bm_icon( 'x' ),
		bm_icon( 'chevron-left', 'bm-icon bm-icon--lg' ),
		bm_icon( 'chevron-right', 'bm-icon bm-icon--lg' )
	);

	$html = '<div class="bm-gallery__filters" role="group" aria-label="Galerie filtern">' . $filters . '</div>'
		. '<ul class="bm-gallery__grid">' . $items . '</ul>' . $lightbox;
	return bm_wrap( 'bm-gallery', $html, 'div', array( 'data-bm-gallery' => '' ) );
}

function bm_render_faq(): string {
	$posts = bm_get_posts( 'bm_faq' );
	if ( ! $posts ) {
		return bm_wrap( 'bm-empty', '<p>Derzeit sind keine Fragen hinterlegt.</p>' );
	}
	$html = '';
	foreach ( $posts as $i => $post ) {
		$html .= sprintf(
			'<details class="bm-faq__item" name="bm-faq"%s><summary><h3>%s</h3>%s</summary><div class="bm-faq__answer">%s</div></details>',
			0 === $i ? ' open' : '',
			esc_html( get_the_title( $post ) ),
			bm_icon( 'chevron-down', 'bm-icon bm-icon--accent bm-faq__chevron' ),
			wp_kses_post( wpautop( $post->post_content ) )
		);
	}
	return bm_wrap( 'bm-faq bm-panel', $html, 'div', array( 'data-bm-reveal' => '' ) );
}
