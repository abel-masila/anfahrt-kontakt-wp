<?php
/**
 * Einstellungen → Bernstein-Motel: Stammdaten, Preiskonditionen, Reservierungs-Modus.
 */

defined( 'ABSPATH' ) || exit;

const BM_OPTION = 'bernstein_settings';

/**
 * Felddefinitionen inkl. Standardwerten (Stand der Website-Demo).
 */
function bm_settings_fields(): array {
	return array(
		'kontakt'     => array(
			'title'  => 'Stammdaten & Kontakt',
			'fields' => array(
				'name'          => array( 'Name des Betriebs', 'text', 'Bernstein-Motel Marchegg' ),
				'legal_name'    => array( 'Firmenwortlaut', 'text', 'BERNSTEIN-Motel e.U.' ),
				'owner'         => array( 'Inhaberin / Ansprechperson', 'text', 'Alexandra Pataki' ),
				'street'        => array( 'Straße', 'text', 'Feldgasse 1' ),
				'zip'           => array( 'PLZ', 'text', '2293' ),
				'city'          => array( 'Ort', 'text', 'Marchegg' ),
				'country'       => array( 'Land', 'text', 'Österreich' ),
				'phone'         => array( 'Telefon (für Anruf-Links)', 'text', '+43 660 1414140' ),
				'phone_display' => array( 'Telefon (Anzeige)', 'text', '+43 (0) 660 / 14 14 140' ),
				'email'         => array( 'E-Mail', 'email', 'office@motel-marchegg.at' ),
				'maps_url'      => array( 'Google-Maps-Link', 'url', 'https://www.google.at/maps/place/Bernstein-Motel+Marchegg/@48.261801,16.910076,17z/data=!3m1!4b1!4m2!3m1!1s0x0:0x87b8d6e84ce172d1' ),
			),
		),
		'aufenthalt'  => array(
			'title'  => 'Aufenthalt',
			'fields' => array(
				'check_in'      => array( 'Check-in', 'text', '14:00 – 21:00 Uhr' ),
				'check_in_note' => array( 'Hinweis zum Check-in', 'text', 'Ein späterer Check-in ist nach Vereinbarung möglich.' ),
				'check_out'     => array( 'Check-out', 'text', 'bis 10:00 Uhr' ),
				'breakfast'     => array( 'Frühstück', 'textarea', 'Für ein herzhaftes Frühstück empfehlen wir das Café Bernstein – direkt gegenüber dem Motel. Montag bis Samstag ab 6:30 Uhr, an Sonn- und Feiertagen ab 8:00 Uhr. Für Frühaufsteher nach Voranmeldung Montag bis Freitag bereits ab 6:00 Uhr.' ),
				'payment'       => array( 'Bezahlung', 'textarea', 'Zahlung mit Bankomat- und Kreditkarte (VISA und Mastercard) möglich.' ),
			),
		),
		'preise'      => array(
			'title'  => 'Preise & Konditionen',
			'fields' => array(
				'single_price'    => array( 'Einzelbelegung (€ pro Nacht)', 'number', '55' ),
				'tourist_tax'     => array( 'Nächtigungstaxe (€ pro Person/Nacht, ab 15 Jahren)', 'number', '2.60' ),
				'dog_fee'         => array( 'Hund (€ pro Nacht)', 'number', '9' ),
				'prices_valid'    => array( 'Gültigkeit der Preise', 'text', 'Preise gültig ab 01.01.2026' ),
				'prices_includes' => array( 'Preis-Hinweis', 'text', 'inkl. 10 % MwSt., exkl. Frühstück und Nächtigungstaxe' ),
			),
		),
		'reservierung' => array(
			'title'  => 'Reservierung',
			'fields' => array(
				'enquiry_retention_days' => array( 'Anfragen automatisch löschen nach (Tagen, 0 = nie)', 'number', '180' ),
				'reservation_mode' => array(
					'Reservierungs-Modus',
					'select',
					'enquiry',
					array(
						'enquiry'      => 'Anfrage-Formular (Standard)',
						'booking-demo' => 'Buchungs-Vorschau (Prototyp)',
					),
				),
			),
		),
	);
}

