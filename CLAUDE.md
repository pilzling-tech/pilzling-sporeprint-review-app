# Sporeprint — Review-Aggregations-Tool

**Branding extern: "Sporeprint".** Inspiriert vom Sporenabdruck — der eindeutigen Pilz-Signatur, die ein Pilz auf Papier hinterlässt. Reviews als unverfälschter Abdruck der Kund:innen-Erfahrung.
**Intern technisch: "reviews".** DB heißt `pilzling_reviews_app`, Tabellen sind `reviews`, `review_replies` etc. — Konsistenz im Code, kein Brand-Naming-Aufwand in technischen Pfaden.

## Quick Context

Eigenes Review-Management-System für drei JTL-Shops (Pilzling, Pilzwald, Shroom Boom). Aggregiert Google-, Trusted-Shops- und JTL-Produktbewertungen in ein Widget + Admin-Dashboard. Ersetzt onlinereviews.tech (80 €/Monat → 0 €/Monat). Genutzt intern (Admin-Dashboard für CV) und im Frontend der drei Shops (Widget mit Sporeprint-Branding).

## Status

Gestartet: März 2026 | Stack: **in Klärung** (Architektur-Pivot Vercel → Server Profis + MariaDB + PHP läuft, siehe Konzept)
Aktuelle Stufe: **Konzept (Stufe 1)** — `_plans/2026-05-02-architektur-pivot-konzept.md`
standard_version: 3.2

## Infrastruktur

- **Hosting + API + Cron:** Server Profis (cPanel, PHP) — seit dem Pivot vom 2026-05-02; die ursprünglich geplante Vercel-Variante ist historisch
- **Datenbank:** MariaDB `pilzling_reviews_app` auf Server Profis — ersetzt das ursprünglich geplante Vercel KV
- **Admin-Dashboard:** `admin-sporeprint.pilzling.eu` (siehe Admin-Subdomain unten)
- **Public Subdomain (Widget + Public-API):** `sporeprint.pilzling.eu`
- **Admin Subdomain:** `admin-sporeprint.pilzling.eu` (cPanel-Verzeichnisschutz davor)
- **Widget-Einbindung:** `<script src="https://sporeprint.pilzling.eu/widget.js" data-shop="..." integrity="...">` in jedem JTL-Template
- **Externe APIs:** Google Business Profile API (OAuth), Trusted Shops Reviews API, JTL REST API
- **E-Mail-Automation:** Brevo (bestehender Account, keine eigene Mail-Schicht)
- **Versionierung:** GitHub (privates Repo — noch nicht angelegt)
- **Credentials:** Bitwarden → Ordner "Webserver & Domain" + `.env` je Umgebung (lokal gitignored, auf dem Server `app.reviews/config/.env`)

## Ordner-Struktur & Zweck

| Ordner | Zweck |
|--------|-------|
| `src/` | Deploybarer Code (Widget, Admin, API-Routes) — aktuell nur `widget_prototype.html` |
| `tests/` | Tests (optional je Schicht) |
| `docs/` | Projekt-Dokumentation (wie das System aufgebaut ist — Architektur, Patterns) |
| `_plans/` | Aktive Planung: ROADMAP + Feature-Pläne + Konzept-Dokumente |
| `_archive/` | Erledigte/veraltete Pläne und historische Docs |
| `_tools/` | Hilfsscripts (lokal, nicht deployed) |
| `_db/` | DB-Schema und Migrationen (MariaDB) |
| `references/` | Externes/Allgemeingültiges: CI-Material, Original-Struktur des alten Anbieters |

**Regel:** `docs/` enthält **keine** Feature-Listen, Checkboxen oder Plan-Fragmente. Alles Planerische gehört in `_plans/`. Docs beschreiben **wie Dinge gebaut sind**, nicht **was noch zu tun ist**.

## Plan-Workflow

Nach Standard-Kern §6 + Dev-Profil §3 — beide laden über `.claude/rules/` in jeder Session und
werden hier nicht wiederholt.

**Projekt-spezifisch:** Das Datenmodell für MariaDB ist ein Pflichtfall für die
3-Stufen-Methodik (neue Datenstruktur, Multi-Tenant-Mechanik) — vor Phase 1 (Backend) entsteht
`_plans/YYYY-MM-DD-datenmodell-konzept.md`.

## Session-Start-Protokoll

