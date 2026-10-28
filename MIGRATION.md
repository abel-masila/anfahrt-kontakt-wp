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
7. Mailversand einrichten (z. B. SMTP-Plugin mit dem Postfach des Motels) und die Benachrichtigung bei neuen Anfragen einschalten – siehe README, Abschnitt „Anfrage-Formular“.
8. Vor dem Launch: Impressum, Datenschutz, AGB von der Kundin prüfen lassen; **Einstellungen → Lesen → „Suchmaschinen davon abhalten …“** deaktivieren, sobald die Seite öffentlich sein soll.

Die Einspielung legt nur Fehlendes an. Mehrfaches Ausführen ist unbedenklich; „überschreiben“ setzt Seiten und Inhalte auf den Demo-Stand zurück.

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
- Schriften (Damion, Barlow Condensed, Inter) liegen im Theme; es werden keine Google-Server kontaktiert.
