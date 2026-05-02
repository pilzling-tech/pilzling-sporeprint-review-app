# Sporeprint — Architektur

**Status:** Soll-Architektur. Stand der Doku: 2026-05-03 (Phase 0.A des Foundation-Plans).

Dieses Dokument beschreibt **wie das System aufgebaut ist** (bzw. werden soll). Was als nächstes gebaut wird, gehört in `_plans/`.

## Naming-Konvention

| Layer | Name | Bedeutung |
|-------|------|-----------|
| Branding (extern sichtbar) | **Sporeprint** | Widget-Footer, Marketing, UI-Strings, öffentliche Subdomains |
| Tech-Layer (intern) | **reviews** | DB-Name, Tabellen, Code-Identifier, alle Pfade auf dem Server |
| GitHub-Repo | `pilzling-tech/pilzling-sporeprint-review-app` | Branding-Identifier auf GitHub-Org-Ebene |
| Workspace-Projekt-Ordner | `sporeprint` (geplant) / `sternfaenger-review-tool` (Rename pending) | identifiziert das Projekt im Workspace |

Sporeprint = Sporenabdruck. Mykologisches Bestimmungsverfahren — Reviews als unverfälschter Abdruck der Kund:innen-Erfahrung.

## Zweck

Eigenes Review-Aggregations- und Management-System für drei JTL-Shops (Pilzling, Pilzwald, Shroom Boom). Ersetzt den Drittanbieter `onlinereviews.tech` (80 €/Monat → 0 €/Monat).

## Tech Stack

| Bereich | Technologie | Kosten |
|---------|-------------|--------|
| Hosting | Server Profis cPanel (cp61.sp-server.net), Tarif "Webhosting Business L 5.1" | bestehend (gleicher Server wie production-app) |
| Web-Server | Apache + LiteSpeed (LSCache automatisch via Cache-Header) | inklusive |
| Application | PHP 8.2 | inklusive |
| Datenbank | MariaDB (DB `pilzling_reviews_app`) | inklusive |
| Frontend Widget | Vanilla JavaScript (kein Framework) | 0 € |
| Admin-UI | PHP-Server-Rendered + Vanilla JS | 0 € |
| Cron | cPanel-Cronjobs (PHP-CLI alle 6 h) | inklusive |
| TLS | Let's Encrypt via cPanel AutoSSL | 0 € |
| E-Mail-Automation | Brevo (bestehender Account) | 0 € extra |
| Google Reviews | Google Business Profile API (OAuth 2.0) | 0 € |
| Trustpilot Reviews | Trustpilot Public API (eventuell Business-Plan-Upgrade in Zukunft) | 0 € (TBD) |
| JTL Produktbewertungen | **zurückgestellt** — später via direkt-SQL aus JTL-MSSQL-DB | 0 € |

**Gesamt laufend: 0 €/Monat** (vs. 80 €/Monat bisher onlinereviews.tech).

## Komponenten-Diagramm