1. `MEMORY.md` lesen — offene Punkte, Korrekturen, Stolperstellen
2. `_plans/ROADMAP.md` lesen — aktueller Stand, nächste Phase
3. Aktive Feature-Pläne UND Konzept-Dokumente in `_plans/` lesen (alles außer ROADMAP.md). Konzepte sind erkennbar am Suffix `-konzept.md`
4. `docs/` gegen SSOT-Nachschlagewerk unten abgleichen (Self-Healing-Check)
5. Status in 3-5 Zeilen zusammenfassen — auch in welcher Stufe (Konzept / Pre-Check / Detailplan / Implementierung)
6. Frage: "Weiter mit aktivem Feature/Phase oder neues Thema?"

## Harte Regeln

Kurzfassung. Herleitung, Beispiele und Verbotslisten: [`docs/KONVENTIONEN.md`](docs/KONVENTIONEN.md).

- **Docs-First:** Docs sind Planungs- und Coding-SSOT. Neue Endpoints → `docs/ARCHITEKTUR.md`, DB-Schema → zusätzlich `_db/README.md`, UI-Patterns → `docs/DESIGN-SYSTEM.md`.
- **SSOT Code:** Kein zweiter Helper für dieselbe Sache. DB nur über `getDb()`, Admin-Antworten über `apiSuccess`/`apiError`, Public über `jsonResponse`, Login über `requireLogin()`, Public-Härtung über `enforcePublicApiHardening()`. Vor jedem neuen Helper `lib/` prüfen.
- **SSOT UI:** Alle Styles in `src/admin/assets/` (Tokens · Komponenten · Layout · Base · Hub). In Admin-Pages verboten: `<style>`-Blöcke, `style=`-Attribute, hardcodierte Farb-/Spacing-Werte, page-eigene CSS-Files. Das Widget ist eigenständig mit `.sporeprint-*`-Namespace.
- **SSOT DB:** Außer Schlüsselspalten existiert jeder Wert nur einmal — bei Mehrfach-Vorkommen FK statt Duplikat. Vor jeder neuen Spalte prüfen, ob der Wert schon woanders steht.
- **Docs-Vollständigkeit:** Jede Datei in `docs/` steht im SSOT-Nachschlagewerk. Unbekannte Datei einsortieren, nie ignorieren.
- **Multi-Tenant:** Daten und Credentials immer per `shop-id` getrennt, kein Shop hardcodiert, kein Cross-Shop-Leak. API-Keys je Shop als `<SERVICE>_KEY_<SHOP>`.
- **Widget-Einbettbarkeit:** einzeiliger `<script>`-Tag mit `data-shop`, keine Build-Schritte beim Shop-Betreiber, keine Konflikte mit JTL-CSS/JS.
- **Credentials:** keine API-Keys und keine DB-Zugänge im Code. Lokal `.env` (gitignored), auf dem Server `app.reviews/config/.env`, plus `.htaccess`-Block für `.env*`.
- **Pattern-Übernahme aus `production-app`:** niemals 1:1 kopieren — deutsche Identifier aktiv umbenennen (`erstellt_von` → `created_by` usw.). Sporeprint nutzt durchgängig englisches Naming.
- **Endpoint-Trennung:** jeder Endpoint ruft als erste Zeile **genau eine** Schutzfunktion — `requireLogin()` oder `enforcePublicApiHardening($shopId)`. Nie beide, nie keine; Unklarheit ist ein Architektur-Bug.
- **Format-Standards:** Datum und Zeit ausschließlich über `formatDate`/`humanTimeDiff` (PHP) bzw. `AppFormat.*` (JS). Nie `date()`, nie `toLocaleDateString()` direkt. Anzeige TT.MM.JJJJ, leer = Em-Dash.
- **Umlaut-Pflicht** (Pre-Commit-Hook `_tools/check_umlauts.py`): technische Identifier ASCII, alles Menschenlesbare mit Umlauten — inklusive Code-Kommentaren und Commit-Messages. Nie `fuer`, `moeglich`, `ueber`.
- **Genderneutral** im Zweifel `:innen`-Form. Anrede: Admin-UI Du, Widget Sie oder neutral.
- **Tone:** sachlich, freundlich, kompakt. Keine Buzzwords, keine Emojis im Admin-UI, Fehlermeldungen lösungsorientiert.

## SSOT-Nachschlagewerk

**Aktive Docs in `docs/` (Stand 2026-05-03):** 2 Dateien (ARCHITEKTUR.md + DESIGN-SYSTEM.md). Wenn diese Zahl nicht stimmt, greift die Docs-Vollständigkeits-Regel oben.

