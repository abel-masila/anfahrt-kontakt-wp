<?php
/**
 * Kopf-/Fußbereich und wiederkehrende Layout-Blöcke.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	function () {
		register_nav_menus(
			array(
				'primary' => 'Hauptnavigation',
				'legal'   => 'Rechtliches (Fußbereich)',
			)
		);
	}
);

/**
 * Menüpunkte eines Menü-Ortes; Fallback auf die Standard-Navigation.
 */
function bm_menu_items( string $location ): array {
	$locations = get_nav_menu_locations();
	if ( ! empty( $locations[ $location ] ) ) {
		$items = wp_get_nav_menu_items( $locations[ $location ] );
		if ( $items ) {
			return array_map(
				fn( $item ) => array( 'label' => $item->title, 'url' => $item->url ),
				array_values( array_filter( $items, fn( $item ) => 0 === (int) $item->menu_item_parent ) )
			);
		}
	}
	$defaults = 'legal' === $location
		? array(
			'Fragen & Antworten' => '/fragen-antworten/',
			'Impressum'          => '/impressum/',
			'Datenschutz'        => '/datenschutz/',
			'AGB'                => '/agb/',
		)
		: array(
			'Startseite'        => '/',
			'Zimmer & Preise'   => '/zimmer/',
			'Ausstattung'       => '/ausstattung/',
			'Aktivitäten'       => '/aktivitaeten/',
			'Galerie'           => '/galerie/',
			'Anfahrt & Kontakt' => '/anfahrt-kontakt/',
		);
	$out = array();
	foreach ( $defaults as $label => $path ) {
		$out[] = array( 'label' => $label, 'url' => home_url( $path ) );
	}
	return $out;
}

/**
 * Ist der Menüpunkt die aktuelle Seite (oder ein Elternpfad davon)?
 */
function bm_is_current( string $url ): bool {
	$request      = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	$item_path    = untrailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) );
	$current_path = untrailingslashit( (string) wp_parse_url( $request, PHP_URL_PATH ) );
	$home_path    = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
	if ( $item_path === $home_path ) {
		return $current_path === $home_path;
	}
	return '' !== $item_path && ( $current_path === $item_path || str_starts_with( $current_path, $item_path . '/' ) );
}

/** „Bernstein-Motel Marchegg“ → [„Bernstein-Motel“, „Marchegg“]. */
function bm_logo_parts(): array {
	$words = explode( ' ', trim( bm_setting( 'name' ) ) );
	$last  = count( $words ) > 1 ? array_pop( $words ) : '';
	return array( implode( ' ', $words ), $last );
}

function bm_render_header(): string {
	[ $logo_main, $logo_sub ] = bm_logo_parts();
	$items                    = bm_menu_items( 'primary' );
	$tel                      = bm_tel_href( bm_setting( 'phone' ) );

	ob_start();
	?>
	<header <?php echo get_block_wrapper_attributes( array( 'class' => 'bm-header' ) ); // phpcs:ignore ?> data-bm-header>
		<div class="bm-header__bar">
			<a class="bm-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( bm_setting( 'name' ) . ' – zur Startseite' ); ?>">
				<span class="bm-logo__main"><?php echo esc_html( $logo_main ); ?></span>
				<?php if ( $logo_sub ) : ?>
					<span class="bm-logo__sub"><?php echo esc_html( $logo_sub ); ?></span>
				<?php endif; ?>
			</a>

			<nav class="bm-nav" aria-label="Hauptnavigation">
				<?php foreach ( $items as $item ) : ?>
					<?php $current = bm_is_current( $item['url'] ); ?>
					<a class="bm-nav__link<?php echo $current ? ' is-current' : ''; ?>" href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $item['label'] ); ?></a>
				<?php endforeach; ?>
				<a class="bm-btn bm-btn--sm" href="<?php echo esc_url( bm_reservation_url() ); ?>">Zimmer anfragen</a>
			</nav>

			<div class="bm-header__mobile">
				<a class="bm-icon-btn" href="<?php echo esc_attr( $tel ); ?>" aria-label="<?php echo esc_attr( 'Anrufen: ' . bm_setting( 'phone_display' ) ); ?>"><?php echo bm_icon( 'phone' ); // phpcs:ignore ?></a>
				<button type="button" class="bm-icon-btn" data-bm-menu-toggle aria-expanded="false" aria-controls="bm-mobile-menu" aria-label="Menü öffnen">
					<span class="bm-icon-open"><?php echo bm_icon( 'menu', 'bm-icon bm-icon--lg' ); // phpcs:ignore ?></span>
					<span class="bm-icon-close"><?php echo bm_icon( 'x', 'bm-icon bm-icon--lg' ); // phpcs:ignore ?></span>
				</button>
			</div>
		</div>

		<nav id="bm-mobile-menu" class="bm-mobile-nav" aria-label="Mobile Navigation" hidden>
			<ul>
				<?php foreach ( $items as $item ) : ?>
					<?php $current = bm_is_current( $item['url'] ); ?>
					<li><a class="<?php echo $current ? 'is-current' : ''; ?>" href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $item['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
			<a class="bm-btn bm-btn--block" href="<?php echo esc_url( bm_reservation_url() ); ?>">Zimmer anfragen</a>
			<p class="bm-mobile-nav__contact"><?php echo esc_html( bm_setting( 'phone_display' ) . ' · ' . bm_setting( 'email' ) ); ?></p>
		</nav>
	</header>
	<?php
	return (string) ob_get_clean();
}

