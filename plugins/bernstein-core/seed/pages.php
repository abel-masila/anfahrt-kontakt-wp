<?php
/**
 * Seiten-Inhalte als Block-Markup.
 *
 * HINWEIS: Impressum, Datenschutz und AGB stammen von der bestehenden Website und sind
 * Demo-Inhalte. Sie müssen vor dem Produktiv-Launch von der Auftraggeberin geprüft und
 * vervollständigt werden (u. a. UID-Nummer, Gewerbebehörde, Kammerzugehörigkeit).
 *
 * @param array $media Dateiname => Attachment-ID.
 */

defined( 'ABSPATH' ) || exit;

function bms_pages( array $media ): array {
	$cta   = bms_block( 'cta-banner' );
	$owner = 'Unser Familienbetrieb wird von mir persönlich geführt und betreut. Möchten Sie ein Zimmer reservieren? Kontaktieren Sie mich per Telefon oder E-Mail – oder senden Sie eine Online-Anfrage. Ich freue mich auf Ihr Kommen!';

	$home = array(
		bms_hero(
			$media['hero-reisende-marchegg.jpg'],
			array(
				bms_p( 'Familiengeführt · Marchegg im Marchfeld', 'bm-eyebrow' ),
				bms_h( 'Willkommen im Bernstein Motel in Marchegg!', 1 ),
				bms_p( 'Modern, sauber und fair – Ihr ideales Sprungbrett in einen erlebnisreichen Tag zwischen Storchenstadt, Marchfeldschlössern und den Metropolen Wien und Bratislava.', 'bm-lead' ),
				bms_buttons(
					array(
						array( 'Zimmer anfragen', '#reservieren', 'red' ),
						array( 'Zimmer ansehen', '/zimmer/', 'ghost' ),
					),
					'bm-actions'
				),
			)
		),
		bms_block( 'trust-bar' ),
		bms_section(
			array(
				bms_heading( 'Zimmer & Preise', 'Moderne Zimmer zu fairen Preisen', 'Sechzehn Doppelzimmer (eines barrierefrei), drei Standard- und drei Comfort-Dreibettzimmer. Alle Zimmer sind auch als Einzelzimmer reservierbar: € 55,00 pro Nacht.' ),
				bms_block( 'rooms', array( 'compact' => true ) ),
				bms_more( 'Alle Zimmer & Preisdetails', '/zimmer/' ),
			)
		),
		bms_section(
			array(
				bms_columns(
					array(
						array( bms_image( $media['rundgang-bernsteinmotelmarchegg.jpg'], 'bm-photo' ) ),
						array(
							bms_p( 'Über das Motel', 'bm-eyebrow' ),
							bms_h( 'Persönlich geführt, ehrlich kalkuliert' ),
							bms_p( 'Ob Privat- oder Geschäftsreisender – durch die zentrale Lage des Motels und das vielfältige Angebot in der Umgebung ist das Bernstein-Motel Marchegg das ideale Sprungbrett in einen erlebnisreichen Tag. Unser Beherbergungsbetrieb bietet Ihnen eine moderne, saubere Übernachtungsmöglichkeit zu einem fairen Preis.' ),
							bms_p( 'Eingebettet in eine Landschaft aus Marchfeldschlössern, Rad- und Wanderwegen und den geheimnisvollen Aulandschaften von March und Donau – und dank der zentralen Lage sind die Metropolen Wien und Bratislava sowohl mit dem Auto als auch mit dem Zug erreichbar – ist unser Motel ein perfekter Ausgangspunkt.' ),
							bms_quote( '„' . $owner . '“', '— Alexandra Pataki, Gastgeberin' ),
						),
					),
					'bm-split',
					true
				),
			),
			'bm-bg-alt'
		),
		bms_section(
			array(
				bms_heading( 'Ausstattung', 'Alles, was Sie brauchen', 'Alle Zimmer sind gänzlich gleich ausgestattet – 100 % rauchfrei, mit eigenem Bad, Sat-TV und kostenlosem WLAN.' ),
				bms_block( 'amenities', array( 'limit' => 6 ) ),
				bms_more( 'Gesamte Ausstattung ansehen', '/ausstattung/' ),
			)
		),
		bms_section(
			array(
				bms_heading( 'Die Lage', 'Mitten im Marchfeld – nah an zwei Hauptstädten', '', 2, 'bm-heading--left' ),
				bms_block( 'location-highlights' ),
			),
			'bm-bg-navy'
		),
		bms_section(
			array(
				bms_heading( 'Aktivitäten', 'Erleben Sie die Region', 'Natur, Kultur, Radwege und Kulinarik – rund um Marchegg wartet mehr, als man auf den ersten Blick vermutet.' ),
				bms_block( 'activities', array( 'limit' => 3 ) ),
				bms_more( 'Alle Aktivitäten entdecken', '/aktivitaeten/' ),
			)
		),
		bms_section(
			array(
				bms_heading( 'Galerie', 'Ein erster Rundgang', 'Machen Sie sich ein Bild von unseren Zimmern, dem Haus und der Umgebung.' ),
				bms_block( 'gallery', array( 'preview' => true, 'limit' => 8 ) ),
				bms_more( 'Zur ganzen Galerie', '/galerie/' ),
			),
			'bm-bg-alt'
		),
		bms_section(
			array(
				bms_columns(
					array(
						array(
							bms_p( 'Kontakt', 'bm-eyebrow' ),
							bms_h( 'Wir sind gerne für Sie da' ),
							bms_p( 'Haben Sie Fragen zu Ihrer Reservierung, zur Anreise oder zu unseren Zimmern? Rufen Sie an oder schreiben Sie uns – Alexandra Pataki kümmert sich persönlich um Ihr Anliegen.' ),
							bms_p( 'Antworten auf häufige Fragen finden Sie außerdem in unseren <a href="/fragen-antworten/">Fragen &amp; Antworten</a>.' ),
						),
						array( bms_block( 'contact-card' ) ),
					),
					'bm-split bm-split--top'
				),
			)
		),
		$cta,
	);

	$legal_note = fn( string $text ) => bms_p( esc_html( $text ), 'bm-note' );

	return array(
		'startseite'       => array(
			'title'   => 'Startseite',
			'excerpt' => 'Modernes, familiengeführtes Motel in Marchegg – zwischen Wien und Bratislava. Faire Preise, gratis Parkplatz & WLAN, absperrbare Fahrradgarage. Jetzt Zimmer anfragen!',
			'blocks'  => $home,
			'thumb'   => 'rundgang-bernsteinmotelmarchegg.jpg',
		),
		'zimmer'           => array(
			'title'   => 'Zimmer & Preise',
			'excerpt' => 'Doppelzimmer, 3-Bettzimmer Standard und Comfort – moderne Zimmer zu fairen Preisen im Bernstein-Motel Marchegg. Jetzt unverbindlich anfragen.',
			'blocks'  => array(
				bms_section(
					array(
						bms_heading( 'Zimmer & Preise', 'Reservieren und buchen Sie gleich Ihr Zimmer!', 'Genießen Sie eine oder mehrere Nächte in unseren modernen und gemütlichen Zimmern. Alle Zimmer sind gänzlich gleich ausgestattet und können auch als Einzelzimmer reserviert werden.', 1 ),
						bms_block( 'rooms' ),
						bms_block( 'price-table' ),
						bms_block( 'price-conditions' ),
					),
					'bm-section--top'
				),
				$cta,
			),
		),
		'ausstattung'      => array(
			'title'   => 'Ausstattung',
			'excerpt' => 'Gratis WLAN, kostenloser Parkplatz, absperrbare Fahrradgarage, Gemeinschaftsküche und mehr – die Ausstattung des Bernstein-Motels Marchegg.',
			'blocks'  => array(
				bms_section(
					array(
						bms_heading( 'Ausstattung', 'Durchdacht bis ins Detail', 'Alle Zimmer sind gänzlich gleich ausgestattet – damit Sie sich bei jedem Aufenthalt auf denselben Komfort verlassen können.', 1 ),
						bms_block( 'amenities' ),
					),
					'bm-section--top'
				),
				$cta,
			),
		),
		'aktivitaeten'     => array(
			'title'   => 'Aktivitäten & Umgebung',
			'menu'    => 'Aktivitäten',
			'excerpt' => 'Storchenkolonie, Marchfeldschlösser, Radwege und Kulinarik – entdecken Sie die Umgebung des Bernstein-Motels Marchegg zwischen Wien und Bratislava.',
			'blocks'  => array(
				bms_section(
					array(
						bms_heading( 'Aktivitäten & Umgebung', 'Erlebnisreiche Tage rund um Marchegg', 'Eingebettet in eine Landschaft aus Marchfeldschlössern, Rad- und Wanderwegen und den geheimnisvollen Aulandschaften von March und Donau ist unser Motel der perfekte Ausgangspunkt.', 1 ),
						bms_block( 'activities' ),
					),
					'bm-section--top'
				),
				$cta,
			),
		),
		'galerie'          => array(
			'title'   => 'Galerie & Rundgang',
			'menu'    => 'Galerie',
			'excerpt' => 'Machen Sie einen Rundgang durch das Bernstein-Motel Marchegg – Zimmer, Eingangsbereich, Außenansichten und die Umgebung im Marchfeld.',
			'blocks'  => array(
				bms_section(
					array(
						bms_heading( 'Galerie & Rundgang', 'Machen Sie einen Rundgang durch unser Motel', 'Zimmer, Gemeinschaftsbereiche, Außenansichten und die schönsten Seiten der Umgebung – klicken Sie sich durch.', 1 ),
						bms_block( 'gallery' ),
					),
					'bm-section--top'
				),
				$cta,
			),
		),
		'anfahrt-kontakt'  => array(
			'title'   => 'Anfahrt & Kontakt',
			'excerpt' => 'So erreichen Sie das Bernstein-Motel Marchegg: Feldgasse 1, 2293 Marchegg. Telefon +43 (0) 660 / 14 14 140 – wir freuen uns auf Ihre Anfrage.',
			'blocks'  => array(
				bms_section(
					array(
						bms_heading( 'Anfahrt & Kontakt', 'Wir freuen uns auf Ihr Kommen', $owner, 1 ),
						bms_columns(
							array(
								array( bms_block( 'contact-card' ) ),
								array(
									bms_image( $media['rundgang-motelmarchegg-marchfeld.jpg'], 'bm-photo bm-photo--wide' ),
									bms_block( 'directions' ),
								),
							),
							'bm-split bm-split--top bm-split--even'
						),
					),
					'bm-section--top'
				),
				$cta,
			),
		),
		'reservierung'     => array(
			'title'   => 'Zimmer anfragen',
			'excerpt' => 'Senden Sie eine unverbindliche Zimmeranfrage an das Bernstein-Motel Marchegg – wir prüfen die Verfügbarkeit und bestätigen Ihre Reservierung.',
			'blocks'  => array(
				bms_section(
					array(
						bms_heading( 'Reservierung', 'Zimmer unverbindlich anfragen', 'Füllen Sie das Formular aus – wir prüfen die Verfügbarkeit und melden uns rasch mit einer Reservierungsbestätigung. Erst mit dieser Bestätigung ist Ihre Reservierung gültig.', 1 ),
						bms_block( 'reservation-form' ),
					),
					'bm-section--top bm-section--narrow'
				),
			),
		),
		'buchung'          => array(
			'title'   => 'Online-Buchung (Vorschau)',
			'excerpt' => 'Vorschau auf eine mögliche integrierte Online-Buchung für das Bernstein-Motel Marchegg.',
			'blocks'  => array(
				bms_section(
					array(
						bms_heading( 'Vorschau', 'Ihre Buchung in wenigen Schritten', 'So könnte eine integrierte Online-Buchung auf der neuen Website aussehen: Reisedaten wählen, Zimmer aussuchen, Preis auf einen Blick.', 1 ),
						bms_block( 'booking-demo' ),
						bms_more( 'Lieber klassisch anfragen? Zum Anfrage-Formular', '/reservierung/' ),
					),
					'bm-section--top'
				),
			),
		),
		'fragen-antworten' => array(
			'title'   => 'Fragen & Antworten',
			'excerpt' => 'Antworten auf häufige Fragen zum Bernstein-Motel Marchegg: Check-in, Parken, WLAN, Hunde, Frühstück, Fahrradgarage und Bezahlung.',
			'blocks'  => array(
				bms_section(
					array(
						bms_heading( 'Fragen & Antworten', 'Gut zu wissen', 'Die häufigsten Fragen rund um Ihren Aufenthalt – kurz und klar beantwortet.', 1 ),
						bms_block( 'faq' ),
					),
					'bm-section--top bm-section--narrow'
				),
				$cta,
			),
		),
		'impressum'        => array(
			'title'   => 'Impressum',
			'excerpt' => 'Impressum des Bernstein-Motels Marchegg.',
			'blocks'  => array(
				bms_section(
					array(
						bms_h( 'Impressum', 1 ),
						bms_h( 'Medieninhaberin & Betreiberin' ),
						bms_p( 'BERNSTEIN-Motel e.U.<br>Inhaberin: Alexandra Pataki<br>Feldgasse 1, 2293 Marchegg, Österreich' ),
						bms_h( 'Kontakt' ),
						bms_p( 'Telefon: <a href="tel:+436601414140">+43 (0) 660 / 14 14 140</a><br>E-Mail: <a href="mailto:office@motel-marchegg.at">office@motel-marchegg.at</a><br>Web: www.motel-marchegg.at' ),
						bms_h( 'Unternehmensgegenstand' ),
						bms_p( 'Beherbergungsbetrieb (Motel) in Marchegg, Niederösterreich.' ),
						$legal_note( 'Diese Seite ist Teil einer Design-Demo. Die vollständigen Pflichtangaben (z. B. UID-Nummer, Gewerbebehörde, Kammerzugehörigkeit) werden vor Veröffentlichung ergänzt.' ),
					),
					'bm-section--top bm-prose'
				),
			),
		),
		'datenschutz'      => array(
			'title'   => 'Datenschutzerklärung',
			'menu'    => 'Datenschutz',
			'excerpt' => 'Datenschutzerklärung des Bernstein-Motels Marchegg.',
			'blocks'  => array(
				bms_section(
					array(
						bms_h( 'Datenschutzerklärung', 1 ),
						bms_h( 'Verantwortliche' ),
						bms_p( 'Alexandra Pataki, BERNSTEIN-Motel e.U.<br>Feldgasse 1, 2293 Marchegg<br>E-Mail: office@motel-marchegg.at' ),
						bms_h( 'Rechtsgrundlagen' ),
						bms_p( 'Wir verarbeiten Ihre personenbezogenen Daten auf Grundlage der EU-Datenschutz-Grundverordnung (DSGVO, VO (EU) 2016/679), des Datenschutzgesetzes (DSG) sowie des Telekommunikationsgesetzes (TKG).' ),
						bms_h( 'Verarbeitete Daten' ),
						bms_p( 'Im Rahmen Ihrer Reservierung und Ihres Aufenthalts verarbeiten wir insbesondere: Stammdaten (Name, Anschrift, Kontaktdaten), Daten aus Reisedokumenten (gemäß gesetzlicher Meldepflicht), Zahlungsinformationen sowie Aufenthaltsdaten. Die Daten werden ausschließlich zur Abwicklung Ihrer Reservierung, zur Erfüllung gesetzlicher Pflichten und zur Betreuung während Ihres Aufenthalts verwendet.' ),
						bms_h( 'Ihre Rechte' ),
						bms_p( 'Ihnen stehen die Rechte auf Widerruf, Auskunft, Löschung, Richtigstellung, Einschränkung und Übertragung Ihrer Daten zu. Wenden Sie sich dazu bitte an office@motel-marchegg.at. Beschwerden richten Sie an die Österreichische Datenschutzbehörde, Wien.' ),
						$legal_note( 'Diese Seite ist Teil einer Design-Demo. Die Datenschutzerklärung wird vor Veröffentlichung rechtlich geprüft und finalisiert.' ),
					),
					'bm-section--top bm-prose'
				),
			),
		),
		'agb'              => array(
			'title'   => 'AGB',
			'excerpt' => 'Allgemeine Geschäftsbedingungen des Bernstein-Motels Marchegg.',
			'blocks'  => array(
				bms_section(
					array(
						bms_h( 'Allgemeine Geschäftsbedingungen', 1 ),
						bms_p( 'Für Beherbergungsverträge mit dem BERNSTEIN-Motel e.U. gelten die <strong>„Allgemeinen Geschäftsbedingungen für die Hotellerie (AGBH)“</strong> der Wirtschaftskammer Österreich in der jeweils gültigen Fassung.' ),
						bms_h( 'Reservierung' ),
						bms_p( 'Nach dem Absenden einer Anfrage wird die Verfügbarkeit geprüft. Danach erhalten Sie eine Reservierungsbestätigung von uns. Erst mit dieser Bestätigung ist Ihre Reservierung gültig.' ),
						bms_h( 'Check-in & Check-out' ),
						bms_p( 'Check-in: 14:00 – 21:00 Uhr (Ein späterer Check-in ist nach Vereinbarung möglich.) · Check-out: bis 10:00 Uhr.' ),
						bms_h( 'Bezahlung' ),
						bms_p( 'Zahlung mit Bankomat- und Kreditkarte (VISA und Mastercard) möglich.' ),
						$legal_note( 'Diese Seite ist Teil einer Design-Demo. Die vollständigen AGB werden vor Veröffentlichung von der Betreiberin geprüft und ergänzt.' ),
					),
					'bm-section--top bm-prose'
				),
			),
		),
	);
}
