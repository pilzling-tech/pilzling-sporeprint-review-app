# Sporeprint — Review-Aggregations-Tool

**Branding extern: "Sporeprint".** Inspiriert vom Sporenabdruck — der eindeutigen Pilz-Signatur, die ein Pilz auf Papier hinterlässt. Reviews als unverfälschter Abdruck der Kund:innen-Erfahrung.
**Intern technisch: "reviews".** DB heißt `pilzling_reviews_app`, Tabellen sind `reviews`, `review_replies` etc. — Konsistenz im Code, kein Brand-Naming-Aufwand in technischen Pfaden.

## Quick Context

Eigenes Review-Management-System für drei JTL-Shops (Pilzling, Pilzwald, Shroom Boom). Aggregiert Google-, Trustpilot- und JTL-Produktbewertungen in ein Widget + Admin-Dashboard. Ersetzt onlinereviews.tech (80 €/Monat → 0 €/Monat). Genutzt intern (Admin-Dashboard für CV) und im Frontend der drei Shops (Widget mit Sporeprint-Branding).

## Status

Gestartet: März 2026 | Stack: **in Klärung** (Architektur-Pivot Vercel → Server Profis + MariaDB + PHP läuft, siehe Konzept)
Aktuelle Stufe: **Konzept (Stufe 1)** — `_plans/2026-05-02-architektur-pivot-konzept.md`

## Infrastruktur

- **Hosting + API + Cron:** Vercel (Free Tier)
- **Datenbank:** Vercel KV (Redis)
- **Admin-Dashboard:** noch offen — `pilzling-reviews.vercel.app` oder `admin.pilzling.shop`
- **Public Subdomain (Widget + Public-API):** `sporeprint.pilzling.eu`
- **Admin Subdomain:** `admin-sporeprint.pilzling.eu` (cPanel-Verzeichnisschutz davor)
- **Widget-Einbindung:** `<script src="https://sporeprint.pilzling.eu/widget.js" data-shop="..." integrity="...">` in jedem JTL-Template
- **Externe APIs:** Google Business Profile API (OAuth), Trustpilot Business Units API, JTL REST API
- **E-Mail-Automation:** Brevo (bestehender Account, keine eigene Mail-Schicht)
- **Versionierung:** GitHub (privates Repo — noch nicht angelegt)
- **Credentials:** Bitwarden → Ordner "Webserver & Domain" + Vercel Environment Variables je Shop

## Ordner-Struktur & Zweck

| Ordner | Zweck |
|--------|-------|
| `src/` | Deploybarer Code (Widget, Admin, API-Routes) — aktuell nur `widget_prototype.html` |
| `tests/` | Tests (optional je Schicht) |
| `docs/` | Projekt-Dokumentation (wie das System aufgebaut ist — Architektur, Patterns) |
| `_plans/` | Aktive Planung: ROADMAP + Feature-Pläne + Konzept-Dokumente |
| `_archive/` | Erledigte/veraltete Pläne und historische Docs |
| `_tools/` | Hilfsscripts (lokal, nicht deployed) |
| `references/` | Externes/Allgemeingültiges: CI-Material, Original-Struktur des alten Anbieters |

**Regel:** `docs/` enthält **keine** Feature-Listen, Checkboxen oder Plan-Fragmente. Alles Planerische gehört in `_plans/`. Docs beschreiben **wie Dinge gebaut sind**, nicht **was noch zu tun ist**.

## Plan-Workflow

Dieses Projekt folgt dem **Dev-Projekt-Standard v2.0** in `C:\AI-Workspace\references\dev-projekt-standard.md` Abschnitt 4. Kurzfassung:

**Drei Plan-Typen:**
1. `_plans/ROADMAP.md` — roter Faden über alle Phasen
2. `_plans/YYYY-MM-DD-<feature>.md` — Detailplan pro Feature
3. `_plans/YYYY-MM-DD-<feature>-konzept.md` — Konzept-Dokument für komplexe Features (Brainstorming-Stufe)

**3-Stufen-Methodik für komplexe Features** (Pflicht ab "neue Datenstrukturen ODER Cross-Cutting"):
- **Stufe 1 — Konzept:** Was/Warum, Datenmodell, offenes Brainstormen, Iterations-Log
- **Stufe 2 — Pre-Check:** SSOT/Redundanz/Drift-Audit gegen Code-Stand, Output ins Konzept
- **Stufe 3 — Detailplan:** erst nach stabilem Konzept + Pre-Check

