# Konzept — Architektur-Pivot: Vercel → Server Profis + MariaDB

**Status:** Konzept-Stufen 1 + 2 ✅ abgeschlossen am 2026-05-02 — reif für Detailplan (Stufe 3)
**Erstellt:** 2026-05-02
**Stufe:** 1 (Konzept) ✅ → 2 (Pre-Check) ✅ → 3 (Detailplan) als nächstes

## Naming-Konvention

- **Extern / Branding:** **Sporeprint** — Widget-Footer, Marketing, UI-Strings, Public-Subdomain, Repo-Name.
- **Intern / Technisch:** **reviews** — DB-Name `pilzling_reviews_app`, alle Tabellen-/Spalten-/Code-Identifier.
- Hintergrund: Projekt startete als "Sternfänger" — Name ist mit existierendem Drittservice belegt, daher 2026-05-02 umbenannt. Sporeprint = Sporenabdruck (mykologisches Bestimmungsverfahren) — passt ins Pilzling-Vokabular und steht metaphorisch für den unverfälschten Abdruck der Kund:innen-Erfahrung.

## Ziel

Sporeprint wechselt vom ursprünglich geplanten Vercel-Stack auf die **bestehende Server-Profis-Infrastruktur** (cPanel + MariaDB + PHP), die schon production-app trägt. Hauptmotivation: **Stack-Konsolidierung** (eine Infrastruktur, ein Login, gleiche Tools, keine Cloud-Vendor-Abhängigkeit), **bessere Eignung von SQL für Analytics/Filter** als Key-Value-Store, und **eingebaute Sicherheits-Layer** (`.htaccess` Pre-Auth) die mit Vercel komplizierter wären.

Zusätzliches Ziel dieses Konzepts: **Sicherheits-Pattern** (Public-API-Härtung + Admin Pre-Auth) gleich von Anfang an mitdenken — nicht nachträglich aufpfropfen wie bei production-app, wo das aktuell noch fehlt.

## Verhältnis zu production-app

**Sporeprint ist vollständig standalone.** Eigene Domain, eigene DB (`pilzling_reviews_app`), eigenes Repo (`pilzling-sporeprint-review-app`), eigener FTP-Target. **Keine Cross-App-Code-Imports**, keine geteilten Tabellen, keine Cross-DB-Queries zur Laufzeit.

production-app dient ausschließlich als **Pattern-Referenz** — wir gucken dort hin, kopieren Snippets rüber, passen sie in Sporeprint lokal an, leben dann unabhängig weiter.

