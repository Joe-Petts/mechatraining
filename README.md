# MechaTraining

Browserbasiertes Lernspiel für die Mechatroniker-Prüfung. Version 0.1: AP1 Elektrotechnik, Login, PHP-API, SQLite-Fortschritt, responsives Industrial-Dark-Design.

## Anforderungen
PHP 8.1+ mit PDO SQLite, HTTPS und Schreibrechte für `storage/`.

## Lokal starten
1. `php scripts/install.php admin "EIGENES-LANGES-PASSWORT"` (mindestens 12 Zeichen).
2. `php -S 127.0.0.1:8080 -t public`
3. http://127.0.0.1:8080 öffnen.

Hinweis: Passwörter als CLI-Argument können in der Shell-History erscheinen. Für Produktivbetrieb einen interaktiven Installer ergänzen.

## ALL-INKL
**Document Root muss `public/` sein.** `src/`, `content/`, `scripts/` und `storage/` müssen außerhalb des öffentlichen Webroots liegen. Vor Deployment PDO SQLite, Dateirechte, HTTPS und sicheren Erstinstallationsweg prüfen. Falls ALL-INKL keinen passenden Document Root oder CLI-Zugang ermöglicht, zuerst das Deployment-Konzept anpassen. Datenbank und Backups niemals öffentlich ausliefern.

## Struktur
- `public/`: Web-App und API
- `src/`: PHP-Datenbank/Session
- `scripts/`: CLI-Installation
- `content/ap1/`: versionierte Aufgaben
- `storage/`: SQLite (per .gitignore ausgeschlossen)
- `AGENTS.md`: Entwicklungsregeln

## Bereits implementiert
Login ohne öffentliche Registrierung; password_hash, serverseitige Session, CSRF; SQLite-Fortschritt für Benutzer; fünf AP1-Beispielfragen mit serverseitiger Antwortprüfung.

## Noch offen
Adminoberfläche, Benutzerverwaltung, Spiele-Priorität 1–10, sicherer Web-Installer für Shared Hosting, Rate-Limiting, Backups, Offline-IndexedDB, AP2, Tests und produktives Deployment.

**Frühe Grundversion, noch nicht für öffentliche Nutzung freigegeben.**
