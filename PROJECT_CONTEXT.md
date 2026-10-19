# MechaTraining – Projektkontext (Stand: 2026-10-16)

## Projektziel
Responsive, browserbasierte Lern- und Übungsplattform für erwachsene Mechatroniker-Auszubildende, insbesondere Vorbereitung auf Abschlussprüfung Teil 1 (AP1); AP2 später. Keine App-Installation erforderlich. Professionelle, sachliche Lernumgebung mit dezenten Gamification-Elementen.

## Repository und Live-Betrieb
- GitHub: https://github.com/Joe-Petts/mechatraining (Branch `main`).
- Live-Domain: https://mechatraining.de
- Hosting: ALL-INKL Privat, PHP 8.5.x, PDO SQLite und SQLite3 nachgewiesen.
- Domain-Webroot: `/mechatraining.de/public/`; Projektverzeichnis: `/mechatraining.de/`.
- Deploy: `.github/workflows/deploy.yml`, GitHub Actions bei Push auf `main`, FTPS mit TLS-Zertifikatsprüfung, `lftp mirror --reverse`.
- GitHub Actions Secrets: `FTP_HOST`, `FTP_USER`, `FTP_PASS`, `FTP_REMOTE_DIR` (bei auf Projektverzeichnis beschränktem FTP-Zugang `./`), optional `SETUP_KEY` für die Erstinstallation.
- Niemals Secrets auslesen, veröffentlichen oder in Code übernehmen. Bei Deploy-Problemen Actions-Logs prüfen; nicht behaupten, dass Deployment erfolgreich war, bevor bestätigt.
- FTP-Deploy überschreibt Quellcodedateien, aber löscht keine Serverdateien; `storage/` ist ausgeschlossen. SQLite-Datenbank und Benutzerfortschritte müssen erhalten bleiben.
- Die einmalige Ersteinrichtung über `public/setup.php` wurde vom Betreiber erfolgreich abgeschlossen. Ein Administrator ist vorhanden, weitere Benutzer konnten im Adminbereich angelegt und getestet werden. Keine Neuinstallation oder automatische Kontoanlage vornehmen.

## Aktuelle Architektur
- `public/index.php`: HTML-Oberfläche und Aufgaben-Einbettung.
- `public/style.css`: responsive Industrial Dark Mode.
- `public/app.js`: Login, Aufgaben, Fortschritt, Benutzerverwaltung, XP-Anzeige.
- `public/api.php`: PHP-API, Session/CSRF, serverseitige Aufgabenprüfung, Admin-Benutzeranlage.
- `src/bootstrap.php`: PDO SQLite, Tabellenanlage und Session-Helfer.
- `content/ap1/elektrotechnik.json`: fünf erste Elektrotechnik-Beispielaufgaben.
- `storage/mechatraining.sqlite`: produktive SQLite-Datenbank, nur auf Server, nicht in GitHub.
- `scripts/install.php`: CLI-Installationsskript; für bestehenden Server nicht erneut ausführen.
- `AGENTS.md`: verbindliche Entwicklungsregeln.

## Bereits funktional
- Individuelle Anmeldung per Benutzername und Passwort.
- Erster Benutzer ist Administrator; Benutzeranlage ausschließlich für Admins.
- Normale Benutzer haben keine Adminberechtigung.
- Pro Benutzer gespeicherte richtige Antworten und Versuchszahlen in SQLite.
- Responsive AP1-Elektrotechnikaufgaben mit Antwortprüfung.
- Dezente XP-/Level-Anzeige im Header (aktueller Prototyp: 10 XP je erstmalig richtig gelöster Aufgabe; 100 XP je Level, aus persistentem Aufgabenfortschritt berechnet). Keine auffälligen Effekte.
- Betreiber bestätigte erfolgreichen Live-Login, Benutzeranlage und automatisches GitHub-Actions-Deployment. Neuere Änderungen separat prüfen.

