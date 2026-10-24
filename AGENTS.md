# MechaTraining – Entwicklungsregeln
- Vor Änderungen README.md, AGENTS.md und PROJECT_CONTEXT.md vollständig lesen; PROJECT_CONTEXT.md dokumentiert den tatsächlichen Hosting- und Entwicklungsworkflow.
- PHP 8.1+, SQLite via PDO, Vanilla JS/CSS; keine externen CDNs.
- Webroot ist ausschließlich `public/`; `storage/` und `src/` niemals öffentlich ausliefern.
- Keine Geheimnisse, Benutzerdateien, SQLite-Datenbanken oder Backups committen.
- Datenbankmigrationen additiv; bestehende Benutzer, Aufgaben-IDs und Fortschritte erhalten.
- Aufgaben-IDs sind stabil und eindeutig; neue Fragen erhalten neue IDs.
- Keine öffentlichen Registrierungen. Passwörter nur mit password_hash/password_verify.
- SQL immer mit Prepared Statements; HTML-Ausgabe escapen; CSRF bei Änderungen; sichere Sessions.
- AP1 zuerst, AP2 strukturell vorbereitet. Mobilgeräte und Tastaturbedienung berücksichtigen.
- Jede neue Spielart registriert sich über eine eindeutige ID; Priorität 1–10.
- Keine behaupteten Testergebnisse ohne tatsächliche Ausführung.

## Zusammenarbeit und Deployment
- Nutzer möchte Änderungen ausschließlich per Prompt beauftragen; Code direkt im verbundenen GitHub-Repository `Joe-Petts/mechatraining` ändern.
- Push auf `main` startet GitHub Actions FTPS-Deployment auf ALL-INKL. Kein manuelles Hochladen oder ReRun vom Nutzer verlangen, außer bei tatsächlichem Fehler.
- Antworten knapp halten: erledigte Änderung und ggf. genau ein nächster Schritt. Keine langen Schritt-für-Schritt-Anleitungen ohne Bedarf.
- Kein neues Setup oder Zurücksetzen bestehender Konten: Administrator und weitere Benutzer sind bereits angelegt.
- XP/Level nur dezent im Header; erwachsene Zielgruppe und seriöses Industrial-Design beachten.
- Dokumentation in PROJECT_CONTEXT.md bei Architektur- oder Workflow-Änderungen aktuell halten.

## CSS-Regeln
- Zentrales Stylesheet der Lernplattform: `public/main.css`. Neue Styles hier einpflegen, nicht als Inline-Styles in PHP/HTML/JavaScript.
- Komponenten- und bereichsspezifische Selektoren verwenden (z. B. `#appPanel .quiz-single-card`, `#formulaPanel ...`, `#futterPanel ...`), damit Änderungen keine fremden Module beeinflussen.
- Eigenständige Verwaltungsseiten dürfen separate, eindeutig gekapselte Stylesheets nutzen (z. B. `public/backup.css`).
- Bei CSS-Änderungen den Cache-Buster in der einbindenden PHP-Datei aktualisieren. Bestehende CSS-Regeln nicht ohne Sichtprüfung großflächig umstrukturieren.