function bm_render_footer(): string {
	[ $logo_main, $logo_sub ] = bm_logo_parts();
	$address                  = bm_setting( 'street' ) . ', ' . bm_setting( 'zip' ) . ' ' . bm_setting( 'city' );

	ob_start();
	?>
	<footer <?php echo get_block_wrapper_attributes( array( 'class' => 'bm-footer' ) ); // phpcs:ignore ?>>
		<div class="bm-footer__grid">
			<div>
				<p class="bm-logo__main bm-logo__main--light"><?php echo esc_html( $logo_main ); ?></p>
				<p class="bm-logo__sub bm-logo__sub--light"><?php echo esc_html( $logo_sub ); ?></p>
				<p class="bm-footer__text">Familiengeführtes Motel im Herzen des Marchfelds – modern, sauber und fair. Zwischen Wien und Bratislava.</p>
			</div>
			<div>
				<p class="bm-footer__title">Navigation</p>
				<ul class="bm-footer__list">
					<?php foreach ( bm_menu_items( 'primary' ) as $item ) : ?>
						<li><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div>
				<p class="bm-footer__title">Kontakt</p>
				<ul class="bm-footer__list bm-footer__contact">
					<li><?php echo bm_icon( 'map-pin' ); // phpcs:ignore ?><span><?php echo esc_html( $address ); ?></span></li>
					<li><?php echo bm_icon( 'phone' ); // phpcs:ignore ?><a href="<?php echo esc_attr( bm_tel_href( bm_setting( 'phone' ) ) ); ?>"><?php echo esc_html( bm_setting( 'phone_display' ) ); ?></a></li>
					<li><?php echo bm_icon( 'mail' ); // phpcs:ignore ?><a href="<?php echo esc_attr( 'mailto:' . bm_setting( 'email' ) ); ?>"><?php echo esc_html( bm_setting( 'email' ) ); ?></a></li>
					<li><?php echo bm_icon( 'clock' ); // phpcs:ignore ?><span>Check-in <?php echo esc_html( bm_setting( 'check_in' ) ); ?><br>Check-out <?php echo esc_html( bm_setting( 'check_out' ) ); ?></span></li>
				</ul>
			</div>
			<div>
				<p class="bm-footer__title">Rechtliches</p>
				<ul class="bm-footer__list">
					<?php foreach ( bm_menu_items( 'legal' ) as $item ) : ?>
						<li><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<div class="bm-footer__bottom">
			<div class="bm-footer__bottom-inner">
				<p>© <?php echo esc_html( wp_date( 'Y' ) . ' ' . bm_setting( 'legal_name' ) . ' · ' . bm_setting( 'owner' ) ); ?></p>
				<p><?php echo esc_html( bm_setting( 'street' ) . ' · ' . bm_setting( 'zip' ) . ' ' . bm_setting( 'city' ) . ' · ' . bm_setting( 'country' ) ); ?></p>
			</div>
		</div>
	</footer>
	<?php
	return (string) ob_get_clean();
}

function bm_render_trust_bar(): string {
	$items = array(
		array( 'circle-parking', 'Gratis Parkplatz', 'direkt beim Motel' ),
		array( 'wifi', 'Kostenloses WLAN', 'in allen Zimmern' ),
		array( 'bike', 'Fahrradgarage', 'absperrbar & sicher' ),
		array( 'map-pin', 'Zentrale Lage', 'zwischen Wien & Bratislava' ),
	);
	$html = '<ul class="bm-trust__list">';
	foreach ( $items as [ $icon, $label, $sub ] ) {
		$html .= sprintf(
			'<li><span class="bm-icon-tile bm-icon-tile--white">%s</span><div><p class="bm-trust__label">%s</p><p class="bm-trust__sub">%s</p></div></li>',
			bm_icon( $icon ),
			esc_html( $label ),
			esc_html( $sub )
		);
	}
	$html .= '</ul>';
	return bm_wrap( 'bm-trust', $html, 'section', array( 'aria-label' => 'Auf einen Blick' ) );
}

function bm_render_location_highlights(): string {
	$items = array(
		array( 'bird', 'Storchenstadt Marchegg', 'Die größte baumbrütende Storchenkolonie Mitteleuropas – direkt vor der Haustür.' ),
		array( 'landmark', 'Marchfeldschlösser', 'Schloss Marchegg, Schloss Hof und Schloss Niederweiden in unmittelbarer Nähe.' ),
		array( 'train-front', 'Wien & Bratislava', 'Beide Metropolen bequem mit dem Auto oder direkt mit dem Zug erreichbar.' ),
	);
	$html = '';
	foreach ( $items as [ $icon, $title, $text ] ) {
		$html .= sprintf(
			'<li class="bm-highlight" data-bm-reveal>%s<h3>%s</h3><p>%s</p></li>',
			bm_icon( $icon, 'bm-icon bm-icon--xl' ),
			esc_html( $title ),
			esc_html( $text )
		);
	}
	return bm_wrap( 'bm-highlights', $html, 'ul' );
}

