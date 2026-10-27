# Bernstein-Motel Marchegg – WordPress

WordPress-Version der Website-Demo (Next.js-Vorlage: https://anfahrt-kontakt.vercel.app).
Gleiche Seiten, gleiche Adressen, gleiches Design – aber alle Inhalte sind in wp-admin bearbeitbar.

## Test-Link (WordPress Playground)

https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/abel-masila/anfahrt-kontakt-wp/main/blueprint.json

- Startet eine vollständige WordPress-Installation im Browser (erster Aufruf ca. 20–40 s).
- Jeder Besucher bekommt eine **eigene, frische Kopie**. Änderungen und Anfragen werden **nicht dauerhaft** gespeichert und sind für niemand anderen sichtbar.
- Kein E-Mail-Versand aus Playground.
- wp-admin: `/wp-admin/` (bereits angemeldet als `admin`).

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
- Spam-Schutz: verstecktes Feld + max. 5 Anfragen pro 10 Minuten je Absender. Es werden keine IP-Adressen gespeichert.
- **E-Mail-Benachrichtigung ans Motel** ist vorbereitet, aber aus (kein Mailversand in Test-Umgebungen). Einschalten, sobald am Server Mailversand eingerichtet ist (z. B. SMTP-Plugin):
  ```php
  add_filter( 'bernstein_enquiry_server_mail', '__return_true' );
  ```

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
- Mailversand einrichten und die Benachrichtigung einschalten (siehe oben).

Umzug auf einen echten Server: siehe [MIGRATION.md](MIGRATION.md).