**Cross-App-Konsistenz wird über Pattern-Doku statt Code-Sharing sichergestellt:** Wiederverwendbare Snippets (.htaccess-Template, DB-Connection-Boilerplate, Auth-Pattern, Sicherheits-Layer) wandern als dokumentierte Vorlagen in einen neuen Workspace-Ordner `C:\AI-Workspace\references\php-patterns\` — beide Apps kopieren von dort, sind aber laufzeit-unabhängig. Pattern-SSOT (Doku), nicht Code-SSOT (shared lib). Wenn ein Pattern verbessert wird: erst in `references/php-patterns/` aktualisieren, dann bewusst in jeder App nachziehen wo relevant.

**Daraus folgt für den Pre-Check (Stufe 2):**
- Wiederverwendung = **Pattern-Inventur** (was kopieren wir rüber)
- Drift-Punkte = **App-intern** (innerhalb von Sporeprint keine Parallel-Implementierungen), NICHT cross-app
- Schema-Korrekturen = **Sporeprint-intern** (Naming, Redundanzen im eigenen Schema)
- Plus: Pattern-SSOT-Vorschlag (welche Patterns gehören nach `references/php-patterns/`)

## Brainstorming-Themen (Reihenfolge nicht relevant)

### 1. DB-Strategie ✅ ENTSCHIEDEN (2026-05-02)

**Entscheidung:** Eigene MariaDB-Datenbank `pilzling_reviews_app` (bereits angelegt auf Server Profis).

Naming-Konvention `<projekt>_app` passt zu `pilzling_app` (production-app) — falls weitere Apps dazukommen, gleiches Schema. Saubere Trennung von Backups, Permissions, Migration-Risiken. Cross-Joins zu `pilzling_app` (z.B. "welche Bestellung führte zu welcher Bewertung") werden bei Bedarf später auf Application-Ebene gemacht, nicht per SQL — bewusster Verzicht auf JOIN-Komfort zugunsten Trennbarkeit.

**Konsequenz für Datenmodell-Sektion (Thema 6):** Alle Tabellen leben in `pilzling_reviews_app`. Eigener DB-User mit Permissions nur auf diese DB.

**Im Pre-Check zu klären:** Welcher DB-User wurde angelegt, welche Permissions? Wo liegen die Credentials (vermutlich Bitwarden-Ordner "Webserver & Domain")?

---

### 2. Domain-Strategie ✅ ENTSCHIEDEN (2026-05-02)

**Entscheidung:** Variante A — zwei Subdomains (Branding-Naming, da extern sichtbar im Shop-HTML):
- **`sporeprint.pilzling.eu`** → Public-API, Widget-Asset-Auslieferung (`widget.js`). Kein `.htaccess`, dafür CORS/Referer-Whitelist + Rate-Limiting.
- **`admin-sporeprint.pilzling.eu`** → Admin-UI + schreibende Endpoints. Vollständig hinter cPanel-Verzeichnisschutz, dahinter App-Login.

Klare Trennung auf Apache-vhost-Ebene → einfache Härtung (`.htaccess` auf Admin-Subdomain schützt **alles** auf der Domain, kein Risiko vergessener Pfade). Saubere CORS-Konfiguration. Cookie-Isolation zwischen Public und Admin.

**Bewusst nicht entschieden:** dritte Subdomain `widget.pilzling.eu` für Asset-only. Falls Performance-Gründe später dazu zwingen, kann ohne großen Umbau ergänzt werden — vorerst liefert `reviews.pilzling.eu` sowohl Asset als auch Public-API aus.

**Konsequenz für später:**
- DNS-Einträge für beide Subdomains nötig (vermutlich auf Server Profis weitergeleitet, ggf. später via Cloudflare proxied — siehe Thema 5)
- Zwei vhost-Einträge in cPanel
- TLS-Zertifikate für beide (Let's Encrypt via cPanel sollte das automatisch lösen)

---

### 3. Sicherheits-Layer Admin (Pre-Auth) ✅ ENTSCHIEDEN (2026-05-02)

**Entscheidung:** **cPanel-Verzeichnisschutz (`.htaccess` HTTP Basic Auth)** für das Verzeichnis hinter `admin-reviews.pilzling.eu`. Genau das Pattern, das Alex ursprünglich für production-app aktiviert hatte.

**Funktionsweise:** Bei jedem Zugriff auf `admin-reviews.pilzling.eu` zeigt der Browser ein Popup für User+Passwort (HTTP Basic Auth). Erst nach Eingabe lädt überhaupt die Sporeprint-Login-Seite. **Layer 2 (App-Login) bleibt zusätzlich davor** — Defense in Depth, beide müssen kompromittiert werden.

**Begründung der Wahl:**

- Cloudflare Access (anfangs erwogen) braucht **vollständigen DNS-Umzug von `pilzling.eu` zu Cloudflare** — User hat entschieden die Domains bei Server Profis zu halten. Cloudflare Access damit ausgeschlossen.
- Selbst-gehostetes SSO (Authelia/Keycloak) bräuchte VPS daneben — Komplexität nicht gerechtfertigt für 3-5 interne User.
- `.htaccess` ist cPanel-nativ, sofort wirksam, in Bitwarden teilbar, keine zusätzliche Infrastruktur.

**Setup:**

1. cPanel → "Verzeichnisschutz" → Ordner `app.admin-reviews.pilzling.eu` (oder analog) → Schutz aktivieren
2. User + Passwort anlegen
3. Credentials in Bitwarden-Ordner "Webserver & Domain" ablegen

**Sub-Frage entschieden:** **Gemeinsames Passwort** (1 Bitwarden-Eintrag, alle relevanten User haben Zugriff). Begründung: Layer 1 ist Pre-Auth, echte User-Identität liegt im App-Login (Layer 2). Doppelte Pflege bringt keinen erkennbaren Nutzen bei 3-5 internen Usern.

**Wichtige Hygiene-Maßnahmen zusätzlich** (gelten unabhängig vom Verzeichnisschutz, für Sporeprint UND production-app):
- HTTPS-only Cookies (`Secure`, `HttpOnly`, `SameSite=Strict` für Admin)
- CSRF-Tokens auf allen POST-Endpoints
- Prepared Statements überall (kein SQL-Injection)
- Konsistente `requireLogin()`-Aufrufe auf allen API-Endpoints
- Keine Backup-Files (`.env.bak`, `.git/`, `*.old.php`) im public_html
- Login-Endpoint mit Rate-Limit / Lockout nach mehreren Fehlversuchen

**Hinweis production-app (2026-05-02):** Verzeichnisschutz war zwischenzeitlich deaktiviert — wurde am 2026-05-02 reaktiviert. Falls Webhooks oder externe Integrationen ohne Basic-Auth-Header dadurch brechen, einzelne Pfade gezielt whitelisten (z.B. `/api/webhook/jtl/*`).

**Sicherheits-Pattern wandert auf Workspace-Ebene:** Sobald wir es einmal sauber dokumentiert haben, gehört es als wiederverwendbares Pattern in `references/sicherheits-pattern.md` (oder ähnlich) — damit das nächste Dev-Projekt es direkt mitnimmt und production-app es nicht jedes Mal neu erfindet.

---

### 4. Sicherheits-Härtung Public-API (Widget) ✅ ENTSCHIEDEN (2026-05-02)

**Risiken die wir adressieren:**
- Widget-Daten werden auf fremden Seiten eingebettet (Bandbreiten-Diebstahl, fremde Marken nutzen unsere Reviews)
- Endpoint wird gescraped / als kostenlose Reviews-API missbraucht
- DDoS auf den Origin-Server
- Manipulierter `widget.js` durch Server-Kompromittierung

**Entschieden — alle 6 Layer aktiv von Anfang an:**

1. ✅ **CORS-Whitelist** — `Access-Control-Allow-Origin` dynamisch nach Shop-ID gesetzt (`pilzling.shop`, `pilzwald.de` (oder finale Domain), `shroom-boom.de`). Verhindert Browser-basiertes Embedden auf fremden Domains.

2. ✅ **Referer-Check im PHP-Endpoint** — `Referer:`-Header gegen Shop-Whitelist. Bei Mismatch: leerer Response oder 403. Nicht 100% (Referer fälschbar), aber filtert kombiniert mit CORS solide.

3. ✅ **Rate-Limiting per IP** — Token-Bucket in MariaDB-Tabelle, z.B. 60 Requests/Minute/IP. 99% der Shop-Besucher:innen brauchen <5 Requests pro Session, Scraper fliegen schnell raus. **IP wird nur im Backend für das Rate-Limit-Window verwendet, nicht ausgeliefert, nicht persistiert.** Rechtsgrundlage: Art. 6(1)(f) DSGVO (berechtigtes Interesse, Missbrauchsschutz). Wird einzeilig in Datenschutzerklärung der Shops erwähnt.

4. ✅ **Aggressive Cache-Header** — `Cache-Control: public, max-age=21600` (6h, gleicher Rhythmus wie Cron). Browser/Proxies cachen, Origin-Last reduziert. Funktioniert auch ohne Cloudflare davor.

5. ✅ **Subresource Integrity (SRI)** — `<script src="..." integrity="sha384-..." crossorigin="anonymous">` im JTL-Template. Schützt vor manipuliertem `widget.js` selbst bei Server-Kompromittierung. Pflege: bei jedem Widget-Update Hash regenerieren — manueller Schritt oder Build-Automation. Wird ab Phase "Widget finalisieren" aktiv, in der initialen Build-Phase noch ohne SRI (würde sonst täglich brechen).

6. ✅ **Keine sensiblen Daten** im Widget-Response. Nicht verhandelbar.

**Datenminimierung — was im Widget-Response steht und was nicht:**

| Feld | Drin? | Begründung |
|------|-------|------------|
| Sterne (1-5) | ✅ | nötig für Anzeige |
| Review-Text | ✅ | nötig für Anzeige |
| Vorname / Initialen des Bewerters | ✅ wenn so von Quelle gegeben | Google/Trustpilot zeigen das eh, ist public |
| Datum | ✅ — auf Tag genau | nicht auf Sekunde |
| Plattform-Name | ✅ | "von Google" etc. |
| Produktname (bei JTL-Reviews) | ✅ | nötig für Kontext |
| Bewerter-IP | ❌ | DSGVO + nicht verfügbar |
| Bewerter-Geolocation | ❌ | DSGVO + nicht verfügbar |
| Genaue Timestamp | ❌ | unnötig genau |
| Bewerter-E-Mail | ❌ | DSGVO, nicht verfügbar |

**Cookie-Politik des Widgets:**

Widget setzt **keine Cookies** und nutzt **kein LocalStorage**. Reine Read-only-Anzeige. Damit fällt das Widget unter Cookie-Banner-Pflicht im Shop **nicht** und ist immer sofort sichtbar. Falls später Funktionen kommen die Persistenz brauchen ("User hat schon mal bewertet, CTA verstecken"), wird das separat entschieden — vermutlich nicht nötig.

**DSGVO-Hinweis:** Bewerter-IP und -Geo werden **nicht** im Widget-Response ausgeliefert (auch nicht intern gespeichert) — die Quell-APIs (Google, Trustpilot) geben sie ohnehin nicht raus, also haben wir sie nicht. Bei JTL-Produktbewertungen wird beim Import explizit gefiltert falls dort technisch was anliegt.

---

### 5. Cloudflare davor — ja oder nein? ✅ ENTSCHIEDEN (2026-05-02)

**Entscheidung:** **Kein Cloudflare.** Domain bleibt vollständig bei Server Profis.

**Begründung:**

- DNS-Hoheit bleibt bei Server Profis (User-Entscheidung in Thema 3) → Cloudflare-Free-Plan-Features (Cache, DDoS, Access) funktionieren alle nicht ohne vollen DNS-Umzug
- **LSCache (LiteSpeed Cache) ist auf Server Profis verfügbar** und liefert server-seitiges Caching, das alle Inhalte mit `Cache-Control`-Header automatisch cached — funktional ersetzt das den Cloudflare-Edge-Cache für unseren Use-Case
- Server Profis hat Standard-DDoS-Schutz auf Netzwerkebene (Provider-typisch)
- Realistische Last (3 Pilzling-Shops, vermutlich <5000 Widget-Loads/Tag) macht globalen Edge-Cache nicht erforderlich

**Resultierender Schutz-Stack für Sporeprint:**

1. **App-Layer-Härtung** (siehe Thema 4) — CORS, Referer-Check, Rate-Limit, Cache-Header, SRI
2. **LSCache server-seitig** (automatisch über Cache-Control aktiviert, keine Extra-Konfiguration nötig)
3. **Server-Profis-Basis-DDoS-Schutz** (Netzwerkebene)

**Reserve-Optionen für später** (falls echte Performance-/Sicherheitsprobleme auftreten — nicht antizipiert):
- Server-Profis-Tarif-Upgrade (mehr Ressourcen, ggf. besserer DDoS-Schutz)
- Vollständiger DNS-Umzug zu Cloudflare → Domain-weite Entscheidung, würde dann auch production-app betreffen (= eigener Plan)
- Anderer Hoster mit dediziertem DDoS-Schutz

**Pre-Check-Aufgabe:** beim Server-Profis-Support kurz schriftlich bestätigen lassen was im Tarif "Webhosting Business L 5.1" an DDoS-Schutz und Bandbreiten-Limits enthalten ist — nicht blockierend für Architektur-Entscheidung, aber gut zu wissen.

**Hinweis:** Redis ist auf Server Profis verfügbar, aber nur als WordPress-LSCache-Backend (Admin-Config nötig). Für Sporeprint irrelevant — wir nutzen MariaDB für alle Daten (Thema 1).

---

### 6. Multi-Tenant-Datenmodell (SQL-Schema) ✅ ENTSCHIEDEN (2026-05-02)

**Entscheidungen:**

- **6 Tabellen**, alle in `pilzling_reviews_app`, durchgängig englisches Naming (`created_at`, `posted_at`, `is_visible` etc.):
  - `shops` — Stammdaten der 3 Shops + Multi-Tenant-Konfiguration (CI, API-Key-Referenzen, Place IDs)
  - `reviews` — polymorph über `source`-ENUM (`google`/`trustpilot`/`jtl`), Unique-Key gegen Duplikate
  - `review_replies` — 1:1 zu `reviews`, separate Tabelle weil optional, eigener `external_status`
  - `sync_runs` — Cron-Lauf-Protokoll (Debugging + Drift-Erkennung)
  - `widget_configs` — Pro-Shop-Widget-Settings (Layout, Filter, Custom-CSS)
  - `rate_limits` — Layer 3 aus Thema 4, IP-binär, Auto-Cleanup nach 1h via Cron
- **Brevo-Tabelle `review_requests` erst in Phase 4** anlegen (wenn Stufe 3 Brevo-Integration konkretisiert wird) — heute Schema raten lohnt nicht, Migrations-Pipeline ist eh da
- **Hard-Delete** bei aus Quellen gelöschten Reviews (DSGVO-sauber, Analytics-Inkonsistenz vernachlässigbar)
- **Visibility als ENUM** (`'visible'` / `'hidden'` / `'flagged'`) statt TINYINT(1) — erweiterbar (z.B. zukünftiger Status `'pending_review'`)

**Konsequenz:** Detailpläne in Phase 1 referenzieren das vollständige Schema (siehe Brainstorm-Skizze oben in der Original-Sektion 6 vor dieser Entscheidung).

**Frage:** Wie modellieren wir 3 Shops in MariaDB?

**Grundsatz:** Jede Tabelle hat eine `shop_id`-Spalte als FK. Kein Hardcoding von Shop-Trennung in Code-Helpern.

**Skizze (Stand Konzept, nicht final):**

```sql
-- Shop-Stammdaten (3 Zeilen: pilzling, pilzwald, shroom-boom)
shops (
  shop_id        VARCHAR(32) PRIMARY KEY,    -- "pilzling"
  name           VARCHAR(128),
  domain         VARCHAR(128),               -- "pilzling.shop"
  google_place_id   VARCHAR(64),
  trustpilot_unit_id VARCHAR(64),
  jtl_api_url    VARCHAR(255),
  ci_primary     VARCHAR(7),                 -- "#7a4f1a" Hex
  ci_secondary   VARCHAR(7),
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)

-- Reviews (alle Quellen, polymorph über source-Feld)
reviews (
  review_id      INT AUTO_INCREMENT PRIMARY KEY,
  shop_id        VARCHAR(32) NOT NULL,
  source         ENUM('google','trustpilot','jtl') NOT NULL,
  external_id    VARCHAR(128) NOT NULL,      -- Quelle-eigene ID, gegen Duplikate
  stars          TINYINT NOT NULL,
  author         VARCHAR(255),
  content        TEXT,
  language       VARCHAR(8),
  product_name   VARCHAR(255) NULL,          -- nur bei source=jtl
  product_sku    VARCHAR(64) NULL,
  posted_at      DATETIME NOT NULL,
  fetched_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  is_visible     TINYINT(1) DEFAULT 1,        -- für manuelle Filter im Admin
  UNIQUE KEY uniq_source (shop_id, source, external_id),
  INDEX idx_shop_source_date (shop_id, source, posted_at DESC),
  FOREIGN KEY (shop_id) REFERENCES shops(shop_id)
)

-- Antworten (1:1 zu reviews, eigene Tabelle weil optional)
review_replies (
  reply_id       INT AUTO_INCREMENT PRIMARY KEY,
  review_id      INT NOT NULL,
  content        TEXT,
  posted_at      DATETIME NOT NULL,
  posted_by      VARCHAR(64),                -- Admin-User-Name
  external_status ENUM('pending','sent','failed'),
  FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE
)

-- Sync-Log (Cron-Läufe protokollieren — Debugging + Drift-Erkennung)
sync_runs (
  run_id         INT AUTO_INCREMENT PRIMARY KEY,
  shop_id        VARCHAR(32) NOT NULL,
  source         ENUM('google','trustpilot','jtl') NOT NULL,
  started_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  finished_at    TIMESTAMP NULL,
  status         ENUM('running','ok','error'),
  reviews_new    INT DEFAULT 0,
  reviews_updated INT DEFAULT 0,
  error_message  TEXT NULL,
  INDEX idx_shop_started (shop_id, started_at DESC)
)

-- Widget-Konfiguration pro Shop (überschreibt Defaults)
widget_configs (
  shop_id        VARCHAR(32) PRIMARY KEY,
  layout         ENUM('carousel','feed') DEFAULT 'carousel',
  min_stars      TINYINT DEFAULT 4,
  max_items      SMALLINT DEFAULT 20,
  show_product_reviews TINYINT(1) DEFAULT 1,
  custom_css     TEXT NULL,
  FOREIGN KEY (shop_id) REFERENCES shops(shop_id)
)
```

**Bewusst nicht im Konzept** (siehe Sektion unten):
- User-Tabelle für Admin-Login → Layer 1 ist `.htaccess`, Layer 2 könnte vorerst auch nur ein einziges Admin-Passwort sein. User-Stamm wird später Thema falls mehrere Admins gleichzeitig schreiben

**Zu prüfen im Pre-Check:**
- Gibt es in production-app schon eine `entity_notes`- oder ähnliche generische Tabelle, die wir wiederverwenden können?
- Naming-Konvention von production-app: `created_at` oder `erstellt_am`? Konsistenz halten.
- Field-Definitionen-Helper (`fieldDef()` in production-app) — ist das wiederverwendbar?

---

### 7. Repo-Strategie ✅ ENTSCHIEDEN (2026-05-02)

**Entscheidung:** Eigenes Git-Repo **`pilzling-sporeprint-review-app`** (privat, GitHub).

**Begründung:**
- Sporeprint ist konzeptuell anderes Produkt als production-app (Marketing-Tool vs. Produktionsmanagement)
- WinSCP-Auto-Deploy je Repo trivial konfigurierbar (kein Multi-Target-Setup nötig)
- Wiederverwendbare Helper (.htaccess-Template, Auth-Pattern, DB-Connection-Boilerplate) wandern auf Workspace-Ebene in `references/php-patterns/` (oder ähnlich) — saubere SSOT für Cross-Cutting
- Spätere Entkopplung (z.B. Sporeprint als eigenständiges Produkt verkaufen) bleibt offen

**Konsequenz:**
- Phase-1-Schritt: Repo `pilzling-sporeprint-review-app` anlegen, `.gitignore` aus Projekt-Ordner committen, initialen Folder-Struktur-Commit
- Workspace-Pattern-Ordner (`C:\AI-Workspace\references\php-patterns\` o.ä.) wird angelegt sobald wir das erste wiederverwendbare Stück haben (.htaccess-Template, Sicherheits-Layer-Snippet) — kein Vorab-Aufwand

**Frage:** Sporeprint als eigenes Git-Repo oder als Modul/Subverzeichnis im production-app-Repo?

**Optionen:**

- **A) Eigenes Repo `pilzling-reviews`**
  - Pro: klare Trennung, kann später als Open-Source-Komponente offengelegt werden, eigene CI-Pipeline möglich, FTP-Deploy auf eigene Subdomain einfach konfigurierbar
  - Contra: zwei Repos zu pflegen, kein gemeinsamer PR für Cross-Cutting-Änderungen

- **B) Subverzeichnis `production-app/sternfaenger/`**
  - Pro: ein Repo, gemeinsame Git-Historie, gemeinsame Helper möglich
  - Contra: vermischt zwei Produkte, FTP-Deploy muss zwei Targets unterstützen, Sporeprint ist nicht "Production-App"

- **C) Monorepo mit klaren Subprojekten**
  - Übertrieben für 2-3 Projekte, aber sauberste Variante wenn das Workspace mal 5+ Projekte hat

**Tendenz:** A) eigenes Repo. Sporeprint ist konzeptuell ein anderes Produkt (Marketing-Tool für 3 Shops, nicht Produktionsmanagement). Wiederverwendbare Helper (z.B. `.htaccess`-Template, DB-Connection-Pattern, Auth-Boilerplate) wandern dann in `references/sicherheits-pattern.md` auf Workspace-Ebene oder in einen neuen Workspace-Ordner `references/php-patterns/`.

---

### 8. Deploy-Pipeline ✅ ENTSCHIEDEN (2026-05-02)

**Entscheidung:** **WinSCP-Auto-Deploy** (gleicher Workflow wie production-app), neuer WinSCP-Tab für die Sporeprint-Subdomain(s). **DB-Migrations manuell via phpMyAdmin** — nummerierte SQL-Files in `_db/` im Repo, händische Ausführung.

**Begründung:** Konsistenz mit production-app. Eingespielter Workflow, kein neues Tooling. Memory-Eintrag dazu existiert bereits ("WinSCP Auto-Deploy production-app — Code-Deploy passiert automatisch, keine Deploy-Hinweise nötig").

**Aufrüstungs-Pfad:** Wenn später mehrere Entwickler:innen oder Reliability-Probleme — Umstellung auf **GitHub Actions → FTP-Deploy** mit Action-Secret für FTP-Credentials. Eigener Plan dafür, aktuell nicht nötig.

**Pre-Check-Aufgabe:** Wie genau ist `_db/` in production-app organisiert? Konvention für Migration-Dateinamen, Reihenfolge, Roll-Forward-only oder reversibel? — übernehmen wir 1:1 für Sporeprint.

---

## Bewusst nicht im Konzept

Folgende Themen werden hier **bewusst ausgespart**, mit Grund:

- **User-Management mehrerer Admins** — Phase-3-Thema. Layer-1 (`.htaccess`) reicht initial für 3-5 interne User, App-Login kann vorerst Single-Admin sein.
- **DSGVO-Auftragsverarbeitungsverträge mit Google/Trustpilot** — sind eh schon vorhanden weil ihr die Plattformen produktiv nutzt. Sporeprint ist nur ein weiterer Konsument.
- **Mehrsprachigkeit** des Widgets — vorerst nur Deutsch. Englisch/andere Sprachen später, wenn Shops international gehen.
- **Webhook-Empfang** für Echtzeit-Updates (statt Cron alle 6h) — Trustpilot bietet Webhooks, Google nicht. Cron reicht für den Anfang, Webhooks ist Nice-to-have für später.
- **Migration der Bestandsdaten** aus onlinereviews.tech — das alte System exportiert vermutlich keine Reviews. Wir starten neu, alte Reviews werden über die Quell-APIs (Google, Trustpilot) eh wieder abgeholt.
- **Mobile-App für Admin** — Web-Admin reicht, ist ohnehin responsive zu bauen.
- **Brevo-Integration** — bleibt wie im ursprünglichen Plan. Ist eh ein separater Strang in Brevo, nicht Teil der Sporeprint-Codebase.

---

## Iterations-Log

- **2026-05-02 (Iteration 1)** — Konzept initial erstellt nach Architektur-Pivot-Entscheidung (Vercel → Server Profis + MariaDB) und Sicherheits-Diskussion mit User. 8 Brainstorming-Themen + Brevo-Sub-Thema, vorläufige Tendenzen pro Thema dokumentiert.
- **2026-05-02 (Iteration 2)** — Alle 8 Themen mit User durchgegangen, Entscheidungen festgehalten:
  - Thema 1: eigene DB `pilzling_reviews_app` (bereits angelegt)
  - Thema 2: zwei Subdomains `sporeprint.pilzling.eu` (public) + `admin-sporeprint.pilzling.eu` (admin) — nach Branding-Pivot von "reviews" zu "sporeprint"
  - Thema 3: cPanel-Verzeichnisschutz (`.htaccess` Basic Auth) als Pre-Auth + gemeinsames Passwort. Cloudflare Access verworfen weil DNS bei Server Profis bleibt
  - Thema 4: alle 6 App-Layer-Härtungen aktiv (CORS, Referer, Rate-Limit, Cache-Header, SRI, Datenminimierung). Keine IP/Geo der Bewerter:innen im Widget. Widget setzt keine Cookies
  - Thema 5: kein Cloudflare. LSCache (auf Server Profis verfügbar) + Server-Profis-Basis-DDoS reicht
  - Thema 6: 6 Tabellen englisch genamed, Brevo-Tabelle erst Phase 4, Hard-Delete bei gelöschten Reviews, Visibility als ENUM
  - Thema 7: eigenes Repo `pilzling-sporeprint-review-app`
  - Thema 8: WinSCP-Auto-Deploy + manuelle DB-Migrations via phpMyAdmin
  - **Branding-Pivot:** Projekt von "Sternfänger" zu "Sporeprint" umbenannt (Sternfänger = bereits genutzter Drittservice). Sporeprint extern, "reviews" intern technisch
  - **Sporen-Rating-Idee** als optionale UI-Spielerei in Phase "Widget finalisieren" aufgenommen
  - **Brevo-Integration** als 4-Stufen-Modell in Roadmap, konkret eingeplant bis Stufe 3
- **2026-05-02 (Iteration 3 — Pre-Check, Stufe 2)** — production-app systematisch durchgegangen, vier Listen erstellt:
  - **A) Pattern-Inventur:** 10 Patterns aus production-app übernehmbar (A1-A10), darunter `.env`-Loader/PDO-Singleton, `.htaccess`-Hardening, API-Envelope, Auth-Skelett (vereinfacht), Cron-Token, Migrations-Konvention, Umlaut-Pre-Commit. Bewusste Nicht-Übernahme: komplexes Permission-System, Modal-Framework, Bewegungstypen-Registry
  - **B) Drift-Punkte intern:** keine bestehenden Doppelungen. Zwei Risiken für Zukunft dokumentiert: Naming-Mismatch (deutsch/englisch) beim Copy-Paste + saubere Public-vs-Admin-Endpoint-Trennung in `lib/`
  - **C) Schema-Korrekturen:** 9 Punkte geprüft, 4 Korrekturen ins Schema (`review_replies` aufgesplittet auf `created_at`+`external_posted_at`, `rate_limits` als Sliding-Window mit `bucket_minute`, `visibility` mit DEFAULT, zwei Admin-Filter-Indexe), Public-API liefert pures Array statt Envelope
  - **D) Pattern-SSOT-Vorschlag:** 9 Patterns für `references/php-patterns/` identifiziert, Anlage in Sporeprint Phase 1 als Beifang (nicht Vorab-Aufgabe)
- **2026-05-02 — Konzept-Stufe 2 ✅ abgeschlossen** — Konzept reif für Detailplan-Erstellung (Stufe 3)

---

## Pre-Check (Stufe 2) ✅ ABGESCHLOSSEN am 2026-05-02

**Wichtig:** Sporeprint ist standalone (siehe "Verhältnis zu production-app" oben). Pre-Check liefert **Pattern-Inventur** (was kopieren wir lokal rüber) + **App-interne Drift-/Schema-Prüfung** + **Pattern-SSOT-Vorschlag**. Keine Cross-App-Code-Abhängigkeiten zur Laufzeit.

### A) Pattern-Inventur — Was kopieren wir aus production-app

| # | Pattern | Quelldatei in production-app | Anpassung für Sporeprint |
|---|---------|-------------------------------|--------------------------|
| A1 | **`.env`-Loader + PDO-Singleton** | `src/config/database.php` | DB-Name `pilzling_reviews_app`, sonst 1:1 |
| A2 | **`.htaccess`-Hardening** | `src/.htaccess` | 1:1 für `public/` und `admin/` (`.env` blocken, `Options -Indexes`, UTF-8) |
| A3 | **API-Response-Envelope** (`apiSuccess($data)` → `{ok:true,data:...}`, `apiError($msg)` → `{ok:false,error:...}`) | `src/includes/helpers.php` Zeilen 6-42 | 1:1 — etabliertes Pattern, Frontend kann es schon |
| A4 | **`jsonResponse()` mit JSON_INVALID_UTF8_SUBSTITUTE** | `src/includes/helpers.php` | 1:1 |
| A5 | **API-Endpoint-Skelett** (require_once auth+helpers+database, Method-Dispatch via `$_SERVER['REQUEST_METHOD']`, Input-Validation mit `apiError()` raus) | `src/api/notes.php` als Vorlage | 1:1 als Boilerplate |
| A6 | **Auth-Skelett** (`session_start()` beim Include, `login()`/`logout()`, `requireLogin()` mit API/HTML-Differenzierung, `isApiRequest()`) | `src/includes/auth.php` Zeilen 1-77 | **Vereinfacht:** Single-Admin-Login ohne User-Tabelle/Permission-System. `users`-Tabelle wird durch hardcoded Admin-Account in `.env` ersetzt (Phase 1) — User-Stamm erst Phase 3 falls nötig |
| A7 | **Cron-Token-Pattern** (`hash_equals`-basierte Validierung, `X-Cron-Token`-Header oder `?token=`) | `src/includes/auth.php` Zeilen 38-49 | übernehmen — auch wenn wir Cron primär per CLI starten, ist das Pattern für etwaige HTTP-Cron-Trigger nützlich |
| A8 | **DB-Migrations-Konvention** (`schema_vN.sql` fortlaufend, `USE <db>` als erste Zeile, nur ALTER+CREATE IF NOT EXISTS, keine DROPs, manuell via phpMyAdmin) | `_db/README.md` | 1:1 mit `USE pilzling_reviews_app;` |
| A9 | **`_tools/check_umlauts.py` + Pre-Commit-Hook** (Umlaute-Pflicht im Repo) | `_tools/check_umlauts.py` + `_tools/umlauts-patterns.txt` + `_tools/umlauts-allowlist.txt` | 1:1 — nur Allowlist ggf. anpassen |
| A10 | **WinSCP-Auto-Deploy-Setup** | (Workflow auf Christians Rechner, nicht im Repo) | gleiches Pattern, neuer WinSCP-Tab für `app.reviews/`-Folder |

**Was NICHT übernommen wird** (bewusst):

- `role_permissions` + `user_permissions` + Hardcoded-Admin-Bypass → überdimensioniert für Sporeprint v1
- Field-Definitions / Form-Schemas / Modal-Schemas (`config/field_definitions.php`, `config/form_schemas/`, `config/modal_schemas/`) → production-app-spezifisches Modal-Framework, Sporeprint braucht v1 simplere UI
- `bewegungstypen.php`-Registry, Inventur-Logik, JTL-CSV-Export-Pipeline → produktionsspezifisch
- Polymorphe `entity_notes` / `entity_attachments` / `entity_assignments` → für v1 nicht nötig (Sporeprint hat kein generisches Notiz/Attachment-Konzept)
- Komplexe Sync-Diff-Logik (`sync_diff.php`) → wir machen Upserts via UNIQUE KEY, kein diff-basierter Sync

### B) App-interne Drift-Prüfung Sporeprint

Sporeprint ist Greenfield — keine bestehenden Doppelungen erkennbar. **Risiko liegt in der Zukunft:**

- **B1 — Naming-Mismatch zu production-app beim Copy-Paste:** production-app nutzt **deutsche** Spalten- und Feldnamen (`erstellt_von`, `erstellt_am`, `aktiv`, `rolle`, `passwort_hash`). Sporeprint hat in Thema 6 **englische** Naming-Konvention beschlossen (`created_at`, `posted_at`, `is_active`). Beim Pattern-Kopieren von production-app müssen Identifier aktiv re-named werden — nicht nur copy-paste.
  - **Mitigation:** beim Kopieren systematisch durchgehen, in der `lib/`-Schicht nur englische Identifier verwenden, Build-Hook könnte deutsche Spaltennamen in Sporeprint-Code als Lint-Fehler flaggen (Phase 1+ optional)

- **B2 — Public-API-Endpoints vs. Admin-API-Endpoints sauber halten:** Beide DocRoots (`public/` und `admin/`) teilen `lib/`. Beim Bau muss klar bleiben: welche Endpoints sind unauthentifiziert (Public) und müssen die Härtungs-Layer aus Thema 4 (CORS, Referer, Rate-Limit) durchlaufen, welche sind hinter `requireLogin()`. **Konvention:** `lib/auth.php` bietet `requireLogin()` für admin, `lib/public_api_guard.php` bietet `enforcePublicApiHardening()` für public. Public-API-Endpoints rufen das als allererste Zeile, Admin-Endpoints rufen `requireLogin()` als allererste Zeile. Niemals beide oder keines.

### C) Schema-Korrekturen Sporeprint-intern

| # | Punkt | Status | Korrektur |
|---|-------|--------|-----------|
| C1 | `reviews.fetched_at` redundant zu `sync_runs`? | nein, beides nötig (`fetched_at` ist erstmaliges Sehen einer Review, `sync_runs` ist Cron-Lauf) | bleibt |
| C2 | Timestamp-Naming-Konsistenz | überwiegend ok (`<event>_at`-Schema), aber: `review_replies.posted_at` vermischt zwei Konzepte (App-Speicherzeitpunkt vs. extern-gepostet-Zeitpunkt) | **Refactor:** `review_replies.created_at` (in DB gespeichert) + `review_replies.external_posted_at` NULL bis erfolgreich an Google/Trustpilot gepostet. `external_status` zeigt den Workflow. |
| C3 | `rate_limits` Schema | aktueller Vorschlag mit `(ip_address, window_start)` PK ist Token-Bucket-Style, aber unscharf | **Refactor:** `rate_limits (ip_address VARBINARY(16), bucket_minute INT UNSIGNED, request_count INT, PRIMARY KEY (ip_address, bucket_minute))`. `bucket_minute = FLOOR(UNIX_TIMESTAMP()/60)`. Sliding-Window-Counter, einfacher zu queryen, Auto-Cleanup via `DELETE WHERE bucket_minute < FLOOR(UNIX_TIMESTAMP()/60) - 60`. |
| C4 | `widget_configs` separat oder JSON-Feld in `shops`? | separate Tabelle ist erweiterbarer (eigene Index-Strategie, einzelne Spalten-Updates) | bleibt separat |
| C5 | `shops.google_place_id` / `trustpilot_unit_id` / `jtl_api_url` als VARCHAR oder eigenständige `shop_credentials`-Tabelle? | für 3 Quellen × 3 Shops = 9 Felder ist 3 Spalten in `shops` ok. Bei 5+ Quellen lohnte Auslagerung | bleibt inline, dokumentiert "bei 5+ Quellen auslagern" als Trigger |
| C6 | `reviews.product_name` + `reviews.product_sku` (nur bei `source='jtl'`) inline oder Auslagerung? | inline ist einfacher, beide Felder sind klein, kein Update-Hotspot | bleibt inline |
| C7 | `visibility` ENUM Default? | aktuell ohne Default → würde NOT NULL ohne Default brechen | **Korrektur:** `visibility ENUM('visible','hidden','flagged') NOT NULL DEFAULT 'visible'` |
| C8 | Indexe für Admin-Filter | aktueller Plan hat nur Public-API-Index `(shop_id, source, posted_at DESC)`. Admin-Filter brauchen `(shop_id, stars)` und `(shop_id, visibility)` | **Hinzufügen:** zwei zusätzliche Indexe in Phase 1, oder in einer Phase-3-Migration falls Performance-Probleme |
| C9 | API-Envelope auch im Public-API-Endpoint? | production-app-Pattern ist `{ok, data}`. Aber: das Widget erwartet ein simples Reviews-Array — extra Envelope-Layer wäre Overhead | **Entscheidung:** Public-API liefert das pure JSON-Array (kein Envelope), Admin-API nutzt das `{ok, data}`-Envelope wie production-app. Doku-Hinweis in Phase-1-Plan dass das bewusst ist |

### D) Pattern-SSOT-Vorschlag — was nach `C:\AI-Workspace\references\php-patterns\`

Folgende Patterns sind **wiederverwendbar über mehrere Dev-Projekte** und sollten als Workspace-Pattern dokumentiert werden, damit beide Apps (production-app + Sporeprint) und künftige PHP-Projekte eine Single-Source haben:

| # | Pattern | Quelle | Form |
|---|---------|--------|------|
| D1 | **`.htaccess`-Hardening-Template** (`.env`-Block, `Options -Indexes`, UTF-8) | production-app `src/.htaccess` | als Snippet-Datei + Erklärung |
| D2 | **`loadEnv()` + `getDb()`-PDO-Singleton-Boilerplate** | production-app `src/config/database.php` | als Snippet-Datei mit `<DB_NAME>`-Platzhalter |
| D3 | **API-Envelope-Convention** (`apiSuccess`/`apiError`, `{ok, data}`-Format) | production-app `src/includes/helpers.php` | als Snippet + Convention-Doku, damit beide Apps das frontend-konsistent halten |
| D4 | **Auth-Boilerplate** (`session_start()`, `requireLogin()` mit API/HTML-Differenzierung, `isApiRequest()`) | production-app `src/includes/auth.php` | als Snippet ohne Permission-System (Apps ergänzen das selbst falls nötig) |
| D5 | **Cron-Token-Pattern** (`hash_equals`-basierte Validierung) | production-app `src/includes/auth.php` Zeilen 38-49 | als Snippet |
| D6 | **DB-Migrations-Konvention** (Naming, README-Format, Pflicht-Checkliste) | production-app `_db/README.md` | als Konvention-Doku, Apps bringen eigene Migrations-Files mit |
| D7 | **Public-API-Härtungs-Snippets** (CORS-Whitelist-Helper, Referer-Check, Rate-Limit-Sliding-Window) | **Sporeprint baut das zuerst** (production-app hat keine public API) | wandert nach Phase 1 von Sporeprint nach `php-patterns/` als Erstwerk |
| D8 | **Sicherheits-Hygiene-Checkliste** (HTTPS-Cookies `Secure`/`HttpOnly`/`SameSite`, CSRF-Tokens auf POSTs, Prepared Statements, `requireLogin()` überall, keine Backup-Files in DocRoot, Verzeichnisschutz auf Admin) | Synthese aus Konzept Thema 3+4 + production-app-Praxis | als Markdown-Doku, kein Code |
| D9 | **Umlaut-Pflicht-Pre-Commit-Hook** (`check_umlauts.py` + Patterns + Allowlist) | production-app `_tools/` | als Tool-Snippet |

**Vorgeschlagene Workspace-Struktur:**

```
C:\AI-Workspace\references\php-patterns\
├── README.md                          ← Übersicht + Anwendungsregel "kopiere und passe an"
├── htaccess-hardening.template
├── env-loader-and-pdo-singleton.php
├── api-envelope-convention.md         ← Doku, Snippet, Begründung
├── auth-boilerplate.php
├── cron-token-validation.php
├── db-migrations-convention.md
├── public-api-hardening/
│   ├── cors-whitelist.php
│   ├── referer-check.php
│   └── rate-limit-sliding-window.php
├── security-hygiene-checklist.md
└── umlaut-precommit/
    ├── check_umlauts.py
    ├── umlauts-patterns.txt
    └── umlauts-allowlist.txt
```

**Wann das angelegt wird:** Nicht jetzt vorab. **Sporeprint Phase 1** baut das Public-API-Hardening (D7) zuerst — sobald das stabil ist, wird der Pattern-Ordner angelegt UND alle anderen Patterns (D1-D6, D8, D9) dabei aus production-app extrahiert. Pragmatisch: ein einziger Migrations-Schritt statt vorab-Vorrat.

---

**Resultat des Pre-Checks für den Detailplan (Stufe 3):**

- **Schema-Korrekturen** (C2, C3, C7, C8, C9) müssen ins Schema vor Phase-1-Implementation
- **Pattern-Inventur** (A1-A10) liefert konkrete Datei-Verweise für Phase-1-Boilerplate
- **Drift-Mitigation B1+B2** wird als Konvention in CLAUDE.md / Phase-1-Plan dokumentiert
- **Pattern-SSOT (D)** ist eigener Sub-Plan in Phase 1 ("Workspace-Patterns extrahieren") — nicht Vorab-Aufgabe

**✅ Konzept-Stufe 2 abgeschlossen am 2026-05-02. Konzept ist reif für Detailplan (Stufe 3).**

---

## Akzeptanzkriterien für "Konzept stabil"

Konzept gilt als reif für Pre-Check wenn:

- [x] User hat Tendenz pro Thema 1-8 bestätigt oder geändert
- [x] DB-Strategie entschieden (Thema 1)
- [x] Domain-Strategie entschieden (Thema 2)
- [x] Cloudflare-Frage entschieden (Thema 5)
- [x] Repo-Strategie entschieden (Thema 7)
- [x] Multi-Tenant-Datenmodell-Skizze (Thema 6) hat keine offenen "?" mehr
- [x] Sicherheits-Layer (Thema 3+4) vom User abgenickt
- [x] "Bewusst nicht"-Liste vollständig (kein Punkt fehlt der später überraschend doch dazukommt)
- [x] Branding-Naming entschieden (Sporeprint extern / reviews intern)

**✅ Konzept-Stufe 1 abgeschlossen am 2026-05-02.**

**Nächster Schritt:** Pre-Check (Stufe 2) ausführen, Output in die Pre-Check-Sektion oben einarbeiten, danach Detailplan erstellen.