function bm_settings_defaults(): array {
	$defaults = array();
	foreach ( bm_settings_fields() as $section ) {
		foreach ( $section['fields'] as $key => $field ) {
			$defaults[ $key ] = $field[2];
		}
	}
	return $defaults;
}

/**
 * Einzelne Einstellung lesen (mit Standardwert).
 */
function bm_setting( string $key ): string {
	$values = wp_parse_args( (array) get_option( BM_OPTION, array() ), bm_settings_defaults() );
	return (string) ( $values[ $key ] ?? '' );
}

function bm_setting_float( string $key ): float {
	return (float) str_replace( ',', '.', bm_setting( $key ) );
}

/** Texte, die aus den Preisfeldern abgeleitet werden. */
function bm_tourist_tax_text(): string {
	return 'Die Nächtigungstaxe beträgt ' . bm_eur( bm_setting_float( 'tourist_tax' ) ) . ' pro Person und Nacht (zahlbar ab dem vollendeten 15. Lebensjahr).';
}

function bm_dog_fee_text(): string {
	return 'Hunde sind herzlich willkommen: ' . bm_eur( bm_setting_float( 'dog_fee' ) ) . ' pro Nacht.';
}

function bm_single_text(): string {
	return 'Alle Zimmer sind auch als Einzelzimmer reservierbar: ' . bm_eur( bm_setting_float( 'single_price' ) ) . ' pro Nacht.';
}

add_action(
	'admin_menu',
	function () {
		add_options_page( 'Bernstein-Motel', 'Bernstein-Motel', 'manage_options', 'bernstein-settings', 'bm_render_settings_page' );
	}
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'bernstein_settings_group',
			BM_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => 'bm_sanitize_settings',
				'default'           => bm_settings_defaults(),
			)
		);
	}
);

function bm_sanitize_settings( $input ): array {
	$input = is_array( $input ) ? $input : array();
	$clean = array();
	foreach ( bm_settings_fields() as $section ) {
		foreach ( $section['fields'] as $key => $field ) {
			$value = $input[ $key ] ?? $field[2];
			switch ( $field[1] ) {
				case 'email':
					$clean[ $key ] = sanitize_email( $value );
					break;
				case 'url':
					$clean[ $key ] = esc_url_raw( $value );
					break;
				case 'number':
					$clean[ $key ] = (string) max( 0, (float) str_replace( ',', '.', $value ) );
					break;
				case 'textarea':
					$clean[ $key ] = sanitize_textarea_field( $value );
					break;
				case 'select':
					$clean[ $key ] = array_key_exists( $value, $field[3] ) ? $value : $field[2];
					break;
				default:
					$clean[ $key ] = sanitize_text_field( $value );
			}
		}
	}
	return $clean;
}

function bm_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$values = wp_parse_args( (array) get_option( BM_OPTION, array() ), bm_settings_defaults() );
	?>
	<div class="wrap">
		<h1>Bernstein-Motel</h1>
		<p>Diese Angaben erscheinen in Kopf- und Fußbereich, auf der Kontaktseite, in der Preisübersicht und im Anfrage-Formular.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'bernstein_settings_group' ); ?>
			<?php foreach ( bm_settings_fields() as $section ) : ?>
				<h2><?php echo esc_html( $section['title'] ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( $section['fields'] as $key => $field ) : ?>
						<?php
						$id    = 'bm-' . $key;
						$name  = BM_OPTION . '[' . $key . ']';
						$value = $values[ $key ];
						?>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field[0] ); ?></label></th>
							<td>
								<?php if ( 'textarea' === $field[1] ) : ?>
									<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="3" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
								<?php elseif ( 'select' === $field[1] ) : ?>
									<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
										<?php foreach ( $field[3] as $opt => $label ) : ?>
											<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $value, $opt ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description">Legt fest, wohin alle „Zimmer anfragen“-Buttons führen: zum Anfrage-Formular (/reservierung) oder zur Buchungs-Vorschau (/buchung).</p>
								<?php else : ?>
									<input type="<?php echo 'number' === $field[1] ? 'number' : esc_attr( $field[1] ); ?>" <?php echo 'number' === $field[1] ? 'step="0.01" min="0"' : ''; ?> id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endforeach; ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