## Produktentscheidungen
- Zielgruppe Erwachsene: sachlich, technisch, professionell, nicht kindlich oder übermäßig spielerisch.
- XP und Level dezent im Kopfbereich, keine dominanten Popups, Soundeffekte oder aufdringlichen Animationen.
- AP1 zuerst; AP2 modular vorbereiten.
- Spielpriorität 1–10 vorgesehen, aber noch nicht als vollständige Verwaltung implementiert.
- Neue Aufgaben als versionierte JSON-Inhalte; stabile IDs; Lernstände nie versehentlich zurücksetzen.
- Mobile Browser (Android/iOS) und Desktop unterstützen.
- Offline/IndexedDB-Synchronisierung ist ein späteres Ziel, derzeit nicht implementiert.

## Arbeitsablauf – besonders wichtig
1. Nutzer beschreibt kurz die gewünschte Änderung; keine unnötigen Rückfragen oder lange Anleitungen.
2. Zuerst `AGENTS.md`, `PROJECT_CONTEXT.md`, `README.md` und betroffene Dateien aus dem aktuellen GitHub-Repository lesen.
3. Änderung eigenständig direkt im verbundenen GitHub-Repository umsetzen.
4. Bei Push auf `main` startet der automatische FTPS-Deploy. Nutzer soll nicht manuell Dateien hochladen oder den Workflow starten müssen.
5. Änderungen sinnvoll prüfen, keine ungetesteten Behauptungen. Kurz mitteilen, was geändert wurde und ob Deployment tatsächlich bestätigt ist.
6. Nur bei notwendiger Benutzeraktion eine einzelne klare Anweisung geben. Nutzer bevorzugt knappe Kommunikation, wenig Erklärung.
7. Bei größeren Änderungen bestehende Benutzerkonten, SQLite, Fortschritte und Live-Betrieb schützen.

## Sicherheitsregeln
- `public/` ist der einzige Webroot. `storage/`, `src/`, `scripts/` und `content/` sind nicht öffentlich.
- Keine Zugangsdaten, Setup-Schlüssel, Passwort-Hashes, persönlichen Daten oder Datenbanken in Git.
- Passwörter nur als sichere Hashes speichern; keine fest codierten Demo-Admin-Passwörter.
- Serverseitige Berechtigungsprüfung für Adminfunktionen, CSRF-Schutz und parametrisierte SQL-Statements.
- Datenbankmigrationen additiv und rückwärtskompatibel; vor destruktiven Änderungen Backup-Konzept.
- Ersteinrichtung nach erfolgreicher Anlage eines Benutzers gesperrt lassen.

## Wissenschaftlicher Taschenrechner\n- Ausklappbare Sidebar mit lokalem wissenschaftlichem Rechner (DEG/RAD, Trigonometrie, Logarithmen, Potenzen, Wurzeln, Fakultät, Speicher, Verlauf). Keine Fremd-CDNs oder Markenabbildungen.\n\n## Notizblock\n- Zweite ausklappbare Sidebar unter dem Taschenrechner; automatische lokale Speicherung pro Browser über localStorage und TXT-Export. Noch keine serverseitige Synchronisierung.\n\n## Noch offen
- Umfangreiches AP1-Dashboard und Fachgebiete, modulare Lernspiele.
- Adminverwaltung für Spiele, Prioritäten, Aufgabensätze und Benutzerpflege.
- Verbesserte XP-Regeln bei wachsender Aufgabenbibliothek.
- Testabdeckung, Login-Rate-Limiting, Backups, Offline-Funktion, AP2.
- Dokumentation bei jeder wesentlichen Architektur- oder Workflow-Änderung aktualisieren.