| Frage | Lies |
|-------|------|
| Globaler Kontext | `C:\AI-Workspace\CLAUDE.md` |
| Dev-Projekt-Standard (Plan-Workflow, 3-Stufen-Methodik) | `C:\AI-Workspace\references\rules-dev\dev-projekt-standard.md` |
| Roadmap + Phasen-Status | `_plans/ROADMAP.md` |
| Aktiver Detailplan (Phase 1 Backend-Foundation) | `_plans/2026-05-03-phase-1-backend-foundation.md` |
| Konzept-Diskussion (Stufen 1+2 abgeschlossen, Architektur-Pivot Vercel→Server Profis) | `_plans/2026-05-02-architektur-pivot-konzept.md` |
| **System-Architektur**, Komponenten, Multi-Tenant, Tech Stack, Datenmodell, Sicherheits-Layer, Folder-Struktur, Cron-Strategie | `docs/ARCHITEKTUR.md` |
| **UI-Standards Admin** (Buttons, Forms, Cards, Chips, Tables, Callouts, Toasts, Layout, Tokens, BEM-Konvention) | `docs/DESIGN-SYSTEM.md` |
| Widget-Prototyp (Sample-Daten, Carousel-HTML — wird in Phase 2 mit echten Daten verbunden) | `references/widget_prototype.html` |
| Marken-Farben und abgeleitete UI-Varianten (verbindlich) | `C:\AI-Workspace\references\corporate-identity\README.md` |
| Projekt-eigenes CI-Material (`references/CI/`) und Original-Layout des alten onlinereviews.tech-Systems (`references/Orginal Structure/`) — existiert so nur hier | `references/` |
| Pattern-Quelle (production-app als Vorlage, siehe Pre-Check im Konzept) | `C:\AI-Workspace\projects\dev\production-app\` |
| **Archivierte Historie** (nur bei Bedarf): | |
| Original-Bauplan unter Vercel-Stack (vor Pivot 2026-05-02) | `_archive/docs/PLAN.md` |

## Aktueller Fokus

→ **2026-05-04 (Nacht):** **Pilzling-Go-Live-Aufbau komplett.** Master-Plan: [`_plans/2026-05-04-pilzling-go-live.md`](_plans/2026-05-04-pilzling-go-live.md). Phasen A-J durchgebaut. Schema v2 wartet auf Einspielung via phpMyAdmin. Widget komplett refactored (Aggregat, Avatar, Pfeil-Nav, CTA, Sporen-Rating). Admin: reviews/replies/analytics/widget-config/qr/shops/settings + public/feedback.php. Sobald Schema v2 eingespielt + WinSCP-Sync durch ist, ist der Pilzling-Shop **funktional komplett bereit für Production-Test** mit Mock-Daten. Echte API-Anbindung Phase K wartet auf Google + Trustpilot Freigaben.

→ **2026-05-03:** **Phase 1 + Design-System + CI-Refactoring komplett.** Aktive Pläne: [`_plans/2026-05-03-phase-1-backend-foundation.md`](_plans/2026-05-03-phase-1-backend-foundation.md) + [`_plans/2026-05-03-ci-refactoring.md`](_plans/2026-05-03-ci-refactoring.md). Foundation steht: lib-Helper, Admin-Login mit CSRF, Public-API 6-Layer-Härtung, Dashboard-Stub, Widget-Skelett, Workspace-Patterns. Plus Design-System v2 mit Pilzling-CI (Rubik-Font, Tokens, alle Komponenten), Format-Helper SSOT (PHP+JS), Sprach-Standards, Reference-Implementation. CI-Refactoring (Phase 1.9) hat ASCII-Drift entfernt, Card-Stub-Badge dezenter gemacht, Widget-Test in Admin verschoben. Verifikations-Greps: 0 Treffer. **Wartet** auf Google + Trustpilot API-Freigaben — danach API-Client + Cron-Skripte als kleiner Folge-Plan.

## Externe Referenzen

Files außerhalb dieses Projekt-Repos, die substanziell genutzt werden.

- `C:\AI-Workspace\projects\dev\production-app\` — Pattern-Quelle für Backend und Design-System; Übernahme nur mit Re-Naming (siehe Harte Regeln)
- `C:\AI-Workspace\references\php-patterns\` — geteilte PHP-Bausteine
- `C:\AI-Workspace\references\rules-dev\dev-projekt-standard.md` — Dev-Profil des Projekt-Standards
- `C:\AI-Workspace\CLAUDE.md` — Workspace-Kontext (Marken, Systeme)
- `https://sporeprint.pilzling.eu` — Live-Deployment (Widget + Public-API), `admin-sporeprint.pilzling.eu` das Admin-Backend

_Pflege: Claude fragt bei substanzieller Nutzung externer Files, ob sie hier ergänzt werden sollen._