**Pflicht-Struktur jedes Detailplans:**
- **Phase 0** — zweiteilig: A) Docs-Review + B) Infrastruktur-Audit (welche bestehenden Helper/Endpoints/Komponenten werden berührt — keine Parallel-Implementierungen)
- **Phase 1, 2, …** — Implementierung
- **Finale Phase** — Docs-Verifikation (Code ↔ Docs)

**Vollständigkeits-Prinzip:** Beim Planen nicht nach Aufwand filtern — alles aus Spec/Briefing rein, bewusste Auslassungen explizit als "NICHT in diesem Plan"-Liste mit Grund.

**Konkrete Sporeprint-Anwendung:** Das **Datenmodell für Vercel KV** ist ein Pflichtfall für die 3-Stufen-Methodik (neue Datenstruktur, Multi-Tenant-Mechanik) — bevor Phase 1 (Backend) startet, wird `_plans/YYYY-MM-DD-datenmodell-konzept.md` erstellt.

## Session-Start-Protokoll

1. `MEMORY.md` lesen — offene Punkte, Korrekturen, Stolperstellen
2. `_plans/ROADMAP.md` lesen — aktueller Stand, nächste Phase
3. Aktive Feature-Pläne UND Konzept-Dokumente in `_plans/` lesen (alles außer ROADMAP.md). Konzepte sind erkennbar am Suffix `-konzept.md`
4. `docs/` gegen SSOT-Nachschlagewerk unten abgleichen (Self-Healing-Check)
5. Status in 3-5 Zeilen zusammenfassen — auch in welcher Stufe (Konzept / Pre-Check / Detailplan / Implementierung)
6. Frage: "Weiter mit aktivem Feature/Phase oder neues Thema?"

## Harte Regeln

### Docs-First-Prinzip (Anti-Drift)

Docs sind die Planungs- und Coding-SSOT. Wenn Code und Docs auseinanderdriften, entstehen Fehler. Für Feature-Arbeit erzwingt die Plan-Struktur das automatisch (Phase 0 Docs-Review + finale Verifikations-Phase). Für kleine Änderungen ohne Plan: Docs zuerst lesen → Docs aktualisieren → Code → Gegen-Check.

**Besonders relevante Doku pro Änderungs-Typ:**
- Neue API-Endpoints → `docs/ARCHITEKTUR.md`
- Vercel-KV-Schema-Änderungen → `docs/ARCHITEKTUR.md` (Sektion Datenmodell)
- Widget-/Admin-UI-Patterns → später `docs/DESIGN-SYSTEM.md` (wenn relevant)
- Cron-Jobs → später `docs/CRON-JOBS.md` (wenn mehr als ein Cron läuft)

### Docs-Vollständigkeit (Self-Healing)

Alle Dateien in `docs/` müssen im SSOT-Nachschlagewerk unten stehen. Beim Session-Start kurz `docs/` auflisten und gegen die Tabelle abgleichen. Unbekannte Datei? → einsortieren (aktiv → Tabelle ergänzen, veraltet → `_archive/docs/`, Plan-Fragment → `_plans/`). Niemals ignorieren — unbekannte Docs sind ein Drift-Signal.

### Multi-Tenant-Sauberkeit

Drei Shops (Pilzling, Pilzwald, Shroom Boom) — Daten und Credentials immer per `shop-id` getrennt. Kein Hardcoding eines Shops in Helper-Funktionen, kein Cross-Shop-Datenleak. API-Keys pro Shop in eigenen ENV-Variablen (`<SERVICE>_KEY_<SHOP>`).

### Widget-Einbettbarkeit

Das Widget muss in JTL-Shop-Templates einbettbar sein — als einzeiliger `<script>`-Tag mit `data-shop`-Attribut. Keine Build-Schritte beim Shop-Betreiber, keine Konflikte mit JTL-CSS/JS.

### Credentials

Keine API-Keys (Google, Trustpilot) und keine DB-Credentials im Code. Lokal in `.env` im Repo-Stamm (NICHT committed, in `.gitignore`), auf dem Server in `app.reviews/config/.env` (auch nicht im Repo). Verzeichnisschutz auf Admin-Subdomain + `.htaccess`-Block für `.env*`-Dateien als Defense-in-Depth.

