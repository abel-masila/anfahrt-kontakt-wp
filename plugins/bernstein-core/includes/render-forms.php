<?php
/**
 * Anfrage-Formular und Buchungs-Vorschau.
 *
 * Das Anfrage-Formular speichert jede Anfrage in der Datenbank (wp-admin → Anfragen,
 * siehe includes/enquiries.php) und bietet zusätzlich eine vorausgefüllte E-Mail
 * (mailto:) an. Schlägt das Speichern fehl, bleibt die E-Mail als Weg zum Motel.
 */

defined( 'ABSPATH' ) || exit;

/**
 * HOOK: true zurückgeben (add_filter( 'bernstein_enquiry_server_mail', '__return_true' )),
 * damit das Motel bei jeder neuen Anfrage zusätzlich per wp_mail benachrichtigt wird.
 * Voraussetzung: Mailversand am Server (z. B. SMTP-Plugin) ist eingerichtet.
 */
function bm_enquiry_server_mail_enabled(): bool {
	return (bool) apply_filters( 'bernstein_enquiry_server_mail', false );
}

function bm_render_reservation_form(): string {
	$rooms      = bm_rooms();
	$owner_name = explode( ' ', trim( bm_setting( 'owner' ) ) );
	$config     = array(
		'email'      => bm_setting( 'email' ),
		'salutation' => 'Sehr geehrte Frau ' . end( $owner_name ) . ',',
		'tel'        => bm_tel_href( bm_setting( 'phone' ) ),
		'endpoint'   => esc_url_raw( rest_url( 'bernstein/v1/enquiry' ) ),
		'rooms'      => array_map( fn( $r ) => array( 'id' => $r['id'], 'name' => $r['name'] ), $rooms ),
	);

	$room_options = '';
	foreach ( $rooms as $room ) {
		$room_options .= sprintf( '<option value="%s">%s</option>', esc_attr( $room['id'] ), esc_html( $room['name'] ) );
	}
	$guest_options = '';
	foreach ( range( 1, 6 ) as $n ) {
		$guest_options .= sprintf( '<option value="%1$d"%2$s>%1$d %3$s</option>', $n, 2 === $n ? ' selected' : '', 1 === $n ? 'Person' : 'Personen' );
	}
	$count_options = '';
	foreach ( range( 1, 4 ) as $n ) {
		$count_options .= sprintf( '<option value="%1$d">%1$d Zimmer</option>', $n );
	}

	$field = function ( string $id, string $label, string $type, string $autocomplete = '' ) {
		return sprintf(
			'<div class="bm-field"><label for="bm-%1$s">%2$s *</label><input id="bm-%1$s" name="%1$s" type="%3$s"%4$s aria-describedby="bm-err-%1$s"><p class="bm-field__error" id="bm-err-%1$s" hidden></p></div>',
			esc_attr( $id ),
			esc_html( $label ),
			esc_attr( $type ),
			$autocomplete ? ' autocomplete="' . esc_attr( $autocomplete ) . '"' : ''
		);
	};

	ob_start();
	?>
	<div <?php echo get_block_wrapper_attributes( array( 'class' => 'bm-reservation' ) ); // phpcs:ignore ?> data-bm-reservation data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
		<form class="bm-form bm-panel" novalidate>
			<div class="bm-form__grid">
				<?php
				echo $field( 'firstName', 'Vorname', 'text', 'given-name' ); // phpcs:ignore
				echo $field( 'lastName', 'Nachname', 'text', 'family-name' ); // phpcs:ignore
				echo $field( 'email', 'E-Mail', 'email', 'email' ); // phpcs:ignore
				echo $field( 'phone', 'Telefon', 'tel', 'tel' ); // phpcs:ignore
				echo $field( 'arrival', 'Anreise', 'date' ); // phpcs:ignore
				echo $field( 'departure', 'Abreise', 'date' ); // phpcs:ignore
				?>
				<div class="bm-field"><label for="bm-guests">Anzahl Personen</label><select id="bm-guests" name="guests"><?php echo $guest_options; // phpcs:ignore ?></select></div>
				<div class="bm-field"><label for="bm-room">Zimmerkategorie</label><select id="bm-room" name="room"><?php echo $room_options; // phpcs:ignore ?></select></div>
				<div class="bm-field"><label for="bm-roomCount">Anzahl Zimmer</label><select id="bm-roomCount" name="roomCount"><?php echo $count_options; // phpcs:ignore ?></select></div>
				<div class="bm-field bm-field--full"><label for="bm-message">Ihre Nachricht (optional)</label><textarea id="bm-message" name="message" rows="4" placeholder="z. B. späte Anreise, Hund, barrierefreies Zimmer …"></textarea></div>
				<div class="bm-field bm-field--full">
					<label class="bm-check">
						<input type="checkbox" name="privacy" aria-describedby="bm-err-privacy">
						<span>Ich habe die <a href="<?php echo esc_url( home_url( '/datenschutz/' ) ); ?>">Datenschutzerklärung</a> gelesen und stimme der Verarbeitung meiner Daten zur Bearbeitung der Anfrage zu. *</span>
					</label>
					<p class="bm-field__error" id="bm-err-privacy" hidden></p>
				</div>
			</div>
			<div class="bm-hp" aria-hidden="true"><label for="bm-website">Website</label><input id="bm-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
			<p class="bm-form__error" role="alert" hidden></p>
			<button type="submit" class="bm-btn bm-btn--lg bm-form__submit">Unverbindlich anfragen</button>
			<p class="bm-form__hint">Nach dem Absenden prüfen wir die Verfügbarkeit. Erst mit unserer Reservierungsbestätigung ist Ihre Reservierung gültig.</p>
		</form>

		<div class="bm-success bm-panel" hidden tabindex="-1">
			<?php echo bm_icon( 'circle-check-big', 'bm-icon bm-success__icon' ); // phpcs:ignore ?>
			<h2 class="bm-success__title"></h2>
			<p class="bm-success__summary"></p>
			<p class="bm-success__note">Bitte beachten Sie: Dies ist noch <strong>keine verbindliche Buchung</strong>. Wir prüfen die Verfügbarkeit und melden uns rasch mit einer Reservierungsbestätigung. Erst mit dieser Bestätigung ist Ihre Reservierung gültig.</p>
			<p class="bm-success__fallback" hidden>Ihre Anfrage konnte gerade nicht automatisch übermittelt werden. Bitte senden Sie sie mit einem Klick per E-Mail – alle Angaben sind bereits ausgefüllt.</p>
			<div class="bm-success__actions">
				<a class="bm-btn bm-btn--outline" data-bm-mailto href="#"><?php echo bm_icon( 'mail', 'bm-icon bm-icon--sm' ); // phpcs:ignore ?><span data-bm-mailto-label>Kopie per E-Mail senden</span></a>
				<a class="bm-btn" href="<?php echo esc_attr( $config['tel'] ); ?>"><?php echo bm_icon( 'phone', 'bm-icon bm-icon--sm' ); // phpcs:ignore ?>Oder gleich anrufen</a>
			</div>
			<button type="button" class="bm-link-btn" data-bm-reset>Neue Anfrage erstellen</button>
		</div>

		<?php if ( 'booking-demo' === bm_setting( 'reservation_mode' ) ) : ?>
			<p class="bm-more"><a href="<?php echo esc_url( home_url( '/buchung/' ) ); ?>">Oder Online-Buchung ausprobieren</a></p>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

function bm_render_booking_demo(): string {
	$rooms  = bm_rooms();
	$config = array(
		'rooms'       => array_map(
			fn( $r ) => array(
				'id'       => $r['id'],
				'name'     => $r['name'],
				'capacity' => $r['capacity'],
				'price'    => $r['price'],
			),
			$rooms
		),
		'singlePrice' => bm_setting_float( 'single_price' ),
		'touristTax'  => bm_setting_float( 'tourist_tax' ),
	);

	$room_list = '';
	foreach ( $rooms as $i => $room ) {
		$room_list .= sprintf(
			'<label class="bm-room-option" data-capacity="%1$d">
				<input type="radio" name="bm-room" value="%2$s"%3$s>
				<span class="bm-room-option__img">%4$s</span>
				<span class="bm-room-option__text"><span class="bm-room-option__name">%5$s</span><span class="bm-room-option__cap">%6$s</span></span>
				<span class="bm-room-option__price"><span>%7$s</span><small>p. P. / Nacht</small></span>
				%8$s
			</label>',
			(int) $room['capacity'],
			esc_attr( $room['id'] ),
			0 === $i ? ' checked' : '',
			bm_image_html( $room['image_id'], '', '96px' ),
			esc_html( $room['name'] ),
			esc_html( $room['capacity_label'] ),
			esc_html( bm_eur( $room['price'] ) ),
			bm_icon( 'check', 'bm-icon bm-room-option__check' )
		);
	}

	ob_start();
	?>
	<div <?php echo get_block_wrapper_attributes( array( 'class' => 'bm-booking' ) ); // phpcs:ignore ?> data-bm-booking data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
		<div class="bm-booking__main">
			<div class="bm-panel bm-booking__box">
				<h2><?php echo bm_icon( 'calendar-days', 'bm-icon bm-icon--amber' ); // phpcs:ignore ?>Reisedaten</h2>
				<div class="bm-booking__dates">
					<div class="bm-field"><label for="bd-arrival">Anreise</label><input id="bd-arrival" type="date"></div>
					<div class="bm-field"><label for="bd-departure">Abreise</label><input id="bd-departure" type="date"></div>
					<div class="bm-field"><label for="bd-guests">Personen</label>
						<select id="bd-guests"><option value="1">1 Person</option><option value="2" selected>2 Personen</option><option value="3">3 Personen</option></select>
					</div>
				</div>
			</div>
			<fieldset class="bm-panel bm-booking__box">
				<legend class="screen-reader-text">Zimmerkategorie wählen</legend>
				<h2><?php echo bm_icon( 'users', 'bm-icon bm-icon--amber' ); // phpcs:ignore ?>Zimmer wählen</h2>
				<div class="bm-booking__rooms"><?php echo $room_list; // phpcs:ignore ?></div>
			</fieldset>
		</div>

		<aside class="bm-panel bm-booking__summary" aria-live="polite">
			<h2>Ihre Auswahl</h2>
			<dl>
				<div><dt>Zimmer</dt><dd data-bd="room">–</dd></div>
				<div><dt>Anreise</dt><dd data-bd="arrival">–</dd></div>
				<div><dt>Abreise</dt><dd data-bd="departure">–</dd></div>
				<div><dt>Personen</dt><dd data-bd="guests">–</dd></div>
				<div><dt>Nächte</dt><dd data-bd="nights">–</dd></div>
			</dl>
			<div class="bm-booking__totals">
				<p class="bm-booking__empty">Bitte wählen Sie An- und Abreisedatum, um die Preisübersicht zu sehen.</p>
				<div class="bm-booking__calc" hidden>
					<p class="bm-booking__line"><span data-bd="line"></span><span data-bd="subtotal"></span></p>
					<p class="bm-booking__line bm-booking__line--small"><span>zzgl. Nächtigungstaxe (ab 15 J.)</span><span data-bd="tax"></span></p>
					<p class="bm-booking__total"><span>Gesamt</span><span data-bd="total"></span></p>
					<p class="bm-booking__vat">inkl. 10 % MwSt., exkl. Frühstück</p>
				</div>
			</div>
			<button type="button" class="bm-btn bm-btn--lg bm-btn--block" data-bd="continue" disabled>Weiter zur Buchung</button>
			<p class="bm-booking__hint">Visueller Prototyp – es wird keine echte Verfügbarkeit geprüft.</p>
		</aside>

		<dialog class="bm-modal" aria-labelledby="bd-modal-title">
			<div class="bm-modal__head">
				<span class="bm-modal__icon"><?php echo bm_icon( 'sparkles' ); // phpcs:ignore ?></span>
				<button type="button" class="bm-icon-btn" data-bm-close aria-label="Schließen"><?php echo bm_icon( 'x' ); // phpcs:ignore ?></button>
			</div>
			<h2 id="bd-modal-title">So könnte Ihre Online-Buchung aussehen</h2>
			<p>Dies ist ein visueller Prototyp. In der fertigen Website wird dieser Schritt mit dem Buchungssystem Ihrer Wahl verbunden – inklusive echter Verfügbarkeitsprüfung, Buchungsbestätigung und optionaler Anzahlung.</p>
			<p>Bis dahin nehmen wir Reservierungen gerne per Anfrage, Telefon oder E-Mail entgegen.</p>
			<button type="button" class="bm-btn bm-btn--block" data-bm-close>Verstanden</button>
		</dialog>
	</div>
	<?php
	return (string) ob_get_clean();
}
