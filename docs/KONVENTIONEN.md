# Konventionen — Code, UI, DB, Sprache

**Level: verbindlich.** Aus der Projekt-CLAUDE.md hierher gezogen (2026-08-02, Standard-Migration):
Kern §4 will die Regel in der CLAUDE.md, die Herleitung mit Beispielen und Verbotslisten in der
Wissensbasis. Inhaltlich unverändert — nur der Ort hat gewechselt.

Die Kurzfassung (jede Regel eine Zeile) steht in [`../CLAUDE.md`](../CLAUDE.md) unter „Harte Regeln".

---

## Harte Regeln — Volltext

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

Keine API-Keys (Google, Trusted Shops) und keine DB-Credentials im Code. Lokal in `.env` im Repo-Stamm (NICHT committed, in `.gitignore`), auf dem Server in `app.reviews/config/.env` (auch nicht im Repo). Verzeichnisschutz auf Admin-Subdomain + `.htaccess`-Block für `.env*`-Dateien als Defense-in-Depth.

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

Eindeutiger Test für Grenzfälle: **Wenn der Text theoretisch von einem Menschen gelesen werden könnte → Umlaute. Wenn der Text als String vom System interpretiert wird (Code-Identifier, URL, Datei-Name) → ASCII.**

| Kategorie | Schreibweise | Beispiele |
|-----------|--------------|-----------|
| **ASCII (technische Identifier)** | nur a-z, 0-9, `_`, `-` | PHP-Variablen (`$user_name`), Funktionen (`getReviewsForShop`), DB-Tabellen/-Spalten (`reviews.created_at`), Datei-/Ordnernamen (`api_clients/`), CSS-Klassen (`.btn-primary`), HTTP-Header (`X-Cron-Token`), ENV-Variablen (`DB_USER`), JSON-Keys (`{"product_name": ...}`), URL-Parameter (`?shop=pilzling`) |
| **Umlaute (alles andere)** | `äöüÄÖÜß` korrekt | HTML-Output (`echo "Bewertung gepostet"`), UI-Labels (`<button>Speichern</button>`), Form-Placeholder (`placeholder="Suche…"`), Error-Messages (`apiError("Bestellung nicht gefunden")`), HTML-Title/Meta, Toast-Texte, **Code-Kommentare** (`// Diese Funktion prüft …`), Doku (`.md`-Files), Commit-Messages, Plan-Files |

**Niemals** ASCII-Substitutionen wie `Aenderung`, `fuer`, `moeglich`, `ueber`, `Stueck`, `Pruefung`, `gehoert`, `naechst`, `koennen`, `muessen`, `fuehren`, `oeffnen`, `Schluessel`, `Stueck`, `vollstaend`. Substitutions-Tabelle vollständig in `docs/DESIGN-SYSTEM.md` Sektion 5b.

**Genderneutrale Sprache:** Im Zweifel `:innen-Form` (`Kund:innen`, `Bewerter:innen`). Niemals nur männliche Form außer bei konkret bekannter Person.

**Anrede:**
- Admin-UI (intern): **Du**-Form
- Widget (im Shop, externe Endkund:innen): **Sie**-Form oder neutral
- Code-Doku, Commits: neutral oder Du

**Tone:** sachlich, freundlich, kompakt. Keine Buzzwords, keine Marketing-Sprache, keine Emojis im Admin-UI. Konkret statt abstrakt. Aktiv statt passiv. Fehlermeldungen lösungsorientiert.

Volle Konvention mit Substitutions-Tabelle: `docs/DESIGN-SYSTEM.md` Sektion 5b.