## Geplanter Workflow „Futter“ (Entscheidung 2026-10-16)
- Keine zusätzliche kostenpflichtige OpenAI-API-Anbindung. Fotos von Schul-/Werkstattarbeitsblättern werden in einem dedizierten Chat hochgeladen und dort durch ChatGPT analysiert.
- ChatGPT schlägt vor, ob vorhandene Themen/Aufgaben erweitert oder neue Themen, Aufgaben und Übungsformen erstellt werden sollten. Nach Zustimmung des Betreibers werden die Inhalte über den normalen GitHub-Workflow umgesetzt.
- „Futter“ wird ein **Admin-only-Verlaufs- und Verwaltungsbereich** in MechaTraining, keine KI-Warteschlange und keine direkte ChatGPT-Rückkommunikation über die Website.
- Dort sollen neu angelegte Inhalte mit Datum, Thema, Aufgabentyp und Herkunft/Arbeitsblatt-Referenz nachvollziehbar sein; Admin kann einzelne Aufgaben prüfen, deaktivieren, reaktivieren und aus der aktiven Ansicht entfernen.
- Standardmäßig **archivieren statt endgültig löschen**: Aufgaben-IDs niemals wiederverwenden, historische Lernfortschritte/XP und Datenbankintegrität bewahren. Bei Bedarf sichere gesonderte endgültige Löschfunktion erst nach bewusster Bestätigung und Prüfung der Auswirkungen.
- Umsetzung der Oberfläche und Datenhaltung ist noch offen; diese Passage dokumentiert zunächst die vereinbarte Produktentscheidung.

## Pneumatik – erster freigegebener Arbeitszettel (2026-10-16)
- Neuer dritter Hauptbereich „Pneumatik“ im Dashboard.
- Datei `content/ap1/pneumatik.json` mit drei Modulen und je 15 Aufgaben: Pneumatische Steuerungen, Zylinderkräfte berechnen, Zylinderarten vergleichen.
- Grundlage: fotografierter Arbeitszettel „Funktionssteuerung der Bereitstellungsanlage / Kraftbestimmung am Pneumatikkolben“, doppeltwirkender Zylinder Ø25 mm, Stange Ø10 mm, Hub 100 mm, 6 bar Betrieb, 2–10 bar zulässig.
- Berechnungen sind theoretische Werte ohne Reibung; für einfachwirkende Zylinder sind konkrete Herstellerdaten nötig.
- Vorhandene Antwort-API validiert neue IDs serverseitig und speichert Fortschritt in der bestehenden Tabelle, ohne Datenmigration.
- Die 45 Aufgaben sind derzeit Auswahlfragen; ein interaktiver Symbolik-Schaltplan-Editor ist noch nicht umgesetzt.
- „Futter“-Admin-Verlauf ist weiterhin geplant, nicht implementiert.


## Pneumatik – zweites freigegebenes Arbeitsblatt (2026-10-16)
- Vorlage: „Ansteuerung eines doppelt wirkenden Zylinders“ (DIN ISO 1219-1, 3/2- und 5/2-Wegeventile, Anschlüsse, pneumatische Vorsteuerung, Rollenstößel, Bauteilkennzeichnungen).
- 60 neue Aufgaben in vier Modulen: Schaltzeichen (15), Wegeventile und Anschlüsse (15), interaktive Schaltpläne (20), Bauteilkennzeichnungen (10).
- Die Schaltplanaufgaben enthalten eine vereinfachte, bedienbare SVG-Simulation für Signal 12/14 mit Aus- und Einfahrbewegung; dies ist kein vollständiger Pneumatiksimulator.
- Fortschritt und Antworten weiterhin serverseitig über vorhandene API, eindeutige IDs; keine Migration und kein Zurücksetzen.
- Futter-Herkunft vorläufig hier dokumentiert; Admin-Futter-Verwaltung weiterhin offen.


