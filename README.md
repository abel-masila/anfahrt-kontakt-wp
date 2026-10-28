# Bernstein-Motel Marchegg – WordPress

WordPress-Version der Website-Demo (Next.js-Vorlage: https://anfahrt-kontakt.vercel.app).
Gleiche Seiten, gleiche Adressen, gleiches Design – aber alle Inhalte sind in wp-admin bearbeitbar.

## Test-Link (WordPress Playground)

https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/abel-masila/anfahrt-kontakt-wp/main/blueprint.json

- Startet eine vollständige WordPress-Installation im Browser (erster Aufruf ca. 20–40 s).
- Jeder Besucher bekommt eine **eigene, frische Kopie**. Änderungen und Anfragen werden **nicht dauerhaft** gespeichert und sind für niemand anderen sichtbar.
- Kein E-Mail-Versand aus Playground.
- wp-admin: `/wp-admin/` (bereits angemeldet als `admin`).

## Design

- **Rot #C82027** – exakt aus dem BM-Logo gemessen; für Titel, Buttons und Akzente. Dunkle Flächen in Nachtblau, Hintergrund weiß wie die Fassade.
- **Lobster** (kräftige Schreibschrift) – passend zum Logo; nur für große Titel und die Unterschrift.
- **Barlow Condensed** – wie „BERNSTEIN-MOTEL MARCHEGG“ im Logo; für Navigation, Dachzeilen, Preise, Buttons.
- **Inter** – Fließtext.
- Das echte BM-Logo (`plugins/bernstein-core/assets/logo-bm.png`, 190 px) steht im Kopf- und Fußbereich und dient als Favicon. Für den Launch eine Vektor-Version (SVG/PDF) beim Motel anfragen.

## Aufbau

| Ordner | Inhalt |
| --- | --- |
| `themes/bernstein` | Block-Theme: Farben, Schriften (lokal, ohne Google), Vorlagen, Grundstile |
| `plugins/bernstein-core` | Inhaltstypen, Blöcke, Einstellungen, Anfrage-Speicherung, SEO, Start-Inhalte |
| `plugins/bernstein-core/seed` | Start-Inhalte (Seiten, Zimmer, Aktivitäten, FAQ, Galerie, Bilder) |
| `blueprint.json` | Playground-Test-Link (lädt Theme und Plugin aus diesem Repo, Branch `main`) |
| `blueprint.local.json` | Gleiches Setup lokal, mit den Dateien aus diesem Ordner |
| `dist/` | Installierbare ZIPs (`npm run build`) |

### Was die Kundin in wp-admin bearbeiten kann

- **Zimmer & Preise** – Name, Beschreibung, Belegung, Preis pro Person/Nacht, Ausstattung, Bild. Änderungen erscheinen sofort auf Startseite, Zimmerseite, Preisübersicht und in der Buchungs-Vorschau.
- **Aktivitäten**, **FAQ**, **Galerie** (mit Kategorie und Reihenfolge).
- **Anfragen** – jede Anfrage über `/reservierung/` wird gespeichert; Status Neu / Bestätigt / Abgelehnt, Zähler für neue Anfragen im Menü.
- **Einstellungen → Bernstein-Motel** – Kontaktdaten, Check-in/-out, Frühstück, Bezahlung, Einzelzimmerpreis, Nächtigungstaxe, Hundepauschale, Reservierungs-Modus.
- **Seiten** – alle Texte im Block-Editor; die Bernstein-Blöcke (Zimmer-Karten, Galerie, Formular …) sind in der Block-Auswahl unter „Bernstein-Motel“.
- **Design → Menüs** – Hauptnavigation und Rechtliches.

### Reservierungs-Modus

Einstellungen → Bernstein-Motel → *Reservierungs-Modus*:

- **Anfrage-Formular** (Standard): Alle „Zimmer anfragen“-Buttons führen zu `/reservierung/`.
- **Buchungs-Vorschau**: Die Buttons führen zum Prototyp `/buchung/`.

In eigenen Buttons im Editor kann `#reservieren` als Link eingetragen werden – er zeigt automatisch auf das Ziel des gewählten Modus.

### Anfrage-Formular

- Prüft alle Felder im Browser **und** am Server, speichert die Anfrage in der Datenbank (wp-admin → Anfragen) und zeigt eine Bestätigung. Die Anfrage ist ausdrücklich **keine verbindliche Buchung**.
- Zusätzlich kann der Gast eine vorausgefüllte E-Mail an office@motel-marchegg.at öffnen. Falls das Speichern fehlschlägt, wird die E-Mail zum Hauptweg.
- Schutz: Prüfung aller Felder am Server, nur Anfragen von der eigenen Website (Origin-Prüfung), verstecktes Spam-Feld, max. 10 Anfragen pro 10 Minuten je Absender. Es werden keine IP-Adressen gespeichert.
- **E-Mail-Benachrichtigung ans Motel** ist automatisch nur im Live-Betrieb aktiv (`WP_ENVIRONMENT_TYPE` = `production`) – Test-Kopien verschicken nie E-Mails. Erzwingen oder abschalten mit `define( 'BERNSTEIN_ENQUIRY_MAIL', true|false );` in `wp-config.php`.
- **Datenschutz:** Anfragen sehen nur Administratoren und Redakteure. Alte Anfragen werden automatisch gelöscht (Einstellungen → Bernstein-Motel, Standard 180 Tage). Auskunft und Löschung je Gast über **Werkzeuge → Personenbezogene Daten exportieren/löschen**.

Sicherheit, Umgebungen und empfohlene `wp-config.php`-Einstellungen: siehe [MIGRATION.md](MIGRATION.md#sicherheit--umgebungen-wp-configphp).

### Buchungs-Vorschau (`/buchung/`)

Rein visueller Prototyp mit Preisberechnung. Prüft **keine** Verfügbarkeit, bucht und kassiert nichts; „Weiter zur Buchung“ öffnet einen Hinweis. Die Seite ist auf `noindex` gesetzt und nicht in der Sitemap.

## Lokal entwickeln

Voraussetzung: Node.js und Docker.

```bash
npm install
npm start            # WordPress unter http://localhost:8888 (admin / password), Inhalte werden eingespielt
npm run seed:force   # Inhalte auf den Demo-Stand zurücksetzen
npm run playground   # Playground lokal mit blueprint.local.json (http://localhost:9400)
npm run build        # ZIPs nach dist/
npm stop
```

## Vor dem Launch prüfen

- **Impressum, Datenschutz, AGB** sind Demo-Inhalte von der bestehenden Website und müssen von der Kundin rechtlich geprüft und vervollständigt werden (u. a. UID-Nummer, Gewerbebehörde, Kammerzugehörigkeit).
- **Datenschutzerklärung** um die Speicherung von Formular-Anfragen ergänzen (Zweck, Speicherdauer, Löschung). Alte Anfragen regelmäßig löschen.
- Bilder sind die vorhandenen 960 × 500 px Fotos der bestehenden Website – für den Launch neue, hochauflösende Fotos empfehlen.
- `wp-config.php` am Live-Server einstellen und Mailversand einrichten (siehe MIGRATION.md).

Umzug auf einen echten Server: siehe [MIGRATION.md](MIGRATION.md).