function bm_render_cta_banner(): string {
	ob_start();
	?>
	<section <?php echo get_block_wrapper_attributes( array( 'class' => 'bm-cta' ) ); // phpcs:ignore ?> aria-labelledby="bm-cta-title">
		<div class="bm-cta__inner" data-bm-reveal>
			<h2 id="bm-cta-title">Reservieren Sie gleich Ihr Zimmer!</h2>
			<p>Senden Sie uns eine unverbindliche Anfrage – wir prüfen die Verfügbarkeit und melden uns rasch mit einer Bestätigung.</p>
			<div class="bm-cta__actions">
				<a class="bm-btn bm-btn--amber" href="<?php echo esc_url( bm_reservation_url() ); ?>">Zimmer anfragen</a>
				<a class="bm-btn bm-btn--ghost" href="<?php echo esc_attr( bm_tel_href( bm_setting( 'phone' ) ) ); ?>"><?php echo bm_icon( 'phone', 'bm-icon bm-icon--sm' ); // phpcs:ignore ?><?php echo esc_html( bm_setting( 'phone_display' ) ); ?></a>
			</div>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

function bm_render_contact_card(): string {
	$rows = array(
		array( 'map-pin', 'Adresse', esc_html( bm_setting( 'street' ) ) . '<br>' . esc_html( bm_setting( 'zip' ) . ' ' . bm_setting( 'city' ) . ', ' . bm_setting( 'country' ) ), '' ),
		array( 'phone', 'Telefon', sprintf( '<a href="%s">%s</a>', esc_attr( bm_tel_href( bm_setting( 'phone' ) ) ), esc_html( bm_setting( 'phone_display' ) ) ), '' ),
		array( 'mail', 'E-Mail', sprintf( '<a href="%s">%s</a>', esc_attr( 'mailto:' . bm_setting( 'email' ) ), esc_html( bm_setting( 'email' ) ) ), '' ),
		array( 'clock', 'Check-in & Check-out', 'Check-in: ' . esc_html( bm_setting( 'check_in' ) ) . '<br>Check-out: ' . esc_html( bm_setting( 'check_out' ) ), esc_html( bm_setting( 'check_in_note' ) ) ),
	);

	$body = '';
	foreach ( $rows as [ $icon, $label, $value, $note ] ) {
		$body .= sprintf(
			'<div class="bm-contact__row"><span class="bm-icon-tile">%s</span><div><p class="bm-contact__label">%s</p><p class="bm-contact__value">%s</p>%s</div></div>',
			bm_icon( $icon ),
			esc_html( $label ),
			$value,
			$note ? '<p class="bm-contact__note">' . $note . '</p>' : ''
		);
	}
	$body .= sprintf(
		'<a class="bm-btn bm-btn--outline bm-btn--block" href="%s" target="_blank" rel="noopener noreferrer">%sIn Google Maps öffnen</a>',
		esc_url( bm_setting( 'maps_url' ) ),
		bm_icon( 'external-link', 'bm-icon bm-icon--sm' )
	);

	$html = sprintf(
		'<div class="bm-contact__head"><p class="bm-contact__eyebrow">Ihre Gastgeberin</p><p class="bm-contact__owner">%s</p><p class="bm-contact__company">%s</p></div><div class="bm-contact__body">%s</div>',
		esc_html( bm_setting( 'owner' ) ),
		esc_html( bm_setting( 'legal_name' ) ),
		$body
	);
	return bm_wrap( 'bm-contact bm-panel', $html, 'div', array( 'data-bm-reveal' => '' ) );
}

function bm_render_directions(): string {
	$items = array(
		array( 'map-pin', 'Das Motel liegt zentral in Marchegg – ' . bm_setting( 'street' ) . ', ' . bm_setting( 'zip' ) . ' ' . bm_setting( 'city' ) . ', im Herzen des Marchfelds.' ),
		array( 'car', 'Mit dem Auto: bequem über die Marchfeldstraßen erreichbar – die Metropolen Wien und Bratislava liegen in praktischer Reichweite. Ein kostenloser Parkplatz pro Zimmer wartet direkt beim Haus.' ),
		array( 'train-front', 'Mit der Bahn: Marchegg liegt an der Bahnstrecke zwischen Wien und Bratislava – ideal für autofreie Ausflüge in beide Hauptstädte.' ),
	);
	$list = '';
	foreach ( $items as [ $icon, $text ] ) {
		$list .= '<li>' . bm_icon( $icon, 'bm-icon bm-icon--amber' ) . '<span>' . esc_html( $text ) . '</span></li>';
	}
	return bm_wrap( 'bm-directions bm-panel', '<h2>So finden Sie uns</h2><ul>' . $list . '</ul>', 'div', array( 'data-bm-reveal' => '' ) );
}
