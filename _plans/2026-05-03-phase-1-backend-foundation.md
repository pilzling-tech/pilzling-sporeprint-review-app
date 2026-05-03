# Phase 1 — Backend-Foundation (API-frei vorgezogen)

**Erstellt:** 2026-05-03
**Konzept-Vorlauf:** [`2026-05-02-architektur-pivot-konzept.md`](2026-05-02-architektur-pivot-konzept.md) (Stufen 1+2 ✅)
**Vorgaenger-Plan:** [`2026-05-03-phase-0-foundation.md`](2026-05-03-phase-0-foundation.md) (Foundation steht, zwei API-Antraege wartend)
**Stufe:** 3 (Detailplan) — Implementierung
**Roadmap-Bezug:** [ROADMAP.md](ROADMAP.md) → Phase 1 Backend

## Ziel

Sporeprint-Backend-Foundation steht — alles unterhalb der API-Clients. Konkret: PHP-Library (`lib/`), Single-Admin-Login mit Session, Public-API-Haertung mit allen 6 Layern, leerer Public-API-Endpoint der die volle Pipeline durchlaeuft (auch wenn DB noch leer ist), Admin-Dashboard-Stub. Plus: Pattern-Heben der wiederverwendbaren Bausteine nach `C:\AI-Workspace\references\php-patterns\` als Workspace-SSOT.

**Nach Phase 1 ist das Backend zu ~70% fertig.** Sobald **eine** der externen APIs (Google Reviews oder Trustpilot) freigeschaltet ist, fehlen nur noch Cron-Skripte + zwei API-Client-Files (`lib/api_clients/google.php`, `lib/api_clients/trustpilot.php`) — eigener Folge-Plan.

## Ausgangslage

**Phase 0 abgeschlossen:** Repo, Schema v1, Subdomains, Verzeichnisschutz, WinSCP-Auto-Deploy, Stub-Index-Seiten — alles live. Server reagiert sauber auf beide Subdomains.

**Wartend (nicht-blockierend):** Google Reviews-API-Antrag und Trustpilot Public-API-Antrag laufen bei den Anbietern. Bearbeitungszeit unbestimmt. Wir bauen jetzt vor, damit beim Eintreffen der Freischaltung nur noch der API-spezifische Block fehlt.

## Verweis auf Konzept + Pre-Check

- **Pattern-Inventur Pre-Check Sektion A:** zehn Patterns aus production-app, davon werden in Phase 1 die Foundation-Patterns konkret umgesetzt (A1 db.php, A3+A4 helpers.php, A6 vereinfachte auth.php, A8 ist schon angewandt fuer Schema)
- **Public-API-Hardening (Pre-Check D7):** wird in Phase 1 als Erstwerk gebaut und nach `references/php-patterns/public-api-hardening/` gehoben
- **Drift-Mitigation B1:** alle Identifier aus production-app-Pattern werden beim Kopieren auf Englisch umbenannt
- **Drift-Mitigation B2:** strikte Public-vs-Admin-Endpoint-Trennung — siehe Konventionen unten

## Konventionen / Harte Regeln (gelten fuer alle Code-Aenderungen in Phase 1+)

### SSOT-Prinzip Code (kein Rad neu erfinden)

Wenn Funktionalitaet bereits in `lib/` existiert, **wird sie genutzt**. Niemals zwei Helper die das gleiche tun. Konkret:

- DB-Zugriff **ausschliesslich** ueber `lib/db.php` → `getDb()`. Niemals `new PDO()` direkt im Endpoint-Code.
- API-Responses **ausschliesslich** ueber `lib/helpers.php` → `apiSuccess($data)` / `apiError($msg, $status)`. Niemals manuell `echo json_encode(...)` mit eigenem Envelope.
- Login-Pruefung **ausschliesslich** ueber `lib/auth.php` → `requireLogin()`. Niemals lokale Session-Checks.
- Public-API-Haertung **ausschliesslich** ueber `lib/public_api_guard.php` → `enforcePublicApiHardening($shopId)`. Niemals einzelne Layer (CORS, Referer, Rate-Limit) lokal in einem Endpoint nachbauen.
- Wenn ein neuer Helper benoetigt wird der ueber zwei Endpoints geteilt wird → in `lib/` extrahieren, niemals lokal duplizieren.
- Bei Unsicherheit ob ein Helper schon existiert: **`lib/`-Inhalt durchgrep-en** bevor neuer geschrieben wird.

### SSOT-Prinzip DB (keine redundanten Spalten)

**Ausser ID-/PK-Spalten existiert jeder Wert nur einmal.** Bei Mehrfach-Vorkommen → FK-Verweis statt Duplikat.

- ❌ `reviews.shop_name` redundant zu `shops.name` — falsch. Stattdessen: `reviews.shop_id` (FK), beim Lesen JOIN auf `shops`
- ❌ `review_replies.review_stars` redundant zu `reviews.stars` — falsch. Stattdessen: JOIN bei Bedarf
- ❌ `sync_runs.shop_name` neben `sync_runs.shop_id` — falsch
- ✅ Polymorphe Querschnitts-Tabellen mit `entity_type` + `entity_id` wenn das gleiche Konzept fuer mehrere Entitaeten gilt (in v1 nicht benoetigt, aber Pattern-Vorlage)

**Bei jeder neuen Tabelle/Spalte vor dem Schema-Vorschlag pruefen:** Steht der Wert oder ein abgeleiteter Wert schon woanders? Wenn ja → Verweis statt Duplikat.

### Public vs. Admin Endpoint-Trennung (Drift-Mitigation B2)

Jeder PHP-Endpoint ruft als allererste inhaltliche Zeile **genau eine** Schutz-Funktion:

| Endpoint-Typ | Erste Zeile |
|--------------|-------------|
| Admin (`src/admin/**/*.php`) | `requireLogin();` |
| Public (`src/public/api/**/*.php`) | `enforcePublicApiHardening($shopId);` |

Niemals beide. Niemals keine. Bei Unklarheit → Plan-Eintrag oder Rueckfrage.

### Naming-Konvention (Drift-Mitigation B1)

Alle Identifier durchgaengig **English** — Tabellen, Spalten, Funktionen, Variablen, Konstanten, Cookies, ENV-Variablen. Beim Pattern-Kopieren aus production-app **aktiv umbenennen** (`erstellt_von` → `created_by`, `aktiv` → `is_active`, `rolle` → `role`, `passwort_hash` → `password_hash`).

### API-Response-Format (Konzept-Festlegung)

| Endpoint-Typ | Format |
|--------------|--------|
| Public API (`public/api/*.php`) | **pures JSON-Array** der Reviews |
| Admin API (`admin/api/*.php`) | **`{ok: true, data: ...}`** / **`{ok: false, error: "..."}`** Envelope |

`apiSuccess()` / `apiError()` in `lib/helpers.php` implementiert das Envelope. Public-Endpoints nutzen `jsonResponse($plainArray)` direkt.

## Scope — was in diesem Plan ist

Drin (alle 7 Phasen):
- `lib/db.php` (PDO-Singleton-Wrapper, nutzt vorhandenes `config/database.php`)
- `lib/helpers.php` (apiSuccess/apiError/jsonResponse)
- `lib/auth.php` (Single-Admin-Login mit `.env`-Hash, Session, requireLogin, isApiRequest)
- `admin/index.php` als echte Login-Seite (Form + CSRF + Hash-Vergleich) — ersetzt aktuellen Stub
- `admin/logout.php`
- `admin/dashboard.php` als Layout-Stub mit Logout-Button und "Daten kommen sobald APIs angeschlossen sind"
- `lib/rate_limit.php` (Sliding-Window-Counter via `rate_limits`-Tabelle)
- `lib/public_api_guard.php` (`enforcePublicApiHardening()` — alle 6 Layer)
- `public/api/reviews.php` als voller Endpoint (durchlaeuft Haertung, liefert vorerst leeres Array — DB hat noch keine Reviews)
- `_tools/cron-cleanup-rate-limits.php` (Sliding-Window-Buckets aelter als 1h purgen)
- Pattern-Heben nach `C:\AI-Workspace\references\php-patterns\` (Workspace-SSOT-Erstanlage)
- Widget-Skelett `public/widget.js` mit Mock-Data (basierend auf bestehendem Prototyp in `references/widget_prototype.html`) — embedbar in JTL-Templates, zeigt Carousel mit Beispiel-Reviews
- Endgueltige Verifikation gegen Drift (Code ↔ Doku)

## NICHT in diesem Plan (bewusste Auslassungen, Vollstaendigkeits-Prinzip)

| Was | Grund |
|-----|-------|
| **API-Clients (`lib/api_clients/google.php`, `trustpilot.php`)** | Beide APIs warten auf Antrags-Freigabe. Eigener Folge-Plan sobald **eine** der APIs grueen ist |
| **Cron-Skripte zum Reviews-Fetchen** (`cron-fetch-google.php`, `cron-fetch-trustpilot.php`) | Brauchen API-Clients — gleicher Folge-Plan |
| **JTL-Reviews via direkt-SQL aus JTL-MSSQL-DB** | Eigener Plan in Phase 1.5 oder 2.5 (siehe ROADMAP) |
| **Admin-Dashboard mit echten Funktionen** (Filter, Reply, Analytics, Widget-Konfigurator) | Phase 3 — kommt nach Backend-Vervollstaendigung |
| **Brevo-Integration (Stufen 1-3)** | Phase 4 |
| **Sporen-Rating-Visual** im Widget | Phase 2 Polish-Step, optional |
| **DESIGN-SYSTEM.md** + zentrale UI-Standards | Phase 3 — wenn das Admin-Dashboard ausgebaut wird. Login-Seite und Dashboard-Stub bleiben minimal gestylt (Vanilla CSS, keine Framework-Abhaengigkeit) |
| **CSRF-Token-System** ueber Admin-Login hinaus | wird gemeinsam mit den ersten Admin-POST-Endpoints in Phase 3 ausgebaut |
| **production-app `.htaccess`-Fix** (cp:ppd-Block) | macht User in separater Session |
| **Cloudflare-Setup** | Konzept-Entscheidung: kein Cloudflare |
| **Tests / PHPUnit** | bewusst ausgelassen v1 — Manual-Tests via Browser/curl, formale Tests wenn Codebase wachstumstechnisch dafuer reif ist |

## Phase 0 — Docs-Review + Infrastruktur-Audit

**Teil A — Docs-Review:**

1. **`CLAUDE.md` "Harte Regeln"-Sektion erweitern** um die SSOT-Prinzipien (Code-SSOT + DB-SSOT) wie oben unter "Konventionen" formuliert. **Wichtig:** das gehoert in CLAUDE.md damit Claude sie in *jeder* Session sieht, nicht nur wenn dieser Plan offen ist.
2. **`docs/ARCHITEKTUR.md` ergaenzen:** API-Endpoint-Pattern-Sektion (welche Endpoints es gibt, wo welcher Schutz greift, Response-Format-Tabelle). Auch: kurzer "lib/-Verzeichnis-Inhaltsverzeichnis"-Block damit klar ist welcher Helper wofuer da ist.
3. **`MEMORY.md` Eintrag** unter "Bestaetigte Entscheidungen": "SSOT-Prinzip ist in CLAUDE.md verankert. Bei jeder Code-Aenderung wird vor neuen Helpern gegen `lib/` und `docs/ARCHITEKTUR.md` gegrepped."

**Teil B — Infrastruktur-Audit:**

Sporeprint ist Greenfield ohne Code, daher kein Audit gegen bestehende Implementierung. Aber: **Pre-Check-Pattern-Inventur (Sektion A im Konzept)** wird Phase fuer Phase referenziert — bei jedem `lib/`-File explizit verwiesen welcher production-app-Pattern als Vorlage diente, damit Drift-Mitigation B1 (Naming-Umbenennung) systematisch greift.

**Akzeptanzkriterium 0:** CLAUDE.md hat den SSOT-Block, ARCHITEKTUR.md hat das API-Pattern-Inhaltsverzeichnis, MEMORY.md hat den Eintrag.
**Commit-Ende 0:** `docs: SSOT-Prinzipien in CLAUDE.md + API-Pattern in ARCHITEKTUR.md`

## Phase 1 — lib-Foundation

**Ziel:** `lib/db.php` + `lib/helpers.php` als Boden fuer alles weitere.

**Files:**

1. `src/lib/db.php` — duenner Wrapper um `config/database.php`. Reicht eigentlich `require_once`-Kette aus, aber `lib/db.php` als public-API macht den Helper-Pfad konsistent ("alles aus `lib/`"). Datei kann `getDb()` und einen `dbQuery($sql, $params)`-Convenience-Wrapper anbieten.
2. `src/lib/helpers.php` — `jsonResponse()`, `apiSuccess()`, `apiError()` aus production-app-Pattern A3+A4. Plus eine `binaryIp(string $ipString): string`-Helper (IP zu VARBINARY(16) fuer rate_limits).

**Akzeptanzkriterium 1:** Beide Files committed, syntaktisch valid (`php -l` lokal grueen). Keine Endpoints aufgerufen — Foundation-Files sind nur Helper.
**Commit-Ende 1:** `feat(lib): db.php + helpers.php Foundation-Helper`

## Phase 2 — Auth (Single-Admin)

**Ziel:** Admin-Login-Form lebt, Session funktioniert, Logout funktioniert, `requireLogin()` schuetzt jeden Admin-Endpoint.

**Files:**

1. `src/lib/auth.php` — `session_start()` global beim Include. Funktionen:
   - `attemptLogin(string $username, string $password): bool` — vergleicht gegen `ADMIN_USER` + `ADMIN_PASSWORD_HASH` aus `.env`, `password_verify`-basiert
   - `logout(): void`
   - `requireLogin(): void` — bei nicht eingeloggt: 401 wenn API-Request, sonst Redirect auf `/index.php`
   - `isApiRequest(): bool` — `str_starts_with($_SERVER['REQUEST_URI'], '/api/')`
   - `currentUser(): ?array` — gibt `{username: ...}` zurueck oder null

2. `src/admin/index.php` — ECHTE Login-Seite (ersetzt aktuellen Stub):
   - Wenn schon eingeloggt → Redirect auf `dashboard.php`
   - Form: Username + Password, POST auf sich selbst
   - CSRF-Token (Session-basiert) im Form
   - Bei POST: Token-Check, dann `attemptLogin()`, bei Erfolg → Redirect Dashboard
   - Minimaler CSS-Block inline (nicht extern, weil noch kein DESIGN-SYSTEM)
   - Bei Login-Fehler: generic Error "Login nicht moeglich" (kein "User existiert nicht" oder "Passwort falsch" — Info-Disclosure-Schutz)

3. `src/admin/logout.php` — `logout()` aufrufen, Redirect auf `/index.php`

**Akzeptanzkriterium 2:** Login mit korrekten Credentials aus `.env` funktioniert, Session bleibt erhalten, Logout zerstoert Session, falsches Passwort gibt generic Error, Direktzugriff auf `dashboard.php` ohne Login redirected zurueck zu `/index.php`.
**Commit-Ende 2:** `feat(admin): Single-Admin-Login mit Session + CSRF`

## Phase 3 — Public-API-Haertung + Endpoint

**Ziel:** Alle 6 Haertungs-Layer aus Konzept Thema 4 sind als Helper aufgebaut und werden vom ersten Public-Endpoint genutzt. Endpoint liefert leeres Array (DB ist leer) — aber haerte Pipeline ist verifiziert.

**Files:**

1. `src/lib/rate_limit.php` — Sliding-Window-Counter:
   - `checkRateLimit(string $ipBinary, int $limitPerMinute = 60, int $windowMinutes = 1): bool` — true wenn ok, false wenn Limit ueberschritten
   - Nutzt `rate_limits`-Tabelle: `bucket_minute = floor(time()/60)`, INSERT mit `ON DUPLICATE KEY UPDATE request_count = request_count + 1`
   - Sum-Query ueber letzten N Minuten

2. `src/lib/public_api_guard.php` — `enforcePublicApiHardening(string $shopId): array`:
   - Layer 1: CORS-Header dynamisch nach Shop-Domain aus `shops`-Tabelle
   - Layer 2: Referer-Check gegen Shop-Whitelist (`Referer:` muss zu `shops.domain` passen) — bei Mismatch: 403
   - Layer 3: Rate-Limit per IP — bei Ueberschreitung: 429 + `Retry-After`-Header
   - Layer 4: `Cache-Control: public, max-age=21600`-Header setzen
   - Layer 5+6: nicht in dieser Funktion (SRI ist client-side, Datenminimierung ist Endpoint-Verantwortung)
   - Rueckgabe: `{shop_id, shop_row}` als Array mit Shop-Konfiguration fuer den Endpoint
   - Bei Fail: schickt schon Response + `exit;`

3. `src/public/api/reviews.php` — voller Endpoint:
   - Erste Zeile: `enforcePublicApiHardening($_GET['shop'] ?? '')`
   - Dann: SELECT auf `reviews` JOIN `widget_configs` mit Filtern (visibility='visible', stars >= min_stars, LIMIT max_items)
   - Datenminimierung: nur die Whitelist-Felder aus ARCHITEKTUR.md zurueckgeben
   - Output: `jsonResponse($reviewsArray)` (kein Envelope — pures Array)

4. `src/_tools/cron-cleanup-rate-limits.php` — CLI-Skript: `DELETE FROM rate_limits WHERE bucket_minute < FLOOR(UNIX_TIMESTAMP()/60) - 60`

**Akzeptanzkriterium 3:** Browser-Aufruf von `https://sporeprint.pilzling.eu/api/reviews?shop=pilzling` liefert `[]` (leeres JSON-Array) mit `Cache-Control: public, max-age=21600`-Header. Aufruf mit `shop=unbekannt` liefert 403 (kein gueltiger Shop). Aufruf ohne Referer-Header liefert 403 (oder akzeptiert Postman-Test je nach Konfig — Browser ohne Referer scheitert sauber). Aufruf vom JTL-Shop-Template (Referer matched) funktioniert.
**Commit-Ende 3:** `feat(public-api): reviews-Endpoint mit voller Haertungs-Pipeline`

## Phase 4 — Admin-Dashboard-Layout-Stub

**Ziel:** Eingeloggte Admins landen auf einer Dashboard-Seite mit Layout-Skelett. Funktionen sind Phase-3-Thema, aber das Layout-Geruest steht damit man's spaeter befuellen kann.

**Files:**

1. `src/admin/dashboard.php` — Layout-Stub:
   - `requireLogin()` als erste Zeile
   - Header mit "Sporeprint Admin", Logout-Link, eingeloggter Username
   - Main-Bereich: Platzhalter-Karten "Reviews", "Analytics", "Widget-Konfigurator", "QR-Generator" — alle mit "Kommt in Phase 3"-Hinweis
   - Footer: Branding "Sporeprint" + Link zur Erklaerung

2. `src/admin/.assets/admin.css` (klein, minimal) — Basis-Layout mit CSS-Custom-Properties als Grundlage fuer spaetere Phase-3-Erweiterung. Definiert nur: Farben, Schrift, Spacing-Scale, Container-Width.

**Akzeptanzkriterium 4:** Eingeloggter Admin sieht Dashboard mit Header + Karten-Stubs, Logout-Button funktioniert.
**Commit-Ende 4:** `feat(admin): Dashboard-Layout-Stub mit CSS-Foundation`

## Phase 5 — Pattern-Heben nach Workspace-SSOT

**Ziel:** Wiederverwendbare Bausteine als `references/php-patterns/`-SSOT fuer Workspace anlegen. Sporeprint-Erstwerk (Public-API-Hardening) ist die Initial-Anlage.

**Files (in Workspace, nicht im Sporeprint-Repo):**

1. `C:\AI-Workspace\references\php-patterns\README.md` — Uebersicht + Anwendungsregel: "**Patterns sind Vorlagen, kein Code-Sharing.** Beide Apps kopieren von hier, leben dann unabhaengig. Bei Pattern-Verbesserung: hier aktualisieren, dann bewusst in jeder App nachziehen."

2. `references/php-patterns/htaccess-hardening.template` — `.env`-Block, Indexes off, UTF-8 (extrahiert aus production-app)

3. `references/php-patterns/env-loader-and-pdo-singleton.php` — Vorlage mit `<DB_NAME>`-Platzhalter

4. `references/php-patterns/api-envelope-convention.md` — `apiSuccess`/`apiError` Code + Begruendung

5. `references/php-patterns/auth-boilerplate.php` — Vereinfachtes Auth-Skelett (Single-Admin, ohne Permission-System)

6. `references/php-patterns/cron-token-validation.php` — `hash_equals`-Pattern

7. `references/php-patterns/db-migrations-convention.md` — Naming + Pflicht-Checkliste

8. `references/php-patterns/public-api-hardening/` — **das Sporeprint-Erstwerk:**
   - `cors-whitelist.php`
   - `referer-check.php`
   - `rate-limit-sliding-window.php`
   - `README.md` mit Anwendungs-Anleitung

9. `references/php-patterns/security-hygiene-checklist.md` — Markdown-Doku der Hygiene-Punkte aus Konzept-Thema 4

10. `references/php-patterns/umlaut-precommit/` — `check_umlauts.py` + Patterns + Allowlist

**Akzeptanzkriterium 5:** Ordner `references/php-patterns/` existiert mit allen 10 Eintraegen, README erklaert die Anwendungsregel. Sporeprint-Code in `lib/` referenziert die Patterns mit Hinweis-Kommentar (`// Pattern: references/php-patterns/...`).
**Commit-Ende 5:** **Kein Sporeprint-Repo-Commit.** Workspace-Aenderung — User entscheidet ob das in einem Workspace-uebergreifenden Commit landen soll oder pragmatisch ohne Versionierung lebt.

## Phase 6 — Widget-Skelett mit Mock-Data

**Ziel:** Embedbares `widget.js` als Vanilla-JS-File, basierend auf bestehendem Prototyp `references/widget_prototype.html`. Zeigt Carousel mit Mock-Reviews. Spaeter (Phase 1.5/2 wenn API-Daten fliessen) wird der Mock durch `fetch('/api/reviews?shop=...')`-Call ersetzt.

**Files:**

1. `src/public/widget.js` — Vanilla-JS, kein Framework. Struktur:
   - IIFE damit kein globaler Scope-Pollution
   - Liest `data-shop="..."` vom eigenen `<script>`-Tag
   - Aktuell: liefert hardcoded Mock-Reviews-Array (3-5 Beispiele)
   - Spaeter: ersetzt Mock durch fetch
   - Rendert Carousel-DOM ins Element mit `id="sporeprint-widget"` ODER haengt sich selbst an `<script>`-Tag-Position
   - CSS via `<style>`-Tag injected (kein externes CSS — das Widget muss self-contained sein, da im Shop-Template embedded)
   - Sporeprint-Footer: kleiner Text "powered by Sporeprint" mit Tooltip
2. `src/public/widget-test.html` — minimale HTML-Seite die `<script src="widget.js" data-shop="pilzling">` nutzt — fuers Live-Testen ohne JTL-Shop

**Akzeptanzkriterium 6:** `https://sporeprint.pilzling.eu/widget-test.html` zeigt das Widget mit Mock-Data im Browser, responsive, mit Sporeprint-Footer.
**Commit-Ende 6:** `feat(widget): widget.js mit Mock-Data + Test-Seite`

## Phase 7 — Verifikation (Drift-Check)

**Checks:**

1. **Code ↔ ARCHITEKTUR.md:** Jede neue API-Datei in `lib/`, `public/api/`, `admin/` ist in ARCHITEKTUR.md erwaehnt? lib-Verzeichnis-Inhaltsverzeichnis aktuell?
2. **CLAUDE.md SSOT-Tabelle:** Alle neuen Doku-Files (falls welche) eingetragen?
3. **`.gitignore`:** keine sensitive Datei (`.env*`) versehentlich committed?
4. **Konsistenz Naming:** alle neuen Identifier in Phase 1 sind English (kein deutsches Relikt aus Pattern-Kopie)?
5. **SSOT Code-Check:** kein Endpoint nutzt PDO direkt statt `lib/db.php`? Kein Endpoint baut eigenen Envelope statt `apiSuccess`/`apiError`? Kein Endpoint dupliziert Hardening-Layer-Logik?
6. **Server-State:** ist die letzte Version per WinSCP synct? `https://sporeprint.pilzling.eu/api/reviews?shop=pilzling` liefert `[]` (nicht 500/403)?
7. **Admin-Login Browser-Test:** Login → Dashboard → Logout → Login-Form wieder. Passwort falsch → generic Error. Direktzugriff Dashboard ohne Login → Redirect.
8. **`pattern-heben` durch:** `references/php-patterns/`-Ordner existiert mit Inhalten wie in Phase 5 spezifiziert?

**Akzeptanzkriterium 7:** Alle 8 Checks gruen oder explizit als "blockiert weil X" markiert. ROADMAP.md "Aktueller Fokus" zeigt Phase 1 als abgeschlossen, naechster Schritt ist API-Block (wartet) oder Phase 2 Widget-Polish.
**Commit-Ende 7:** `chore: Phase 1 Verifikation — Backend-Foundation steht`

## Akzeptanzkriterien (Plan gilt als abgeschlossen wenn…)

- [ ] CLAUDE.md hat SSOT-Prinzip-Sektion (Code + DB)
- [ ] ARCHITEKTUR.md hat API-Pattern-Inhaltsverzeichnis + lib-Inhaltsverzeichnis
- [ ] `lib/db.php`, `lib/helpers.php` existieren und werden von Endpoints genutzt
- [ ] Admin-Login funktioniert (Login + Logout + Session + CSRF + falsches Passwort generic Error)
- [ ] Public-API-Endpoint `/api/reviews?shop=pilzling` lebt mit allen 6 Haertungs-Layern
- [ ] Cron-Cleanup-Skript fuer rate_limits ist deployed (manueller Cron-Eintrag in cPanel folgt nach Phase 7)
- [ ] Admin-Dashboard-Stub mit Layout existiert, eingeloggte User landen drauf
- [ ] Workspace-Patterns unter `references/php-patterns/` angelegt
- [ ] Widget-Skelett `widget.js` zeigt Mock-Reviews via Test-HTML
- [ ] Verifikations-Phase abgeschlossen, alle 8 Checks gruen
- [ ] ROADMAP.md "Aktueller Fokus" aktualisiert auf "Phase 1 ✅, wartet auf API-Anbindung"
- [ ] Plan in `_archive/_plans/` verschoben sobald oben gruen

## Referenzen

- **Vorgaenger-Plan:** [`2026-05-03-phase-0-foundation.md`](2026-05-03-phase-0-foundation.md)
- **Konzept:** [`2026-05-02-architektur-pivot-konzept.md`](2026-05-02-architektur-pivot-konzept.md)
- **Architektur:** [`../docs/ARCHITEKTUR.md`](../docs/ARCHITEKTUR.md)
- **Pattern-Quelle production-app:** `C:\AI-Workspace\projects\dev\production-app\src\` (siehe Pre-Check Sektion A im Konzept fuer konkrete Datei-Verweise)
- **Dev-Projekt-Standard:** [`C:\AI-Workspace\references\dev-projekt-standard.md`](../../../../references/dev-projekt-standard.md) (v2.1)
