# Phase 0 — Foundation

**Erstellt:** 2026-05-03
**Konzept-Vorlauf:** [`2026-05-02-architektur-pivot-konzept.md`](2026-05-02-architektur-pivot-konzept.md) (Stufen 1 + 2 ✅ abgeschlossen)
**Stufe:** 3 (Detailplan) — Implementierung
**Roadmap-Bezug:** [ROADMAP.md](ROADMAP.md) → Phase 0

## Ziel

Sporeprint-Foundation steht: ARCHITEKTUR.md auf den neuen Soll-Zustand (Server Profis statt Vercel), Repo `pilzling-sporeprint-review-app` initialisiert, Folder-Struktur in `app.reviews/` auf dem Server angelegt, Schema v1 eingespielt, externe API-Voraussetzungen (Google + Trustpilot) geklärt, WinSCP-Auto-Deploy konfiguriert. Nach Phase 0 ist alles bereit für Phase 1 (Backend-Implementation).

## Ausgangslage

**Konzept abgeschlossen:** Architektur-Pivot von Vercel auf Server Profis durchgespielt, alle 8 Themen entschieden, Pre-Check gegen production-app durchgeführt (10 Patterns übernehmbar, 9 Patterns für späteres Heben in `references/php-patterns/` identifiziert), Schema-Korrekturen erarbeitet (Sliding-Window-Rate-Limit, `review_replies` aufgesplittet, Public-API ohne Envelope, Visibility-Default, Admin-Filter-Indexe).

**cPanel-Setup ✅ erledigt am 2026-05-02:** Subdomains `sporeprint.pilzling.eu` (DocRoot `app.reviews/public`) + `admin-sporeprint.pilzling.eu` (DocRoot `app.reviews/admin`) angelegt, Verzeichnisschutz auf Admin-Subdomain aktiv, DB `pilzling_reviews_app` angelegt, Bitwarden-Einträge gepflegt.

**Was fehlt:** Code-Foundation (Repo + Folder-Struktur + Config-Boilerplate + Schema-v1) und externe API-Zugänge (Google Business Profile OAuth, Trustpilot API-Key).

## Verweis auf Konzept + Pre-Check

Phase 0 referenziert die **Pattern-Inventur** aus dem Konzept (Sektion "Pre-Check (Stufe 2) → A) Pattern-Inventur") als Vorlage für die Code-Boilerplate. Die **Schema-Korrekturen** aus Sektion C werden in `schema_v1.sql` direkt eingearbeitet.

**Drift-Mitigation aus Pre-Check B1+B2:**
- Beim Pattern-Kopieren aus production-app **systematisch deutsche → englische Identifier umbenennen** (B1)
- `lib/auth.php` für Admin (`requireLogin()`), `lib/public_api_guard.php` für Public-API (`enforcePublicApiHardening()`) — saubere Trennung von Anfang an (B2)

## Scope — was in diesem Plan ist

**Drin:**
- ARCHITEKTUR.md komplett auf neuen Soll-Zustand umschreiben
- GitHub-Repo `pilzling-sporeprint-review-app` anlegen (privat)
- Folder-Struktur in `app.reviews/` auf dem Server (Hand-Anlage in cPanel-Dateimanager)
- `.env`-Boilerplate + Config-Pattern-Übernahme aus production-app (A1, A2 aus Pattern-Inventur)
- `schema_v1.sql` schreiben mit allen 6 Tabellen + Schema-Korrekturen aus Pre-Check
- Migration einspielen (manuell via phpMyAdmin)
- Google Business Profile API: OAuth einrichten, alle 3 Shop-Konten verbinden, Test-Call durchführen
- Trustpilot API: Key registrieren, Test ob Public-API für alle Reviews reicht
- Server-Profis-Support: schriftliche Bestätigung DDoS-Schutz + Bandbreiten-Limits (Tarif L 5.1)
- WinSCP-Tab für Sporeprint-FTP einrichten und Test-Push (Auto-Deploy validieren)

## NICHT in diesem Plan (Vollständigkeits-Prinzip — bewusste Auslassungen)

