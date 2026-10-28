# Umzug auf den endgültigen Server

Es gibt zwei Wege – je nachdem, ob die Inhalte schon bearbeitet wurden.

## A) Frische Installation (Inhalte noch im Demo-Stand)

Voraussetzungen beim Hoster: WordPress 6.6 oder neuer, PHP 8.0 oder neuer, HTTPS.

1. WordPress beim Hoster installieren, Sprache **Deutsch (Österreich)** wählen.
2. wp-admin → **Design → Themes → Theme hinzufügen → Theme hochladen** → `dist/bernstein-theme.zip` → installieren → **aktivieren**.
3. wp-admin → **Plugins → Plugin hinzufügen → Plugin hochladen** → `dist/bernstein-core.zip` → installieren → **aktivieren**.
4. Inhalte einspielen – eine der beiden Varianten:
   - **Ohne WP-CLI:** wp-admin → **Werkzeuge → Bernstein-Inhalte** → „Inhalte einspielen“.
   - **Mit WP-CLI:** `wp bernstein seed`
5. **Einstellungen → Permalinks** öffnen und einmal **Speichern** (setzt die Weiterleitungsregeln des Servers).
6. **Einstellungen → Bernstein-Motel** prüfen (Kontaktdaten, Preise, Reservierungs-Modus).
7. `wp-config.php` wie unten unter „Sicherheit & Umgebungen“ einstellen (`WP_ENVIRONMENT_TYPE` = `production`) und Mailversand einrichten (z. B. SMTP-Plugin mit dem Postfach des Motels) – dann kommen Anfragen automatisch auch per E-Mail.
8. Vor dem Launch: Impressum, Datenschutz, AGB von der Kundin prüfen lassen; **Einstellungen → Lesen → „Suchmaschinen davon abhalten …“** deaktivieren, sobald die Seite öffentlich sein soll.

Die Einspielung legt nur Fehlendes an. Mehrfaches Ausführen ist unbedenklich; „überschreiben“ setzt Seiten und Inhalte auf den Demo-Stand zurück.

## Sicherheit & Umgebungen (wp-config.php)

Die Website verhält sich je nach Umgebung unterschiedlich. Die Umgebung wird in `wp-config.php` des Servers festgelegt – **nie im Repository** (wp-config.php, `.env`, Datenbank-Exporte und Backups sind per `.gitignore` ausgeschlossen).

| | `local` (wp-env) | `staging` (Playground, Test-Server) | `production` (live) |
| --- | --- | --- | --- |
| Suchmaschinen | gesperrt (noindex + robots.txt) | gesperrt | erlaubt |
| E-Mail bei neuen Anfragen | aus | aus | an (sofern Mailversand eingerichtet) |
| Inhalte „überschreiben“ (Seed) | erlaubt | erlaubt | gesperrt (CLI nur mit `--yes-production`) |
| Clickjacking-Schutz (X-Frame-Options) | aus | aus | an |

**Empfohlene Einträge für den Live-Server** (oberhalb von „That's all, stop editing!“):

```php
define( 'WP_ENVIRONMENT_TYPE', 'production' );
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'DISALLOW_FILE_EDIT', true );      // kein Code-Editor in wp-admin
define( 'FORCE_SSL_ADMIN', true );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );  // Sicherheits-Updates automatisch

// Optional – Bernstein-Plugin:
// define( 'BERNSTEIN_ENQUIRY_MAIL', true );                       // E-Mail-Benachrichtigung erzwingen (an/aus)
// define( 'BERNSTEIN_ENQUIRY_RATE_LIMIT', 10 );                   // Anfragen pro Absender und 10 Minuten
// define( 'BERNSTEIN_TRUSTED_PROXY_HEADER', 'HTTP_CF_CONNECTING_IP' ); // NUR hinter Cloudflare/Proxy setzen
```

Für einen Test-Server beim Hoster: `define( 'WP_ENVIRONMENT_TYPE', 'staging' );` – dann wird er nicht indexiert und verschickt keine E-Mails.

**Beim Hoster einstellen (gehört nicht ins Plugin):** HTTPS mit automatischer Weiterleitung und HSTS, tägliche Backups (Datenbank + Uploads, verschlüsselt, mit Löschfrist), aktuelle PHP-Version (8.2+), starke Passwörter und Zwei-Faktor-Anmeldung für alle wp-admin-Konten, Datenbank-Präfix nicht `wp_`. Für den Mailversand ein SMTP-Plugin mit dem Postfach des Motels; die Zugangsdaten als Konstanten in `wp-config.php` hinterlegen, nicht in der Datenbank.

**Bereits eingebaut:** XML-RPC gesperrt, Benutzernamen nicht über REST-API oder `?author=` abrufbar, WordPress-Version verborgen, Sicherheits-Header (nosniff, Referrer-Policy, Permissions-Policy), Anfragen nur für Administratoren und Redakteure sichtbar, automatisches Löschen alter Anfragen (Einstellungen → Bernstein-Motel, Standard 180 Tage) sowie Auskunft und Löschung über **Werkzeuge → Personenbezogene Daten exportieren/löschen**.

## B) Bereits bearbeitete Website umziehen

Playground speichert Änderungen nicht dauerhaft – dort gemachte Bearbeitungen müssen auf dem Zielserver wiederholt werden. Für eine bearbeitete Installation auf einem echten Server (z. B. Staging beim Hoster):

1. Auf dem alten Server ein Export-/Umzugs-Plugin installieren (z. B. *All-in-One WP Migration*, *Duplicator* oder *Migrate Guru*) und ein komplettes Paket erstellen (Datenbank, Medien, Theme, Plugins).
2. Auf dem neuen Server WordPress installieren, dasselbe Plugin installieren und das Paket importieren. Das Plugin ersetzt dabei die alte Adresse durch die neue.
3. Alternativ mit WP-CLI: Datenbank exportieren (`wp db export`), `wp-content/uploads` kopieren, auf dem Ziel importieren und `wp search-replace 'https://alt.example' 'https://neu.example' --all-tables` ausführen.
4. Danach: **Einstellungen → Permalinks → Speichern**, Seiten kurz durchklicken, Formular einmal testen.

Interne Links in den Seiten-Inhalten sind relativ gespeichert (`/zimmer/`) und werden zur aktuellen Adresse aufgelöst – sie funktionieren nach dem Umzug ohne Anpassung. Bilder in der Mediathek werden vom Umzugs-Plugin bzw. `search-replace` angepasst.

## Hinweise

- **Anfragen** (wp-admin → Anfragen) enthalten personenbezogene Daten. Beim Umzug werden sie mitkopiert; alte Anfragen regelmäßig löschen und die Datenschutzerklärung entsprechend ergänzen.
- Das Theme braucht das Plugin „Bernstein Core“ – ohne Plugin fehlen Kopf-, Fußbereich und alle Inhalts-Blöcke.
- Schriften (Lobster, Barlow Condensed, Inter) liegen im Theme; es werden keine Google-Server kontaktiert.