### Pattern-Übernahme aus production-app

Beim Kopieren von Code aus `C:\AI-Workspace\projects\dev\production-app\` immer **deutsche Identifier umbenennen**: `erstellt_von` → `created_by`, `erstellt_am` → `created_at`, `aktiv` → `is_active`, `rolle` → `role`, `passwort_hash` → `password_hash`, `notiz` → `note`, etc. Sporeprint nutzt durchgängig **englisches Naming** in Tabellen, Spalten, Funktionsnamen, Variablen. **Niemals 1:1 paste — aktiv re-namen.** Begründung steht im Pre-Check-Abschnitt B1 des Konzepts.

### Public vs. Admin Endpoint-Trennung

Jeder PHP-Endpoint ruft als allererste Zeile **genau eine** der beiden Schutz-Funktionen:

- **Admin-Endpoints:** `requireLogin();` (aus `lib/auth.php`)
- **Public-Endpoints:** `enforcePublicApiHardening($shopId);` (aus `lib/public_api_guard.php` — implementiert die 6 Härtungs-Layer aus ARCHITEKTUR.md)

Niemals beide oder keine. Wenn ein Endpoint unklar zwischen beiden steht, ist das ein Architektur-Bug — nicht raten, sondern explizit klären welche Schicht greifen soll.

### Umlaute-Pflicht

In allen `.md`-Dateien, Code-Kommentaren, UI-Strings und Commit-Messages müssen deutsche Umlaute korrekt geschrieben werden — nicht als ASCII-Substitution. Einzige Ausnahme: Code-Variablennamen, Funktionsnamen, Datei-/Ordnernamen.

## SSOT-Nachschlagewerk

**Aktive Docs in `docs/` (Stand 2026-05-03):** 1 Datei (ARCHITEKTUR.md). Wenn diese Zahl nicht stimmt, greift die Docs-Vollständigkeits-Regel oben.

| Frage | Lies |
|-------|------|
| Globaler Kontext | `C:\AI-Workspace\CLAUDE.md` |
| Dev-Projekt-Standard (Plan-Workflow, 3-Stufen-Methodik) | `C:\AI-Workspace\references\dev-projekt-standard.md` |
| Roadmap + Phasen-Status | `_plans/ROADMAP.md` |
| Aktiver Detailplan (Phase 0 Foundation) | `_plans/2026-05-03-phase-0-foundation.md` |
| Konzept-Diskussion (Stufen 1+2 abgeschlossen, Architektur-Pivot Vercel→Server Profis) | `_plans/2026-05-02-architektur-pivot-konzept.md` |
| **System-Architektur**, Komponenten, Multi-Tenant, Tech Stack, Datenmodell, Sicherheits-Layer, Folder-Struktur, Cron-Strategie | `docs/ARCHITEKTUR.md` |
| Widget-Prototyp (Sample-Daten, Carousel-HTML — wird in Phase 2 mit echten Daten verbunden) | `src/widget_prototype.html` |
| CI-Material (Farben, Logos, Original-Layout vom alten onlinereviews.tech-System) | `references/` |
| Pattern-Quelle (production-app als Vorlage, siehe Pre-Check im Konzept) | `C:\AI-Workspace\projects\dev\production-app\` |
| **Archivierte Historie** (nur bei Bedarf): | |
| Original-Bauplan unter Vercel-Stack (vor Pivot 2026-05-02) | `_archive/docs/PLAN.md` |

## Aktueller Fokus

→ **2026-05-03:** **Detailplan Phase 0 angelegt** — [`_plans/2026-05-03-phase-0-foundation.md`](_plans/2026-05-03-phase-0-foundation.md). 5 Phasen: 0.A/B Docs-Update, 1 Repo+Struktur, 2 Schema v1, 3 externe APIs (Google + Trustpilot, **kein JTL-REST**), 4 WinSCP, 5 Verifikation. cPanel-Setup ist bereits ✅ live. **JTL REST API zurückgestellt** — kostenpflichtige Beta, später via direkt-SQL aus JTL-MSSQL-DB. ARCHITEKTUR.md wird in Phase 0.A auf den Server-Profis-Stack-Soll-Zustand umgeschrieben (zeigt aktuell noch den ursprünglichen Vercel-Stand).
