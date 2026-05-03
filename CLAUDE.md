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
- Neue API-Endpoints → `docs/ARCHITEKTUR.md` (Endpoint-Tabelle)
- DB-Schema-Änderungen → `_db/README.md` + `docs/ARCHITEKTUR.md` (Datenmodell-Sektion)
- Neue `lib/`-Helper → `docs/ARCHITEKTUR.md` (lib-Inhaltsverzeichnis)
- Widget-/Admin-UI-Patterns → später `docs/DESIGN-SYSTEM.md` (Phase 3)
- Cron-Jobs → später `docs/CRON-JOBS.md` (wenn mehr als ein Cron läuft)

### SSOT-Prinzip Code (kein Rad neu erfinden)

Wenn Funktionalität bereits in `lib/` existiert, **wird sie genutzt**. Niemals zwei Helper die das gleiche tun. Konkret:

- **DB-Zugriff** ausschließlich über `lib/db.php` → `getDb()`. Niemals `new PDO()` direkt im Endpoint-Code.
- **Admin-API-Responses** ausschließlich über `lib/helpers.php` → `apiSuccess($data)` / `apiError($msg, $status)`. Niemals manuell `echo json_encode(...)` mit eigenem Envelope.
- **Public-API-Responses** über `lib/helpers.php` → `jsonResponse($plainArray)` (ohne Envelope, pures Array für Widget).
- **Login-Prüfung** ausschließlich über `lib/auth.php` → `requireLogin()`. Niemals lokale Session-Checks.
- **Public-API-Härtung** ausschließlich über `lib/public_api_guard.php` → `enforcePublicApiHardening($shopId)`. Niemals einzelne Layer (CORS, Referer, Rate-Limit) lokal in einem Endpoint nachbauen.

**Vor neuem Helper:** `lib/`-Inhalt durchgrep-en oder Inhaltsverzeichnis in `docs/ARCHITEKTUR.md` checken — gibt's das schon? Bei Unsicherheit: nachfragen statt parallel bauen.

Wenn ein Helper über zwei Endpoints geteilt werden soll: in `lib/` extrahieren, niemals lokal duplizieren.

### SSOT-Prinzip UI (Admin-Backend)

**Alle Styles leben in `src/admin/assets/`** — nirgendwo sonst:

- **Tokens** (Farben, Spacing, Typo, Radius, Shadows): `tokens.css` → ausschließlich dort
- **Komponenten** (Buttons, Forms, Cards, Chips, Tables, Callouts, Toasts, Status-Block): `components.css`
- **Layout** (App-Header, App-Main, Login-Layout, Page-Header, Grid): `layout.css`
- **Reset + Typo + Utilities**: `base.css`
- **Hub** (Single Entry-Point): `admin.css` → wird von Pages eingebunden

**In Admin-PHP-Pages verboten:**
- ❌ `<style>...</style>`-Blöcke inline
- ❌ `style="..."` Attribute auf Elementen
- ❌ Hardcoded Farb-/Spacing-Werte (immer über `var(--token)`)
- ❌ Page-spezifisches CSS-File (z.B. `dashboard.css`) — alles in `components.css` zentral

**Vor neuer Komponente:** in `docs/DESIGN-SYSTEM.md` und `components.css` prüfen ob's das schon gibt. Wenn nicht: dort ergänzen, dann nutzen — niemals lokal duplizieren.

Volle Konvention: `docs/DESIGN-SYSTEM.md`. Pattern-Quelle: `production-app/docs/DESIGN-SYSTEM.md` (kondensiertes Subset für Sporeprint).

**Widget (Public-Subdomain) ist eigenständig** — kein Admin-CSS-Import, eigene Namespaces (`.sporeprint-*`). Widget-CSS lebt inline in `src/public/widget.js` (self-contained für JTL-Shop-Embedding).

### SSOT-Prinzip DB (keine redundanten Spalten)

**Außer ID-/PK-Spalten existiert jeder Wert nur einmal.** Bei Mehrfach-Vorkommen → FK-Verweis statt Duplikat.

- ✅ `reviews.shop_id` → FK auf `shops.shop_id`. Beim Lesen JOIN auf `shops` für Name/Domain
- ❌ `reviews.shop_name` redundant zu `shops.name` — niemals
- ❌ `review_replies.review_stars` neben `reviews.stars` — niemals
- ✅ Polymorphe Querschnitts-Tabellen mit `entity_type` + `entity_id` wenn das gleiche Konzept für mehrere Entitäten gilt (in v1 nicht benötigt, aber Pattern-Vorlage falls später Notes/Attachments)

**Vor neuer Tabelle/Spalte prüfen:** Steht der Wert oder ein abgeleiteter Wert schon woanders? Wenn ja → Verweis statt Duplikat. Drift entsteht durch Redundanz.

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

### Format-Standards (SSOT)

**Datum / Zeit / Format überall über zentrale Helper:**