| Was | Grund |
|-----|-------|
| **JTL-REST-API-Setup** | API ist in Beta, JTL plant kostenpflichtige Lizenzierung (~100 €/Monat) — vermeiden. Workaround später: Reviews direkt aus JTL-MSSQL-DB ziehen (production-app hat dazu schon den Connection-Pattern). Eigener Plan dafür in Phase 1.5 oder 2.5 |
| **PHP-Implementation** (Cron-Skripte, API-Endpoints, Auth) | Das ist Phase 1 — eigener Plan |
| **Widget-Code** | Phase 2 |
| **Admin-Dashboard-Code** | Phase 3 |
| **Brevo-Integration** | Phase 4, in 4 Stufen, separater Plan |
| **Pattern-Heben nach `references/php-patterns/`** | Phase-1-Beifang (siehe Pre-Check D) — Sporeprint baut Public-API-Hardening zuerst, dann erst Pattern-Ordner |
| **`docs/DESIGN-SYSTEM.md`** | erst in Phase 3 (Admin-UI) relevant — heute kein UI-Pattern dokumentierbar |
| **`docs/CRON-JOBS.md`** | wird in Phase 1 angelegt sobald erste Cron-Skripte da sind |
| **GitHub Actions / CI** | nicht jetzt — WinSCP-Auto-Deploy reicht (siehe Konzept Thema 8) |
| **Tests** | für Phase 0 nicht nötig (kein laufender Code), kommen später entlang Phase 1+ |
| **Sporen-Rating-Visual** | Phase 2 Polish-Step |

## Phase-Struktur

### Phase 0.A — Docs-Review & Updates

**Ziel:** `docs/ARCHITEKTUR.md` komplett auf den neuen Server-Profis-Stack umschreiben. SSOT-Nachschlagewerk in `CLAUDE.md` aktualisieren.

**Konkrete Änderungen:**

1. **`docs/ARCHITEKTUR.md` neuer Inhalt:**
   - Tech-Stack-Tabelle: Server Profis cPanel + MariaDB + PHP 8.2 + Vanilla JS (statt Next.js + Vercel + KV)
   - Komponenten-Diagramm: zwei Subdomains (public + admin) auf einer Codebase, geteilte `lib/`
   - Folder-Struktur in `app.reviews/` (public, admin, lib, config, _db, _tools)
   - Datenmodell-Sektion: alle 6 Tabellen mit Schema-Korrekturen aus Pre-Check (`review_replies` aufgesplittet, `rate_limits` Sliding-Window, `visibility` mit Default, Admin-Filter-Indexe)
   - Sicherheits-Sektion: Verzeichnisschutz Admin + Public-API-Härtungs-Layer (CORS, Referer, Rate-Limit, Cache-Header, SRI, Datenminimierung) + Sicherheits-Hygiene-Checkliste
   - Multi-Tenant-Mechanik: `shop_id` als FK, Konvention für ENV-Variablen-Naming
   - Brevo-Integration: 4-Stufen-Modell (konkret bis Stufe 3)
   - Naming-Konvention: extern Sporeprint, intern reviews
   - Public-API: pures Reviews-Array (kein Envelope), Admin-API `{ok, data}`-Envelope
   - JTL-Produktbewertungen-Status: zurückgestellt, später via direkt-SQL aus JTL-MSSQL-DB

2. **`CLAUDE.md` SSOT-Nachschlagewerk-Tabelle aktualisieren:** alle Vercel-/KV-Verweise raus, Stack-Beschreibung anpassen.

3. **`MEMORY.md` Eintrag** unter "Bestätigte Entscheidungen": "JTL REST API zurückgestellt — Beta + drohende Kostenpflicht (~100 €/Monat). JTL-Produktbewertungen werden später via direkt-SQL aus JTL-MSSQL-DB gezogen (Pattern aus production-app)."

**Akzeptanzkriterium 0.A:** `docs/ARCHITEKTUR.md` zeigt vollständig den neuen Soll-Zustand. Beim Lesen ist klar wie das System läuft, ohne dass man das Konzept-Dokument bemühen muss. Konzept-Dokument bleibt als Diskussions-Verlauf, ARCHITEKTUR.md ist der finale Stand.

**Commit-Ende 0.A:** `docs(architektur): Server-Profis-Stack als Soll-Zustand`

---

### Phase 0.B — Infrastruktur-Audit

Greenfield-Projekt — kein Audit gegen bestehenden Sporeprint-Code. **Aber Drift-Prävention:**