```
┌──────────────────────────────────────────────────────────────┐
│  PUBLIC LAYER  —  sporeprint.pilzling.eu                     │
│  DocRoot: /home/pilzling/app.reviews/public/                 │
│                                                              │
│  · widget.js              ← als <script>-Tag im JTL-Shop    │
│  · GET /api/reviews       ← Public-API (read-only)          │
│                                                              │
│  Härtung (alle 6 Layer aktiv):                              │
│  · CORS-Whitelist pro Shop                                  │
│  · Referer-Check                                             │
│  · Rate-Limit per IP (Sliding-Window in MariaDB)            │
│  · Cache-Header 6h (LSCache + Browser/Proxy)                │
│  · SRI-Hash für widget.js (im JTL-Template eingebettet)     │
│  · Datenminimierung (keine IP/Geo, keine Cookies)           │
└──────────────────────────────────────────────────────────────┘
                            ▲
                            │ liest aus
┌──────────────────────────────────────────────────────────────┐
│  DATA LAYER  —  MariaDB pilzling_reviews_app                 │
│  6 Tabellen (siehe Datenmodell unten)                       │
└──────────────────────────────────────────────────────────────┘
                            ▲
                            │ schreibt rein
┌──────────────────────────────────────────────────────────────┐
│  CRON LAYER  —  PHP-CLI alle 6h (cPanel-Cronjobs)            │
│  Pfad: /home/pilzling/app.reviews/_tools/                    │
│                                                              │
│  · cron-fetch-google.php       (3 Shops)                     │
│  · cron-fetch-trustpilot.php   (3 Shops)                     │
│  · cron-fetch-jtl.php          (zurückgestellt — später)     │
│  · cron-cleanup-rate-limits.php (alte Buckets purgen)        │
└──────────────────────────────────────────────────────────────┘
                            ▲
                            │ schreibt rein / liest
┌──────────────────────────────────────────────────────────────┐
│  ADMIN LAYER  —  admin-sporeprint.pilzling.eu                │
│  DocRoot: /home/pilzling/app.reviews/admin/                  │
│                                                              │
│  Schutz (Defense in Depth):                                  │
│  · Layer 1: cPanel-Verzeichnisschutz (.htaccess Basic Auth) │
│  · Layer 2: App-Login (Single-Admin via .env)               │
│                                                              │
│  · /admin/dashboard.php   ← Reviews einsehen, antworten     │
│  · /admin/api/reply.php   ← schreibende Endpoints           │
│  · /admin/oauth/google/   ← OAuth-Callbacks                 │
│  · /admin/widget-config/  ← Pro-Shop-Konfiguration          │
└──────────────────────────────────────────────────────────────┘
```

**Wichtig:** Public Layer und Admin Layer teilen sich **eine** Codebase. Geteilte Helper liegen in `app.reviews/lib/` (außerhalb der DocRoots, NICHT öffentlich erreichbar). Die zwei DocRoots zeigen nur auf ihre jeweiligen Endpoints, requiren aber `lib/`-Helper über relative Pfade.

## Folder-Struktur (Server + Repo)

```
/home/pilzling/app.reviews/                         ← Server-Wrapper-Folder
├── public/                                          ← DocRoot sporeprint.pilzling.eu
│   ├── .htaccess                                   ← Hardening: .env-Block, Indexes off, UTF-8
│   ├── widget.js
│   └── api/
│       └── reviews.php                             ← GET /api/reviews
├── admin/                                           ← DocRoot admin-sporeprint.pilzling.eu
│   ├── .htaccess                                   ← + Verzeichnisschutz-Hinweis
│   ├── index.php                                   ← Login
│   ├── dashboard.php
│   ├── api/
│   │   └── reply.php                               ← POST /api/reply
│   └── oauth/
│       └── google/callback.php                     ← Google OAuth Redirect
├── lib/                                             ← geteilte Helper, NICHT public
│   ├── db.php                                      ← PDO-Singleton
│   ├── auth.php                                    ← Single-Admin-Login + requireLogin()
│   ├── public_api_guard.php                        ← enforcePublicApiHardening()
│   ├── helpers.php                                 ← apiSuccess/apiError/jsonResponse
│   ├── api_clients/
│   │   ├── google.php
│   │   └── trustpilot.php
│   └── rate_limit.php
├── config/
│   ├── database.php                                ← getDb() + loadEnv()
│   └── .env                                        ← DB-Credentials, API-Keys (NICHT im Repo!)
├── _db/                                             ← Migrations
│   ├── README.md
│   └── schema_vN.sql                               ← fortlaufend nummeriert
└── _tools/                                          ← Cron-Skripte (CLI), nicht über HTTP
    ├── cron-fetch-google.php
    ├── cron-fetch-trustpilot.php
    └── cron-cleanup-rate-limits.php
```

