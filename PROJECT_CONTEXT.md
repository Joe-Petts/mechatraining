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

## Noch offen
- Umfangreiches AP1-Dashboard und Fachgebiete, modulare Lernspiele.
- Adminverwaltung für Spiele, Prioritäten, Aufgabensätze und Benutzerpflege.
- Verbesserte XP-Regeln bei wachsender Aufgabenbibliothek.
- Testabdeckung, Login-Rate-Limiting, Backups, Offline-Funktion, AP2.
- Dokumentation bei jeder wesentlichen Architektur- oder Workflow-Änderung aktualisieren.