- Pattern-Inventur aus Pre-Check (A1-A10) als Vorlage-Liste in den Implementations-Phasen referenziert
- Drift-Risiken B1 (Naming-Mismatch) und B2 (Public-vs-Admin-Endpoint-Trennung) als Konvention in `CLAUDE.md` "Harte Regeln"-Abschnitt eintragen

**Konkrete Aktion:** zwei zusätzliche Punkte in CLAUDE.md "Harte Regeln":

> **Pattern-Übernahme aus production-app:** Beim Kopieren von Code aus `C:\AI-Workspace\projects\dev\production-app\` immer deutsche Identifier (`erstellt_von`, `aktiv` etc.) zu englischen umbenennen (`created_by`, `is_active`). Nicht 1:1 paste — aktiv re-namen.

> **Public vs. Admin Endpoint-Trennung:** `lib/auth.php` mit `requireLogin()` für Admin-Endpoints, `lib/public_api_guard.php` mit `enforcePublicApiHardening()` für Public-Endpoints. Jeder Endpoint ruft genau eine der beiden Funktionen als allererste Zeile — niemals beide oder keine.

**Commit-Ende 0.B:** zusammen mit 0.A — `docs(architektur): Server-Profis-Stack als Soll-Zustand + Anti-Drift-Regeln`

---

### Phase 1 — Repo + initiale Code-Struktur

**Ziel:** GitHub-Repo `pilzling-sporeprint-review-app` existiert mit sauberem Initial-Commit. Lokales Working-Directory ist klar.

**Schritte:**

1. GitHub-Repo `pilzling-sporeprint-review-app` als **privates Repo** anlegen (CV) — keine README oder License-Auto-Generierung
2. Lokales Repo-Verzeichnis: **innerhalb** des Workspace-Projekt-Folders unter `src/` — der Workspace-Folder `sporeprint/` (aktuell noch `sternfaenger-review-tool/`) ist das **Workspace-Wrapper**, das Repo lebt in `src/`
3. `cd src/` → `git init` → `git remote add origin git@github.com:<owner>/pilzling-sporeprint-review-app.git`
4. **Initiale Folder-Struktur im Repo `src/`** anlegen:
   ```
   src/
   ├── public/
   │   ├── .htaccess
   │   └── (leer — Code kommt in Phase 1 Backend)
   ├── admin/
   │   ├── .htaccess           ← extra: Verzeichnisschutz-Hinweis-Kommentar
   │   └── (leer)
   ├── lib/
   │   └── (leer — Helper kommen in Phase 1)
   ├── config/
   │   ├── database.php        ← übernommen aus production-app + Anpassung
   │   └── .env.example        ← Template ohne echte Credentials
   ├── _db/
   │   ├── README.md           ← Migrations-Konvention dokumentieren (übernommen aus production-app)
   │   └── schema_v1.sql       ← initiale 6 Tabellen + Schema-Korrekturen
   ├── _tools/
   │   ├── README.md
   │   ├── check_umlauts.py    ← übernommen aus production-app
   │   ├── umlauts-patterns.txt
   │   └── umlauts-allowlist.txt
   ├── .gitignore              ← .env, *.log, etc.
   └── README.md               ← kurz: was Sporeprint ist, Setup-Hinweise, Verweis auf Workspace-Doku
   ```

5. `.htaccess`-Files in `public/` und `admin/` schreiben (übernommen aus production-app `src/.htaccess`)
6. `.gitignore` mit `.env`, `.env.*`, `*.log`, `node_modules/`, `.DS_Store`
7. Initial-Commit, Push auf GitHub

**Akzeptanzkriterium 1:** Repo lebt auf GitHub, lokal geklont, Folder-Struktur steht, alle Dateien aus production-app kopiert + lokal angepasst (deutsche → englische Identifier wo Code-relevant).

**Commit-Ende 1:** `feat: initiale Repo-Struktur + Boilerplate aus production-app`

---

### Phase 2 — Schema v1 schreiben + einspielen

**Ziel:** `schema_v1.sql` liegt im Repo, ist in `pilzling_reviews_app` eingespielt, alle 6 Tabellen existieren mit den Schema-Korrekturen aus Pre-Check.

**Tabellen** (Naming durchgängig English, alle Schema-Korrekturen aus Pre-Check Sektion C drin):

```sql
USE pilzling_reviews_app;