**Repo-Struktur** spiegelt diesen Server-Folder **ohne `config/.env`** (steht nur auf dem Server). Repo-Root ist eine Ebene höher als `app.reviews/` — der Repo-Inhalt wird per WinSCP-Auto-Deploy direkt nach `/home/pilzling/app.reviews/` synchronisiert.

## Datenmodell

DB: `pilzling_reviews_app`. Naming durchgängig English. Schema gilt ab `schema_v1.sql` (Migrations-Konvention siehe `_db/README.md`).

### Tabelle `shops` — Stammdaten der 3 Shops

```sql
shops (
  shop_id            VARCHAR(32)  PRIMARY KEY,            -- "pilzling" | "pilzwald" | "shroom-boom"
  name               VARCHAR(128) NOT NULL,
  domain             VARCHAR(128) NOT NULL,                -- "pilzling.shop"
  google_place_id    VARCHAR(64)  NULL,
  trustpilot_unit_id VARCHAR(64)  NULL,
  jtl_api_url        VARCHAR(255) NULL,                    -- vorerst nicht genutzt (JTL zurückgestellt)
  ci_primary         VARCHAR(7)   NULL,                    -- "#7a4f1a"
  ci_secondary       VARCHAR(7)   NULL,
  created_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
)
```

### Tabelle `reviews` — alle Bewertungen polymorph über `source`

```sql
reviews (
  review_id      INT AUTO_INCREMENT PRIMARY KEY,
  shop_id        VARCHAR(32)  NOT NULL,
  source         ENUM('google','trustpilot','jtl') NOT NULL,
  external_id    VARCHAR(128) NOT NULL,                    -- Quelle-eigene ID
  stars          TINYINT      NOT NULL,                    -- 1-5
  author         VARCHAR(255) NULL,                        -- Vorname/Initialen
  content        TEXT         NULL,
  language       VARCHAR(8)   NULL,                        -- "de" | "en"
  product_name   VARCHAR(255) NULL,                        -- nur bei source='jtl'
  product_sku    VARCHAR(64)  NULL,
  posted_at      DATETIME     NOT NULL,                    -- wann der Bewertende geschrieben hat
  fetched_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  visibility     ENUM('visible','hidden','flagged') NOT NULL DEFAULT 'visible',
  UNIQUE KEY uniq_source (shop_id, source, external_id),
  INDEX idx_shop_source_date (shop_id, source, posted_at DESC),
  INDEX idx_shop_stars (shop_id, stars),
  INDEX idx_shop_visibility (shop_id, visibility),
  CONSTRAINT fk_reviews_shop FOREIGN KEY (shop_id) REFERENCES shops(shop_id)
)
```

Unique-Key auf `(shop_id, source, external_id)` verhindert Duplikate beim Re-Fetch (Cron-Idempotenz). Drei Indexe für die häufigsten Query-Pfade: Public-API (Datum-sortiert), Admin-Filter (Sterne, Visibility).

### Tabelle `review_replies` — unsere Antworten (1:1 zu reviews)

```sql
review_replies (
  reply_id            INT AUTO_INCREMENT PRIMARY KEY,
  review_id           INT          NOT NULL,
  content             TEXT         NOT NULL,
  created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,    -- bei uns gespeichert
  external_posted_at  DATETIME     NULL,                                   -- bei Quelle gepostet
  posted_by           VARCHAR(64)  NULL,                                   -- Admin-User
  external_status     ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  external_error      TEXT         NULL,
  CONSTRAINT fk_replies_review FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE
)
```

`created_at` und `external_posted_at` sind getrennt: Wir können eine Antwort speichern, bevor sie an Google/Trustpilot gepostet wird. `external_status` zeigt den Push-Status.

### Tabelle `sync_runs` — Cron-Lauf-Protokoll

```sql
sync_runs (
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
)
```

Wird bei jedem Cron-Lauf neu geschrieben. Erlaubt Drift-Erkennung ("letzter erfolgreicher Lauf vor 18 Stunden — Cron hängt") und Debugging (welche Quelle hat wann welche Errors geliefert).