## Futter – implementiert (2026-10-16)
- Admin-only-Verwaltung mit Suche, Statusfilter, Lösungen, Quellen und Zeitstempeln. Aufruf im Adminbereich unterhalb der Lernbereiche.
- Jede Bestandsaufgabe erhielt `created_at` (ISO 8601) und `source`; der Zeitstempel ist ein dokumentierter Bestandsimport am 16.10.2026 und kein nachweisbarer individueller Entstehungszeitpunkt.
- Neue Aufgaben müssen ihren tatsächlichen Erstellzeitpunkt mit Zeitzone erhalten.
- Status active / disabled / archived in additiver SQLite-Tabelle futter_status; Ereignisprotokoll futter_events mit Änderungszeitpunkten in UTC.
- Deaktivierte und archivierte Aufgaben werden im Lernmodul ausgeblendet und serverseitig bei Antwortversuchen abgelehnt. Fortschrittsdaten und XP bleiben erhalten. Reaktivierung jederzeit möglich.
- Kein endgültiges Löschen und keine Wiederverwendung von IDs; Quellaufgaben bleiben versioniert in GitHub.


## Formel-Modus (2026-10-16)
- Separater Modus auf dem Dashboard für AP1-Formeltraining, unabhängig von normalen Quiz-Aufgaben und XP.
- Inhalte in content/ap1/formeln.json: 11 Elektrotechnik- und 6 Pneumatikformeln mit Fachgebiet und Varianten.
- Zwei Übungsarten: Formel erkennen (drei Antworten) und Formel selbst eingeben (zeichenbasierter Vergleich, nur begrenzte Schreibweisen-Normalisierung).
- Gemischte oder fachspezifische Runden, zufällige Reihenfolge, direkte Rückmeldung und Rundenergebnis.
- Kein Datenbankeingriff, keine Änderung bestehender Aufgaben-IDs oder Fortschritte. Weitere Formelgebiete können in der JSON-Sammlung ergänzt werden.


## Admin-Navigation und Futter-Blöcke (2026-10-16)
- Adminzugang nur für Administratoren über kleines Zahnrad im Header. Admin-Startansicht mit Futter und Benutzerverwaltung; keine Adminlisten auf dem Lerndashboard.
- Futter zeigt gruppierte Arbeitsblatt-Importe (Pneumatik Arbeitsblatt 1 und 2 sowie getrennten Elektrotechnik-Bestand) statt einzelne Fragen auf oberster Ebene. Fragen/Lösungen bleiben in aufklappbaren Details verfügbar.
- Ganze Blöcke können aktiviert, deaktiviert und archiviert werden. Die aktuelle Umsetzung führt die vorhandene statusgeschützte Einzelaufgaben-API sequenziell aus; bei Netzwerkfehlern können Teiländerungen auftreten. Status und Fortschritte bleiben in SQLite erhalten.
- Für zukünftige Arbeitsblatt-Importe eine eindeutige dauerhafte Block-ID und einen tatsächlichen Zeitstempel vergeben.


## Formel-Modus: Erfolgsleiter (2026-10-16)
- Im Formeltraining klettert ein animierter Fuchs nach jeweils fünf unmittelbar aufeinanderfolgenden richtigen Antworten eine Leiterstufe höher.
- Falsche Antwort setzt nur die aktuelle Fünferserie zurück; bereits erreichte Stufen bleiben für die laufende Runde bestehen. Neue Runde setzt Serie und Leiter zurück.
- Nur clientseitige Motivation ohne XP, persistente Daten oder Änderungen bestehender Aufgaben.


## Dezente Formel-Levelanzeige und Startseitenlogo (2026-10-16)
- Formel-Modus: links am Fragenbereich eine schmale Fortschrittsschiene mit Fuchskopf. Pro richtiger Antwort steigt die Position innerhalb der Fünferserie; bei fünf richtigen Antworten wird das Level erhöht und pro dauerhaft erreichtem Level ein grüner Balken angezeigt. Bei Fehlern fällt nur der Serienfortschritt auf null zurück.
- Das Headerlogo verlinkt auf die Startseite (`./`).