- **PHP:** `lib/helpers.php` → `formatDate($iso)`, `formatDate($iso, true)` (mit Uhrzeit), `humanTimeDiff($iso)` (relative Zeit). Niemals `date('d.m.Y', ...)` direkt im Page-Code.
- **JS:** `src/admin/assets/format.js` → `AppFormat.date(iso)`, `AppFormat.dateTime(iso)`, `AppFormat.relative(iso)`. Niemals `toLocaleDateString()` / `toLocaleString()` / `new Intl.DateTimeFormat()` direkt.
- **Display:** TT.MM.JJJJ mit führender Null. Bei null/leer/ungültig: Em-Dash `–`. Locale: deutsche Konventionen (Komma als Dezimaltrennzeichen).

Volle Doku + Erkennungs-Greps: `docs/DESIGN-SYSTEM.md` Sektion 2b.

### Sprache + Schreibweise

**Umlaut-Pflicht (hart erzwungen via Pre-Commit-Hook `_tools/check_umlauts.py`):**

In allen `.md`-Dateien, PHP-Strings (Echo, HTML, Errors), JS-Strings (UI-Text), Code-Kommentaren und Commit-Messages müssen deutsche Umlaute korrekt geschrieben werden — niemals ASCII-Substitution wie `Aenderung`, `fuer`, `moeglich`, `ueber`, `Stueck`. **Einzige Ausnahme:** Code-Variablennamen, Funktionsnamen, Datei-/Ordnernamen, DB-Spalten (dort ASCII).

**Genderneutrale Sprache:** Im Zweifel `:innen-Form` (`Kund:innen`, `Bewerter:innen`). Niemals nur männliche Form außer bei konkret bekannter Person.

**Anrede:**
- Admin-UI (intern): **Du**-Form
- Widget (im Shop, externe Endkund:innen): **Sie**-Form oder neutral
- Code-Doku, Commits: neutral oder Du

**Tone:** sachlich, freundlich, kompakt. Keine Buzzwords, keine Marketing-Sprache, keine Emojis im Admin-UI. Konkret statt abstrakt. Aktiv statt passiv. Fehlermeldungen lösungsorientiert.

Volle Konvention mit Substitutions-Tabelle: `docs/DESIGN-SYSTEM.md` Sektion 5b.

## SSOT-Nachschlagewerk

**Aktive Docs in `docs/` (Stand 2026-05-03):** 2 Dateien (ARCHITEKTUR.md + DESIGN-SYSTEM.md). Wenn diese Zahl nicht stimmt, greift die Docs-Vollständigkeits-Regel oben.

| Frage | Lies |
|-------|------|
| Globaler Kontext | `C:\AI-Workspace\CLAUDE.md` |
| Dev-Projekt-Standard (Plan-Workflow, 3-Stufen-Methodik) | `C:\AI-Workspace\references\dev-projekt-standard.md` |
| Roadmap + Phasen-Status | `_plans/ROADMAP.md` |
| Aktiver Detailplan (Phase 1 Backend-Foundation) | `_plans/2026-05-03-phase-1-backend-foundation.md` |
| Konzept-Diskussion (Stufen 1+2 abgeschlossen, Architektur-Pivot Vercel→Server Profis) | `_plans/2026-05-02-architektur-pivot-konzept.md` |
| **System-Architektur**, Komponenten, Multi-Tenant, Tech Stack, Datenmodell, Sicherheits-Layer, Folder-Struktur, Cron-Strategie | `docs/ARCHITEKTUR.md` |
| **UI-Standards Admin** (Buttons, Forms, Cards, Chips, Tables, Callouts, Toasts, Layout, Tokens, BEM-Konvention) | `docs/DESIGN-SYSTEM.md` |
| Widget-Prototyp (Sample-Daten, Carousel-HTML — wird in Phase 2 mit echten Daten verbunden) | `references/widget_prototype.html` |
| CI-Material (Farben, Logos, Original-Layout vom alten onlinereviews.tech-System) | `references/` |
| Pattern-Quelle (production-app als Vorlage, siehe Pre-Check im Konzept) | `C:\AI-Workspace\projects\dev\production-app\` |
| **Archivierte Historie** (nur bei Bedarf): | |
| Original-Bauplan unter Vercel-Stack (vor Pivot 2026-05-02) | `_archive/docs/PLAN.md` |

## Aktueller Fokus

→ **2026-05-03:** **Phase 1 Backend-Foundation Code-seitig komplett.** Aktiver Plan: [`_plans/2026-05-03-phase-1-backend-foundation.md`](_plans/2026-05-03-phase-1-backend-foundation.md). Foundation steht: lib-Helper (db, helpers, auth, rate_limit, public_api_guard), Admin-Login mit CSRF, Public-API mit 6-Layer-Härtung, Dashboard-Stub, Widget-Skelett mit Mock-Data. Workspace-Patterns unter [`references/php-patterns/`](../../../references/php-patterns/) angelegt. Browser-Verifikation steht noch aus (User testet Login + API-Endpoint nach WinSCP-Sync). **Wartet** auf Freigabe Google Reviews-API + Trustpilot Public-API — danach folgt API-Client + Cron-Skripte als kleiner Folge-Plan.