### Tabelle `widget_configs` — Pro-Shop-Widget-Settings

```sql
widget_configs (
  shop_id              VARCHAR(32) PRIMARY KEY,
  layout               ENUM('carousel','feed') NOT NULL DEFAULT 'carousel',
  min_stars            TINYINT     NOT NULL DEFAULT 4,                     -- nur Reviews >= N Sterne
  max_items            SMALLINT    NOT NULL DEFAULT 20,
  show_product_reviews TINYINT(1)  NOT NULL DEFAULT 1,
  custom_css           TEXT        NULL,
  CONSTRAINT fk_widget_configs_shop FOREIGN KEY (shop_id) REFERENCES shops(shop_id)
)
```

Vom Admin-Dashboard editierbar. Public-API liest hier shop-spezifische Anzeige-Regeln.

### Tabelle `rate_limits` — Sliding-Window-Counter

```sql
rate_limits (
  ip_address     VARBINARY(16) NOT NULL,                                   -- IPv4/IPv6 binär
  bucket_minute  INT UNSIGNED  NOT NULL,                                   -- floor(unix_ts / 60)
  request_count  INT           NOT NULL DEFAULT 1,
  PRIMARY KEY (ip_address, bucket_minute),
  INDEX idx_bucket (bucket_minute)
)
```

Sliding-Window-Pattern: pro Minute eine Zeile pro IP, Counter inkrementiert. Public-API checkt summe der letzten N Minuten gegen Limit. Cleanup-Cron löscht alle `bucket_minute < FLOOR(UNIX_TIMESTAMP()/60) - 60` (1h Retention) — DSGVO-Datenminimierung.

### Bewusst nicht im v1-Schema

- **`review_requests`** (Brevo-Funnel-Tracking) — kommt erst Phase 4 wenn Brevo-Stufe 3 konkretisiert wird. Schema heute raten lohnt nicht.
- **`users`-Tabelle für Mehr-Admin-Login** — Single-Admin via `.env` reicht v1, User-Stamm erst Phase 3 falls nötig.

## Multi-Tenant-Mechanik

Jede Datenzeile ist über `shop_id` (FK auf `shops`) einem Shop zugeordnet. Helper im Code (z.B. `getReviewsForShop($shopId)`) verwenden `shop_id` immer als Filter.

**API-Keys pro Shop in ENV-Variablen:**

```
GOOGLE_OAUTH_REFRESH_TOKEN_PILZLING=...
GOOGLE_OAUTH_REFRESH_TOKEN_PILZWALD=...
GOOGLE_OAUTH_REFRESH_TOKEN_SHROOMBOOM=...

TRUSTPILOT_API_KEY=...                           # ggf. shared, falls eine Trustpilot-Org
```

Ein Backend-Helper `getCredentialsForShop($shopId, $service)` löst das auf. Niemals einen Shop hardcodieren.

## Sicherheit

### Schicht 1: cPanel-Verzeichnisschutz (Admin Pre-Auth)

`admin-sporeprint.pilzling.eu` ist hinter HTTP Basic Auth. Browser-Popup vor jedem Zugriff. **Vor** der App-Login-Seite. User+Passwort gemeinsam, in Bitwarden geteilt ("Sporeprint — Verzeichnisschutz Admin"). Schützt automatisch alle Admin-Endpoints, auch Test- oder Debug-Files.

### Schicht 2: App-Login (Single-Admin)

Hinter dem Verzeichnisschutz folgt eine PHP-Login-Seite mit Single-Admin-Credentials aus `.env`. Session-Cookie mit `Secure`, `HttpOnly`, `SameSite=Strict`. `requireLogin()` als allererste Zeile in jedem Admin-Endpoint.

### Schicht 3: Public-API-Härtung (6 Layer)

`public_api_guard.php` → `enforcePublicApiHardening($shopId)` als allererste Zeile in jedem Public-Endpoint:

1. **CORS-Whitelist** — `Access-Control-Allow-Origin` dynamisch nach `shops.domain` gesetzt
2. **Referer-Check** — `Referer:` muss zu `shops.domain` passen, sonst 403
3. **Rate-Limit per IP** — Sliding-Window via `rate_limits`-Tabelle, 60 Req/Min/IP
4. **Cache-Header** — `Cache-Control: public, max-age=21600` (6 h)
5. **SRI-Hash** — Widget-`<script>` im JTL-Template hat `integrity="sha384-..."` → manipulation-resistant
6. **Datenminimierung** — Response enthält nur Sterne, Inhalt, Vorname, Tag-Datum, Plattform, Produktname (bei JTL). Keine IP/Geo, keine E-Mail, keine internen IDs.

### Datenschutz / DSGVO

- **Keine Bewerter-IP/Geo** in DB oder Response. Quell-APIs (Google, Trustpilot) liefern das eh nicht.
- **Aufrufer-IP** nur kurzfristig im `rate_limits`-Bucket (max. 1h Retention), nicht persistiert. Rechtsgrundlage: Art. 6(1)(f) DSGVO (berechtigtes Interesse, Missbrauchsschutz). Wird einzeilig in Datenschutzerklärung der Shops erwähnt.
- **Hard-Delete** bei aus Quellen gelöschten Reviews — keine "Geister-Daten".
- **Widget setzt keine Cookies** und nutzt **kein LocalStorage** — fällt damit nicht unter Cookie-Banner-Pflicht im Shop.

### Sicherheits-Hygiene-Checkliste (gilt für alle Endpoints)

- [ ] `.env` und `.env.*` per `.htaccess` blockiert (auch in DocRoots)
- [ ] HTTPS-only Cookies (`Secure`, `HttpOnly`, `SameSite`)
- [ ] CSRF-Tokens auf allen POST-Endpoints im Admin
- [ ] Prepared Statements überall (PDO mit `EMULATE_PREPARES=false`)
- [ ] `requireLogin()` (Admin) oder `enforcePublicApiHardening()` (Public) als allererste Zeile in jedem Endpoint — niemals beide oder keine
- [ ] Keine Backup-Files (`.env.bak`, `.git/`, `*.old.php`) im DocRoot
- [ ] Login-Endpoint mit Rate-Limit / Lockout nach mehreren Fehlversuchen

## API-Response-Konvention

| Endpoint-Typ | Response-Format |
|--------------|-----------------|
| Public API (`public/api/reviews.php`) | **pures JSON-Array** der Reviews (kein Envelope) — Widget-optimiert |
| Admin API (`admin/api/*.php`) | **`{ok: true, data: ...}`** bei Erfolg, **`{ok: false, error: "<msg>"}`** bei Fehler — Pattern aus production-app, Frontend kennt das schon |

Begründung: Public-API soll für JS-Frontends so direkt verwertbar sein wie möglich. Admin-API hat komplexere Fehler-Cases und nutzt das etablierte Envelope für konsistente Toast-Anzeige im Dashboard.

## Brevo-Integration (4-Stufen-Modell, in Phase 4)

Stufe 1: Brevo unabhängig (Trigger aus JTL, Sporeprint nicht beteiligt) → Stufe 2: Sporeprint empfängt Brevo-Webhooks (Open/Click-Counter) → Stufe 3: Sporeprint triggert Brevo (Funnel-Tracking, eigene `review_requests`-Tabelle, gehashte Email) → Stufe 4: bidirektionales Tagging (perspektivisch).

Konkret eingeplant bis **Stufe 3** (siehe `_plans/ROADMAP.md` Phase 4). Stufe 4 erst wenn Stufen 1-3 stabil und Marketing konkret danach fragt.

## Cron-Strategie

cPanel-Cronjobs alle 6 h, getriggert per CLI:

```
0 */6 * * *  php /home/pilzling/app.reviews/_tools/cron-fetch-google.php
30 */6 * * * php /home/pilzling/app.reviews/_tools/cron-fetch-trustpilot.php
*/15 * * * *  php /home/pilzling/app.reviews/_tools/cron-cleanup-rate-limits.php
```

Skripte schreiben Lauf-Status in `sync_runs`. Bei Fehler: Log + Eintrag in `sync_runs` mit Status `error`, `error_message`. Optional: E-Mail-Benachrichtigung bei Fehler über Brevo (Phase 4).

**JTL-Cron** (`cron-fetch-jtl.php`) ist zurückgestellt — wird gebaut sobald wir den direkt-SQL-Workaround zur JTL-MSSQL-DB umgesetzt haben (eigener Plan, nicht Phase 0).

## Deploy-Pipeline

WinSCP-Auto-Deploy von lokalem Repo `src/` nach Server `/home/pilzling/app.reviews/`. "Keep remote directory up to date"-Modus. **DB-Migrations bleiben manuell** via phpMyAdmin (nummerierte SQL-Files in `_db/`, README pflegt Versions-Tabelle).

## Browser-/Server-/DB-Voraussetzungen

| Komponente | Mindestversion |
|------------|----------------|
| Server | PHP 8.2+ (Server Profis Default) |
| DB | MariaDB 10.4+ (Server Profis Default — `IF NOT EXISTS` für Spalten-DDL) |
| Browser (Widget) | Modern Browsers — `fetch`, CSS Grid, CSS Scroll Snap (= alles seit ~2019) |
| Browser (Admin) | gleiches Niveau, plus Login-Cookies |

## Repo + Workspace

- **GitHub-Repo:** `pilzling-tech/pilzling-sporeprint-review-app` (privat, Org `pilzling-tech`, Collaborator `cmvetter92`)
- **Workspace-Projekt-Ordner:** `C:\AI-Workspace\projects\dev\sporeprint\` (Rename pending — heißt aktuell noch `sternfaenger-review-tool`)
- **Lokales Repo-Verzeichnis:** `<workspace-folder>/src/`

## Bewusst nicht im System

| Feature | Grund |
|---------|-------|
| SMS-Anfragen | DSGVO-Aufwand, Brevo deckt das ab |
| WhatsApp-Anfragen | WhatsApp Business API teuer + komplex |
| Video-Reviews | nicht vorhanden, kein unmittelbarer Mehrwert |
| Popup-Widget | schlechte UX, meist ignoriert |
| Cloudflare CDN/Proxy | DNS bleibt bei Server Profis, LSCache reicht |
| Vercel / Cloud-Hosting | Architektur-Pivot 2026-05-02 → Konsolidierung auf Server Profis |
| Cloudflare Access (Zero Trust) | bräuchte DNS-Umzug, nicht nötig — `.htaccess` reicht |
| JTL REST API | drohende Kostenpflicht (~100 €/Monat) — später via direkt-SQL aus JTL-MSSQL-DB als Workaround |
| Mehrsprachiges Widget | vorerst nur Deutsch — Englisch später bei Bedarf |
| Webhook-Empfang für Echtzeit | Trustpilot bietet Webhooks, Google nicht — Cron alle 6h reicht initial |
| User-Management mehrere Admins | Single-Admin via `.env` reicht v1, ausgebaut bei Bedarf |

## Verweise

- **Konzept-Diskussion:** `_plans/2026-05-02-architektur-pivot-konzept.md`
- **Roadmap:** `_plans/ROADMAP.md`
- **Aktiver Detailplan:** `_plans/2026-05-03-phase-0-foundation.md`
- **Dev-Projekt-Standard (Workspace):** `C:\AI-Workspace\references\dev-projekt-standard.md`
- **production-app als Pattern-Quelle (siehe Pre-Check im Konzept-Dokument):** `C:\AI-Workspace\projects\dev\production-app\`