-- 1. shops — Stammdaten der 3 Shops
CREATE TABLE IF NOT EXISTS shops (
  shop_id            VARCHAR(32)  PRIMARY KEY,
  name               VARCHAR(128) NOT NULL,
  domain             VARCHAR(128) NOT NULL,
  google_place_id    VARCHAR(64)  NULL,
  trustpilot_unit_id VARCHAR(64)  NULL,
  jtl_api_url        VARCHAR(255) NULL,
  ci_primary         VARCHAR(7)   NULL,
  ci_secondary       VARCHAR(7)   NULL,
  created_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 2. reviews — alle Bewertungen
CREATE TABLE IF NOT EXISTS reviews (
  review_id      INT AUTO_INCREMENT PRIMARY KEY,
  shop_id        VARCHAR(32)  NOT NULL,
  source         ENUM('google','trustpilot','jtl') NOT NULL,
  external_id    VARCHAR(128) NOT NULL,
  stars          TINYINT      NOT NULL,
  author         VARCHAR(255) NULL,
  content        TEXT         NULL,
  language       VARCHAR(8)   NULL,
  product_name   VARCHAR(255) NULL,
  product_sku    VARCHAR(64)  NULL,
  posted_at      DATETIME     NOT NULL,
  fetched_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  visibility     ENUM('visible','hidden','flagged') NOT NULL DEFAULT 'visible',
  UNIQUE KEY uniq_source (shop_id, source, external_id),
  INDEX idx_shop_source_date (shop_id, source, posted_at DESC),
  INDEX idx_shop_stars (shop_id, stars),
  INDEX idx_shop_visibility (shop_id, visibility),
  CONSTRAINT fk_reviews_shop FOREIGN KEY (shop_id) REFERENCES shops(shop_id)
);

-- 3. review_replies — Korrektur C2: created_at vs. external_posted_at
CREATE TABLE IF NOT EXISTS review_replies (
  reply_id            INT AUTO_INCREMENT PRIMARY KEY,
  review_id           INT          NOT NULL,
  content             TEXT         NOT NULL,
  created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  external_posted_at  DATETIME     NULL,
  posted_by           VARCHAR(64)  NULL,
  external_status     ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  external_error      TEXT         NULL,
  CONSTRAINT fk_replies_review FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE
);

-- 4. sync_runs
CREATE TABLE IF NOT EXISTS sync_runs (
  run_id          INT AUTO_INCREMENT PRIMARY KEY,
  shop_id         VARCHAR(32)  NOT NULL,
  source          ENUM('google','trustpilot','jtl') NOT NULL,
  started_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at     TIMESTAMP    NULL,
  status          ENUM('running','ok','error') NOT NULL DEFAULT 'running',
  reviews_new     INT          NOT NULL DEFAULT 0,
  reviews_updated INT          NOT NULL DEFAULT 0,
  error_message   TEXT         NULL,
  INDEX idx_shop_started (shop_id, started_at DESC)
);

-- 5. widget_configs
CREATE TABLE IF NOT EXISTS widget_configs (
  shop_id              VARCHAR(32) PRIMARY KEY,
  layout               ENUM('carousel','feed') NOT NULL DEFAULT 'carousel',
  min_stars            TINYINT     NOT NULL DEFAULT 4,
  max_items            SMALLINT    NOT NULL DEFAULT 20,
  show_product_reviews TINYINT(1)  NOT NULL DEFAULT 1,
  custom_css           TEXT        NULL,
  CONSTRAINT fk_widget_configs_shop FOREIGN KEY (shop_id) REFERENCES shops(shop_id)
);

-- 6. rate_limits — Korrektur C3: Sliding-Window mit bucket_minute
CREATE TABLE IF NOT EXISTS rate_limits (
  ip_address     VARBINARY(16) NOT NULL,
  bucket_minute  INT UNSIGNED  NOT NULL,
  request_count  INT           NOT NULL DEFAULT 1,
  PRIMARY KEY (ip_address, bucket_minute),
  INDEX idx_bucket (bucket_minute)
);

-- Initial-Daten: 3 Shops vorbefüllen
INSERT INTO shops (shop_id, name, domain) VALUES
  ('pilzling',    'Pilzling',    'pilzling.shop'),
  ('pilzwald',    'Pilzwald',    'pilzwald.de'),
  ('shroom-boom', 'Shroom Boom', 'shroom-boom.de')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Initial-Daten: Default-Widget-Configs für die 3 Shops
INSERT INTO widget_configs (shop_id, layout, min_stars, max_items, show_product_reviews) VALUES
  ('pilzling',    'carousel', 4, 20, 1),
  ('pilzwald',    'carousel', 4, 20, 1),
  ('shroom-boom', 'carousel', 4, 20, 1)
ON DUPLICATE KEY UPDATE layout = VALUES(layout);
```

**Schritte:**

1. `_db/schema_v1.sql` mit obigem SQL schreiben (Naming-Konvention: `schema_vN.sql`, `USE pilzling_reviews_app;` als erste Zeile, nur CREATE/INSERT, keine DROPs)
2. `_db/README.md` mit Versions-Tabelle (zuerst nur v1) und Migrations-Konvention (übernommen aus production-app, an Sporeprint-DB angepasst)
3. **CV: Migration einspielen** via phpMyAdmin → DB `pilzling_reviews_app` auswählen → SQL-Tab → Inhalt von `schema_v1.sql` einfügen + ausführen
4. Verifikation: `SHOW TABLES;` zeigt 6 Tabellen, `SELECT * FROM shops;` zeigt 3 Zeilen
5. Domain-Werte für Pilzwald + Shroom-Boom prüfen (`pilzwald.de` ist Platzhalter — finale Domain klären)

**Akzeptanzkriterium 2:** Schema v1 ist im Repo committed, lokal eingespielt, alle 6 Tabellen existieren in `pilzling_reviews_app`, 3 Shops sind angelegt, 3 Widget-Configs sind angelegt.

**Commit-Ende 2:** `feat(_db): schema_v1 mit 6 Tabellen + Initial-Daten`

---

### Phase 3 — Externe API-Voraussetzungen klären

**Ziel:** Google Business Profile API + Trustpilot API sind soweit vorbereitet dass Phase 1 (Cron-Skripte) damit arbeiten kann. Server-Profis-Tarif-Details schriftlich.

**Schritte:**

1. **Google Cloud Projekt anlegen** (CV)
   - Google Cloud Console → neues Projekt "Pilzling Sporeprint"
   - Business Profile API aktivieren
   - OAuth 2.0 Client-ID erstellen (Web-Application-Type)
   - Authorized Redirect URI: `https://admin-sporeprint.pilzling.eu/admin/oauth/google/callback.php` (Phase 1 baut den Callback)
   - Test-Call durchführen mit Postman/curl: alle Reviews abrufbar?
   - Credentials sichern: Client-ID + Client-Secret in Bitwarden-Eintrag "Sporeprint — Google API"
   - **Pro Shop wiederholen** falls jeder Shop ein eigenes Google-Konto hat (vermutlich 3 separate OAuth-Flows nötig)

2. **Trustpilot API-Key registrieren** (CV)
   - https://developers.trustpilot.com/ → Developer Account anlegen
   - API-Key beantragen für Business Account
   - Test-Call mit Public-API: alle Reviews abrufbar oder nur Sample? Pagination prüfen
   - Credentials: API-Key in Bitwarden-Eintrag "Sporeprint — Trustpilot API"
   - **Wichtige Frage:** Kann eine API-Key-Instanz alle 3 Shops abfragen oder brauchen wir 3 separate?

3. ~~Server-Profis-Support kontaktieren~~ — **bewusst zurückgestellt (2026-05-03):** Tarif-Details (DDoS-Schutz, Bandbreite, Cron-Limits, PHP-Memory) sind nicht blockierend für Phase 0/1. Bei konkretem Problem später gezielt nachfragen.

4. **Brevo-JTL-Trigger-Mechanismus klären** (CV, weniger dringend — wird erst Phase 4 relevant)
   - Wie kommt der "Bestellung geliefert"-Event von JTL nach Brevo? Webhook von JTL? JTL-Connector? Manueller Sync?
   - Antwort dokumentieren, **nicht blockierend für Phase 1**

**Akzeptanzkriterium 3:** Drei Bitwarden-Einträge existieren (Google API, Trustpilot API, Server-Profis-Support-Antwort dokumentiert), Test-Calls erfolgreich für mindestens einen Shop pro Quelle, OR explizite Doku falls eine Quelle blockiert (z.B. "Trustpilot Public API liefert nur 50 Reviews — wir brauchen Business-Plan-Upgrade später").

**Commit-Ende 3:** keiner — externer Aufgaben-Block, Doku in MEMORY.md

---

### Phase 4 — WinSCP-Auto-Deploy einrichten

**Ziel:** Push auf `main` (oder Save in WinSCP-Watch-Folder) deployt automatisch nach `/home/pilzling/app.reviews/`.

**Schritte:**

1. **CV:** WinSCP öffnen → neuen Site-Tab "Sporeprint" anlegen
   - Host: `ftp.pilzling.eu` (gleicher FTP-Server wie production-app)
   - User + Passwort: aus Bitwarden-Eintrag "Sporeprint — FTP" (anlegen falls noch nicht da)
   - Remote-Pfad: `/home/pilzling/app.reviews/`
   - Local-Pfad: lokaler `src/`-Ordner des Sporeprint-Repos
2. WinSCP "Keep remote directory up to date" aktivieren (Auto-Deploy)
3. Test-Push: leere Datei `src/public/test-deploy.txt` → speichern → in WinSCP Auto-Deploy beobachten → über `https://sporeprint.pilzling.eu/test-deploy.txt` abrufen
4. Test-File wieder löschen (lokal und remote)
5. **Folder-Struktur auf dem Server anlegen** (falls cPanel die nicht automatisch erstellt hat): `app.reviews/public/`, `app.reviews/admin/`, `app.reviews/lib/`, `app.reviews/config/`, `app.reviews/_db/`, `app.reviews/_tools/`
6. `.env`-Datei manuell in `app.reviews/config/.env` anlegen (NICHT committen!) mit DB-Credentials, später Google-/Trustpilot-API-Keys

**Akzeptanzkriterium 4:** WinSCP-Tab funktioniert, Test-Deploy ist erfolgreich (Datei aus lokalem Repo erreicht den Server), `.env` liegt auf dem Server, Folder-Struktur ist live.

**Commit-Ende 4:** keiner (kein Code-Change), aber MEMORY.md-Update "WinSCP-Tab Sporeprint eingerichtet, Auto-Deploy verifiziert".

---

### Phase 5 — Verifikation (gegen Drift)

**Ziel:** Code ↔ Doku stimmen überein. Kein blinder Fleck.

**Checks:**

1. **`docs/ARCHITEKTUR.md` vs. tatsächliche Folder-Struktur** (`app.reviews/` lokal + remote): stimmen die in der Doku genannten Pfade mit der Realität überein?
2. **`docs/ARCHITEKTUR.md` Datenmodell-Sektion vs. `_db/schema_v1.sql`**: jede Spalte in der Doku existiert im SQL und umgekehrt? Naming identisch?
3. **`CLAUDE.md` SSOT-Nachschlagewerk**: zeigt korrekt auf alle aktiven Dokumente, keine veralteten Verweise (Vercel etc.)?
4. **`MEMORY.md`**: hat einen Eintrag zu Phase-0-Abschluss + JTL-REST-API-Verzicht + Server-Profis-Tarif-Antwort?
5. **GitHub-Repo `pilzling-sporeprint-review-app`**: existiert, hat Initial-Commit, README beschreibt Sporeprint kurz?
6. **Server-Profis `app.reviews/`**: 6 Unterordner existieren, `.env` liegt in `config/`, Verzeichnisschutz auf `admin-sporeprint.pilzling.eu` ist aktiv (CV verifiziert mit Browser-Test)?
7. **DB `pilzling_reviews_app`**: 6 Tabellen + 3 Shops + 3 Widget-Configs vorhanden (`SELECT shop_id, name FROM shops;` gibt 3 Zeilen)?
8. **Externe APIs**: Google + Trustpilot Test-Calls dokumentiert (Bitwarden-Einträge + MEMORY-Notiz)?

**Akzeptanzkriterium 5:** Alle 8 Checks grün ODER explizit als "blockiert weil X" markiert (z.B. wenn Trustpilot-API-Antrag noch nicht durch ist).

**Commit-Ende 5:** `chore: Phase 0 Verifikation — Foundation steht`

## Akzeptanzkriterien (Plan gilt als abgeschlossen wenn…)

- [ ] `docs/ARCHITEKTUR.md` zeigt vollständig den Server-Profis-Stack-Soll-Zustand (keine Vercel-Reste)
- [ ] GitHub-Repo `pilzling-sporeprint-review-app` existiert, hat Initial-Commit
- [ ] Lokales Repo `src/` hat Folder-Struktur (public, admin, lib, config, _db, _tools)
- [ ] `_db/schema_v1.sql` ist commitet und in `pilzling_reviews_app` eingespielt
- [ ] 3 Shops sind in der `shops`-Tabelle
- [ ] Google Business Profile API ist konfiguriert + Test-Call erfolgreich (oder explizit blockiert dokumentiert)
- [ ] Trustpilot API ist konfiguriert + Test-Call erfolgreich (oder explizit blockiert dokumentiert)
- [ ] Server-Profis-Support-Antwort zu Tarif-Details liegt vor
- [ ] WinSCP-Auto-Deploy funktioniert (Test-Push verifiziert)
- [ ] `.env` liegt auf dem Server unter `app.reviews/config/.env` (mit DB-Credentials, optional schon API-Keys)
- [ ] Verzeichnisschutz auf `admin-sporeprint.pilzling.eu` ist aktiv (Browser-Test)
- [ ] Verifikations-Phase 5 abgeschlossen, alle Checks grün oder explizit dokumentiert
- [ ] ROADMAP.md "Aktueller Fokus" auf Phase 1 zeigt
- [ ] Konzept-Dokument bleibt im `_plans/`-Ordner (Diskussions-Verlauf), Phase-0-Plan wird nach Abschluss nach `_archive/_plans/` verschoben

## Referenzen

- **Konzept (Stufen 1+2):** [`2026-05-02-architektur-pivot-konzept.md`](2026-05-02-architektur-pivot-konzept.md)
- **Roadmap:** [`ROADMAP.md`](ROADMAP.md)
- **Pattern-Quelle production-app:**
  - `C:\AI-Workspace\projects\dev\production-app\src\config\database.php` (A1)
  - `C:\AI-Workspace\projects\dev\production-app\src\.htaccess` (A2)
  - `C:\AI-Workspace\projects\dev\production-app\src\includes\helpers.php` Zeilen 6-42 (A3, A4)
  - `C:\AI-Workspace\projects\dev\production-app\src\api\notes.php` (A5 als Vorlage-Skelett)
  - `C:\AI-Workspace\projects\dev\production-app\src\includes\auth.php` (A6, A7)
  - `C:\AI-Workspace\projects\dev\production-app\_db\README.md` (A8)
  - `C:\AI-Workspace\projects\dev\production-app\_tools\check_umlauts.py` + `umlauts-patterns.txt` + `umlauts-allowlist.txt` (A9)
- **Dev-Projekt-Standard:** [`C:\AI-Workspace\references\dev-projekt-standard.md`](../../../../references/dev-projekt-standard.md)
- **Sicherheits-Pattern (Stand Konzept):** Konzept-Sektion 3 (Verzeichnisschutz) + Sektion 4 (Public-API-Härtung)

## Reihenfolge der Schritte für CV

Nicht alle Phasen müssen sequenziell — externe Aufgaben (Phase 3) laufen **parallel** zu Repo-Setup (Phase 1+2):

```
Tag 1 — CV-Hand-Aufgaben starten:
  Parallel:
    a) Google Cloud Setup beantragen (Phase 3.1) — kann mehrere Tage Wartezeit haben
    b) Trustpilot Developer Account beantragen (Phase 3.2)
    c) Server-Profis-Support-Anfrage rausschicken (Phase 3.3)

Tag 1 — Ich (Claude):
  - Phase 0.A + 0.B (Docs-Update)
  - Phase 1 (Repo-Struktur, Boilerplate aus production-app kopieren + anpassen)
  - Phase 2 (schema_v1.sql schreiben)

Tag 1 oder 2 — CV:
  - Phase 2 Schritt 3 (Schema einspielen via phpMyAdmin)
  - Phase 4 (WinSCP-Tab anlegen + Test-Deploy)

Tag 1-3 — wenn externe Antworten reinkommen:
  - Phase 3 schrittweise abhaken

Letzter Tag:
  - Phase 5 (Verifikation)
  - Plan archivieren
```

Phase 1 + 2 sind nicht von externen APIs abhängig. Sobald die durch sind und WinSCP läuft, ist die Code-Foundation fertig — Phase 1 (Backend-Implementation) kann starten sobald Phase 3 grün ist.