## Kompakte Formel-Modus-Oberfläche (2026-10-16)
- Formel-Level-Fuchs als feste schmale Anzeige links auf Bildschirmmitte, auch beim Scrollen sichtbar.
- Im Formel-Modus eigenständiger dunkler grünlicher Seitenhintergrund; ein Icon oben rechts führt zurück zu den Lernbereichen.
- Überflüssige Titel und Beschreibung entfernt, kompakte Fachgebiet-/Übungsart-Auswahl und runder Neustart-Button unten rechts.

## Wiederaufnahme nach Browser-Neuladen (2026-10-17)
- Die zuletzt geöffnete Lernansicht wird pro Browser-Tab in sessionStorage gespeichert und nach erfolgreicher Sessionprüfung wiederhergestellt (Dashboard, Fachgebiet, Modul, Formelmodus).
- Formelmodus merkt sich Rundenreihenfolge, Fragenposition, Trefferzahl und gewählte Filter; Ctrl+F5 startet daher nicht automatisch auf der Startseite. Neue Runde setzt den Rundenzustand zurück.
- Benutzerwechsel/Logout entfernt die gespeicherte Ansicht. Die serverseitige Anmeldung bleibt unverändert; kein zusätzlicher Tracking-Cookie.


## Metallbau – Schweißtechnik (freigegeben 2026-10-17)
- Quelle: fotografiertes Arbeitsblatt „Schweißnaht, -querschnitt, Elektrodenverbrauch, Kehlnaht / V-Naht“.
- `content/ap1/metallbau.json`: vier neue Module mit jeweils 10 Fragen und stabilen IDs `ap1-metall-schweissen-*`: Formeln, idealisierter Kehlnahtquerschnitt, Schweißgutvolumen, theoretischer Elektrodenverbrauch.
- Die Aufgaben nutzen nachvollziehbare Lösungen und wechselnde Zahlen; Kehlnaht-Schenkellänge z und wirksame Nahtdicke a werden ausdrücklich unterschieden. Idealisiertes V-Naht-Modell ohne Steg/Überhöhung; Elektrodenverbrauch ist theoretisch, nutzbarer Anteil wird bei Bedarf vorgegeben.
- `content/ap1/formeln.json`: neues Fachgebiet Metallbau mit 9 Formeln; auswählbar im Formel-Modus.
- Serverseitige Antwortprüfung und Futter-Adminverwaltung um `metallbau.json` erweitert; Futter-Block-ID `metall-schweissnaht-20261017`.
- Vorhandene Konten, Fortschritts-IDs, XP und SQLite bleiben unverändert.


## Futter: Arbeitsblatt-Blöcke sicher entfernen (2026-10-19)
- Admin-Aktion „Block löschen“ mit Bestätigungsdialog; löscht nicht physisch, sondern archiviert alle enthaltenen Fragen und entfernt den Block dauerhaft aus der Futter-Verwaltungsansicht.
- Additive SQLite-Tabelle `futter_deleted_batches` speichert entfernte Block-IDs; serverseitige Transaktion archiviert alle zugehörigen Aufgaben und protokolliert `deleted_block`-Ereignisse.
- Öffentliche Status-API meldet entfernte Aufgaben als verborgen; Antwort-API lehnt archivierte Aufgaben ab. Quellfragen, stabile IDs, bestehende XP und Lernfortschritte bleiben erhalten.


## Checkbox-Fragenfilter nach Übungsbereichen (2026-10-19)
- `public/index.php` enthält über den Quizfragen eine kompakte Mehrfachauswahl der Übungsbereiche.
- `public/app.js` bildet die verfügbaren Bereiche aus den Modulen des aktuellen Fachgebiets (Elektrotechnik, Pneumatik, Metallbau). Beim Öffnen ist zunächst das gewählte Modul aktiviert; weitere Module können per Checkbox hinzugeschaltet oder abgewählt werden. Mindestens ein Modul bleibt ausgewählt.
- Fragen, Zähler und Fortschrittsanzeige werden unmittelbar anhand der Auswahl aktualisiert; gespeicherte Aufgaben-IDs, Antworten und XP bleiben unverändert. Auswahl gilt für die aktuelle Übungsansicht.
