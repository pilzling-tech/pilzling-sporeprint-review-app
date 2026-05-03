# Pilzling Sporeprint — Go-Live-Plan

**Erstellt:** 2026-05-04 ~04:15
**Stufe:** 3 (Master-Plan, Implementation parallel)
**Roadmap-Bezug:** [ROADMAP.md](ROADMAP.md) — Phase 2 + Phase 3 zusammengefasst zum Pilzling-Go-Live
**Konzept-Vorlauf:** [`2026-05-03-widget-refactor-konzept.md`](2026-05-03-widget-refactor-konzept.md) (Widget-Refactor) + Sternfänger-Backend-Material in `references/Orginal Structure/`

## Vision

**In wenigen Tagen** soll der Pilzling-Shop das Sporeprint-Widget produktiv nutzen können. Nicht nur das Widget selbst — die ganze Backend-Verwaltung soll stehen, sodass beim Eintreffen der API-Freigaben (Google + Trustpilot) sofort alles ineinander greift.

Sternfänger-Material gibt das Vorbild — Sporeprint übernimmt den **funktionalen Umfang** (5 Widget-Typen, Analytics, Reviews-Liste, Reply, QR-Generator, Bewertungslink-Landing-Page, Settings) aber mit **eigenem Spin** (Sporen-Rating, Pilzling-CI, klarere Card-Strukturen).

## Reverse-Engineering aus Sternfänger-Backend (15 Screenshots durchgesehen)

### Backend-Navigation (Sidebar)

```
[Pilzling Logo]
Pilzling

⭐ Bewertungen          (= Reviews-Liste mit Filtern + Reply)
📨 Bewertungen anfragen (= SMS/E-Mail/WhatsApp/QR — bei uns nur Mail+QR)
🔗 Bewertungslink       (= Landing-Page-Konfigurator + URL)
📊 Analytics
🔄 Automatisieren       (= Mail-Templates + Trigger — Phase 4 Brevo)
🟫 Widgets              (= Widget-Konfigurator mit 5 Typ-Tabs)
⚙ Einstellungen
   ↳ Standorte
   ↳ Integrationen
   ↳ Nutzer
   ↳ Benachrichtigungen
   ↳ Datenschutz
   ↳ Konto
🔁 Social               (= Reviews als Insta/FB-Post — skip nice-to-have)

— Bottom: Plattform-Stats —
[G] 4.8 ▓▓▓▓░░  50
[T] 4.5 ▓▓▓░░░  13
```

### Widget-Typen (5)

1. **Karussell** — horizontales Scroll-Karussell mit Pfeil-Nav (Hauptvariante)
2. **Feed** — vertikale Liste, volle Card-Breite, "Mehr Bewertungen anzeigen"-Button
3. **Video** — Video-Reviews — **skip in Sporeprint v1** (haben wir nicht)
4. **Pop-up** — kleines Pop-up unten links das eine Review zeigt (nicht beim Page-Load aufdringlich, dezent)
5. **Symbol** — Sticky-Element rechts mit Sterne-Score, klappt auf bei Hover

### Widget-Konfig-Optionen (allgemein)

- "Powered by"-Toggle (Sporeprint-Footer ein/aus)
- Dunkler Modus
- Ergebnisse ausblenden (Aggregat verstecken)
- Daten der Rezensenten ausblenden (Avatar/Name weg)
- "Rezension hinterlassen" CTA ausblenden
- Nur 4-5 Sterne anzeigen (Filter)
- Plattform-Toggles: Feedback / Google / Trustpilot
- Skript-des-Widgets-Anzeige (kopierbarer Embed-Code)

### Reviews-Liste (Hauptbildschirm)

Pro Review-Zeile:
- Avatar (rund, links)
- Name + Datum
- Standort/Plattform-Indikator rechts oben (📍 Pilzling)
- Sterne darunter
- Review-Text
- "Antworten"-Button (gelber Pill)
- 4 Aktions-Icons rechts unten: ✓ genehmigen, 👁 sichtbar/versteckt, 📷 (Bild/Profil-Verifikation?), 🗑 löschen

Filter rechts:
- Toggle "Alle Aktivitäten"
- Suche
- Bewertungs-Sentiment (👍/👎)
- Typ-Dropdown (Plattform)
- Antworten-Dropdown (alle/beantwortet/offen)
- Datum-Dropdown (alle/heute/letzte Woche/etc.)

### Analytics-Dashboard

- Filter rechts oben: "Letzte 7 Tage" Dropdown
- "Neue öffentliche Bewertungen" — Counter mit Stern-Icon
- "Anzahl neuer öffentlicher Bewertungen" — Bar-Chart pro Tag
- Bewertungs-Funnel (vertikale Liste, farbig):
  - 🟠 Einladungen gesendet
  - 🔵 Besuche insgesamt
  - 🔵 QR-Code-Besuche
  - 🟢 Neue öffentliche Bewertungen
  - 🔴 Negative Rückmeldungen
- "Bewertungswachstum" Line-Chart (grün)
- "Durchschnittliches Rating-Wachstum" Line-Chart (gelb)

### Bewertungslink-Konfigurator

- URL-Slug bearbeiten (`feedback.pilzling.eu/pilzling`)
- Title-Tag der Landing-Page bearbeiten
- "Startseite der Zielseite" — Plattformwahl-Dropdown (Standardvariante)
- Live-Vorschau rechts (mit Logo, Begrüßungstext, Plattform-Buttons)

### QR-Code-Tab

- Bewertungslink (kopierbar)
- Großer QR-Code generiert
- Download-Button "QR-Code herunterladen"

### Bewertungs-Landing-Page (Public)

`feedback.sternfaenger.eu/pilzling` (im Original) — bei uns:
- `feedback.pilzling.eu/<shop-id>` (eigene Subdomain) ODER
- `sporeprint.pilzling.eu/feedback?shop=<shop-id>` (Sub-Pfad)

Inhalt:
- Logo
- Begrüßungstext ("Hey! Wenn dir gefällt was wir machen — lass uns gerne eine Bewertung da. Das hilft uns wirklich weiter! Wähl einfach aus, wo du uns bewerten möchtest.")
- Plattform-Buttons: Google, Trustpilot, ggf. JTL-Produktseite
- Footer: "Zur Verfügung gestellt von Sporeprint"

### Settings → Benachrichtigungen

- E-Mail für Benachrichtigungen (täglich neue Reviews)
- E-Mail für Antworten (Forwarding-Adresse für ankommende Antworten)

## Sporeprint-Eigene Anpassungen (kein 1:1-Klon)

- **Sporen-Rating-Visual** statt klassischer Sterne (User-Wunsch — bleibt)
- **Pilzling-CI 1:1** statt Sternfänger-Bronze (Cream + Dark + Orange + Blau-Dark)
- **Plattform-Aggregat oben** anders gestaltet — User mag Sternfänger-Variante nicht 100%. Vorschlag: subtilerer Stat-Block, kleinere Icons, integriert in Header statt freischwebend
- **Karten einheitlicher** — JTL-Produktbewertungen mit Produkt-Chip unten links statt prominent oben
- **Hub-Tile-Layout** im Admin (flacher Hub statt Sub-Dashboard)
- **Modernes Pilzling-Design** — Rubik-Font, gefüllte CTAs in Akzent-Farbe statt Outline

## Master-Phasen (Reihenfolge der Implementation)

| # | Phase | Was | Wann | Abh. |
|---|-------|-----|------|------|
| **A** | Schema-Migration v2 | `widget_configs.theme_overrides JSON`, `shops.feedback_url`, `shops.contact_email`, neue Tabelle `notification_emails` | sofort | – |
| **B** | Widget-Komplett-Refactor | Original-Prototyp-Features (Aggregat, Avatar, Datum, Plattform-Icon, Pfeil-Nav, CTA, Footer) + Sporeprint-Spin | sofort | A |
| **C** | Reviews-Liste-Page | `admin/reviews.php` mit Filter + Inline-Actions + Mock-Reviews | sofort | – |
| **D** | Reply-Funktion | `admin/replies.php` + Reply-Form-Modal in Reviews-Page + `admin/api/reply.php`-Endpoint | sofort | C |
| **E** | Analytics-Page | `admin/analytics.php` mit Charts + Funnel (Mock-Daten erstmal) | sofort | – |
| **F** | Widget-Konfigurator | `admin/widget-config.php` mit Live-Preview + alle Konfig-Optionen + Theme-Override-Picker | sofort | B |
| **G** | QR-Code + Bewertungslink | `admin/qr.php` mit QR-Generator (PHP-Lib) + Bewertungslink-Konfigurator | sofort | – |
| **H** | Shop-Switcher | `admin/shops.php` mit Multi-Tenant-Wahl, Stamm-Daten-Edit | sofort | – |
| **I** | Settings | `admin/settings.php` — Benachrichtigungs-E-Mails, Datenschutz-Page-Link, Konto-Info | sofort | – |
| **J** | Public Bewertungs-Landing-Page | `public/feedback.php?shop=<id>` mit Plattform-Wahl + Sporeprint-Branding | sofort | – |
| **K** | API-Anbindung (Google + Trustpilot) | `lib/api_clients/google.php` + `trustpilot.php` + Cron-Skripte | wartet | API-Freigaben |
| **L** | Brevo-Integration Stufe 1-3 | E-Mail-Templates + Trigger + Funnel-Tracking | wartet | API-Freigaben + Brevo-Setup |

**A bis J = heute Nacht** (in dieser Reihenfolge, Commit pro Phase)
**K + L = Folge-Pläne** sobald APIs freigegeben

## Was in jeder Phase entsteht

### Phase A — Schema-Migration v2

`_db/schema_v2.sql`:

```sql
USE pilzling_reviews_app;

-- Widget-Theme-Overrides (siehe ARCHITEKTUR.md "Widget-Theming-Strategie")
ALTER TABLE widget_configs
  ADD COLUMN IF NOT EXISTS theme_overrides JSON NULL;

-- Bewertungs-Landing-Page-Konfiguration pro Shop
ALTER TABLE shops
  ADD COLUMN IF NOT EXISTS feedback_url_slug VARCHAR(64) NULL,
  ADD COLUMN IF NOT EXISTS feedback_landing_title VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS feedback_landing_text TEXT NULL,
  ADD COLUMN IF NOT EXISTS contact_email VARCHAR(128) NULL;

-- Initial-Default-Werte fuer feedback_url_slug
UPDATE shops SET feedback_url_slug = shop_id WHERE feedback_url_slug IS NULL;

-- Benachrichtigungs-E-Mails pro Shop (mehrere Empfaenger moeglich)
CREATE TABLE IF NOT EXISTS notification_emails (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  shop_id         VARCHAR(32)  NOT NULL,
  email           VARCHAR(128) NOT NULL,
  notify_new_review TINYINT(1) NOT NULL DEFAULT 1,
  notify_reply_received TINYINT(1) NOT NULL DEFAULT 1,
  is_active       TINYINT(1)   NOT NULL DEFAULT 1,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_shop FOREIGN KEY (shop_id) REFERENCES shops(shop_id),
  UNIQUE KEY uniq_shop_email (shop_id, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

User spielt das via phpMyAdmin ein.

### Phase B — Widget-Refactor

`src/public/widget.js` komplett überarbeitet:

- **Plattform-Aggregat oben** — kompakter Stat-Block mit Mini-Logos (G, Trustpilot-Stern) + Score + Star-Icon. Statt freischwebend wie im Sternfänger: integriert in Widget-Header als zentrierte Zeile.
- **Total-Count** "63 Bewertungen" zentriert darunter
- **Avatar/Profile-Pic** in Card oben links (Default-Pic wenn keiner — generischer User-Icon)
- **Plattform-Icon** in Card oben rechts (Google G, Trustpilot Stern, JTL für Produkt)
- **Datum** unter Name (deutsch formatiert: "17. Dezember 2025")
- **Pfeil-Nav** Prev/Next-Buttons als runde Buttons (44x44) mit SVG-Chevrons
- **Sporen-Rating** (bleibt, Sporeprint-eigen)
- **Produkt-Chip** unten links bei JTL-Reviews (statt prominenter Top-Border)
- **CTA "Bewertung schreiben"** als Pill-Button unten zentriert, führt auf `feedback_url`
- **Footer** mit Sporen-Icon + "powered by Sporeprint" + Tooltip
- **Mock-Aggregat-Daten** für Phase 2a (echte Werte kommen aus API in Phase K)

### Phase C — Reviews-Liste-Page (`admin/reviews.php`)

Layout:
- Standard-Page-Skelett (App-Header, App-Main)
- Page-Header: H1 "Reviews" + Page-Header-Actions (z.B. "CSV-Export"-Button — vorerst Stub)
- Linke Spalte (8/12): Reviews-Liste als Cards
- Rechte Spalte (4/12): Filter-Sidebar
- Filter:
  - Suche-Input
  - Plattform-Multi-Select (Checkbox-Liste: Google, Trustpilot, JTL)
  - Sterne-Filter (1-5 Star Buttons als Toggle)
  - Datum-Range (von/bis Date-Inputs)
  - Visibility-Filter (visible/hidden/flagged)
  - Antwort-Status (alle/beantwortet/offen)
- Pro Review-Card:
  - Avatar + Name + Datum oben
  - Plattform-Chip rechts oben
  - Sporen-Rating
  - Review-Text
  - Inline-Actions:
    - "Antworten"-Button (öffnet Reply-Modal aus Phase D)
    - Visibility-Toggle (👁 sichtbar / 👁‍🗨 versteckt)
    - "Flaggen"-Toggle (🚩)
    - Löschen-Button (mit Confirm-Dialog)
  - Wenn Reply existiert: Reply unten in Card mit Push-Status-Chip
- Mock-Reviews aus DB lesen (Schema v1, alle 6 Tabellen sind ja schon da)

### Phase D — Reply-Funktion

- **Reply-Modal** auf Reviews-Page (Click "Antworten"):
  - Textarea für Antwort-Text
  - "Speichern (lokal)" — speichert in DB, `external_status='pending'`
  - "Speichern + Senden" — wartet auf API-Anbindung Phase K, vorerst nur Lokal-Speichern verfügbar
- **`admin/replies.php`** — Antworten-Verwaltung (alle Replies-Liste, Push-Status, manueller Retry-Button)
- **`admin/api/reply.php`** — POST-Endpoint mit CSRF-Check, schreibt in `review_replies`
- Reply-Status-Chip (orange=pending, green=sent, red=failed)

### Phase E — Analytics-Page (`admin/analytics.php`)

- Filter-Dropdown oben rechts: Letzte 7/30/90 Tage / dieses Jahr
- Counter-Card oben "Neue Reviews letzte X Tage" mit Sporen-Icon
- Bar-Chart: Reviews pro Tag (vanilla CSS, kein Charting-Lib jetzt — einfache divs als Balken mit Höhe-Calc)
- Bewertungs-Funnel als 5 farbige Balken untereinander mit Counter-Werten
- Line-Chart: Durchschnitts-Rating-Verlauf (vanilla SVG-Path, basierend auf `sync_runs`/`reviews`-Aggregat)
- Mock-Daten für jetzt — echte Werte sobald Reviews da sind

### Phase F — Widget-Konfigurator (`admin/widget-config.php`)

- 5 Tabs oben: Karussell / Feed / Pop-up / Symbol (Video skip)
- Pro Tab:
  - Live-Preview rechts (Iframe oder direkt embedded Widget mit aktuellen Settings)
  - Konfig-Form links:
    - Filter (min_stars, max_items, show_product_reviews)
    - Theme-Overrides (Color-Picker für accent, accent_soft, background, card_background)
    - "Auf Default zurücksetzen"-Button pro Override
    - Plattform-Toggles (Google/Trustpilot/JTL)
    - Powered-by-Toggle
  - Embed-Code-Block unten ("Skript des Widgets") mit Copy-Button
- POST-Endpoint `admin/api/widget-config.php` schreibt in `widget_configs`

### Phase G — QR-Code + Bewertungslink (`admin/qr.php`)

- 2-Spalten-Layout
- Linke Spalte: Bewertungslink-Konfigurator
  - URL-Slug-Input (ergibt `https://sporeprint.pilzling.eu/feedback?shop=<slug>` — für jetzt Sub-Pfad statt eigene Subdomain)
  - Title-Tag-Input
  - Begrüßungstext-Textarea
  - "Speichern"-Button → schreibt in `shops.feedback_url_slug` etc.
- Rechte Spalte:
  - Großer QR-Code (mit `phpqrcode`-Library oder simple inline-Generation via Google-Charts-API als Fallback)
  - "Download als PNG"-Button
- Live-Preview-Link unten: "Bewertungs-Landing-Page öffnen"

### Phase H — Shop-Switcher (`admin/shops.php`)

- Liste der 3 Shops als Cards (oder data-table)
- Pro Shop:
  - Name + Domain
  - CI-Farben (Color-Swatches)
  - Google Place ID, Trustpilot Business Unit ID
  - Anzahl Reviews
  - Edit-Link → öffnet Edit-Form für Shop-Stamm-Daten
- Edit-Form: Stamm-Daten + API-Credentials (gemaskt anzeigen)

Plus: Shop-Switcher-Pattern in App-Header — kleine Pille rechts neben dem User-Bereich, zeigt aktuellen Shop, Click → Dropdown mit Shop-Wechsel. Beeinflusst alle Pages über Session-State `$_SESSION['active_shop']`.

### Phase I — Settings (`admin/settings.php`)

- Tab-Layout (analog Sternfänger Settings-Sub-Pages):
  - **Benachrichtigungen** — Liste der `notification_emails`-Einträge, Hinzufügen/Entfernen/Toggles
  - **Integrationen** — API-Status für Google + Trustpilot (verbunden / nicht verbunden), OAuth-Connect-Button
  - **Konto** — Eingeloggter Admin, Logout-Button, Passwort-Hash neu setzen (Hash-Generator ähnlich wie das einmalige Setup-Tool)

### Phase J — Public Bewertungs-Landing-Page (`public/feedback.php`)

- URL: `https://sporeprint.pilzling.eu/feedback?shop=<shop-id>`
- Liest `shops.feedback_landing_*`-Felder
- Layout:
  - Pilzling-Logo + Brand
  - Begrüßungstext (aus `feedback_landing_text`)
  - 2-3 Plattform-Buttons (Google, Trustpilot, optional JTL-Produktseite)
  - Footer: "Sporeprint" mit Tooltip
- Smart-Redirect: Click auf Plattform-Button → leitet weiter zu Google-Review-URL bzw. Trustpilot-Review-URL des Shops

## Technische Konventionen (gelten für alle Phasen)

Aus CLAUDE.md "Harte Regeln" — keine Verletzungen:

- **Naming durchgängig English** in Code-Identifiern (PHP-Vars, JS-Funktionen, DB-Spalten)
- **Umlaute überall** in UI-Strings, Code-Kommentaren, Doku
- **SSOT-Code** — nur über `lib/`-Helper (db, helpers, auth, public_api_guard)
- **SSOT-DB** — keine redundanten Spalten, FK-Verweise statt Duplikate
- **SSOT-UI** — keine Inline-Styles, keine page-spezifische CSS, alle Komponenten in `components.css`
- **Endpoint-Trennung** — Admin: `requireLogin();`, Public: `enforcePublicApiHardening($shopId);`
- **CSRF-Tokens** auf allen POST-Endpoints
- **Format-Helper** (`formatDate`, `humanTimeDiff`) statt `date()` direkt
- **Reference-Implementation** aus DESIGN-SYSTEM.md Sektion 12 als Page-Skelett-Vorlage

## Was bewusst NICHT in diesem Master-Plan

- **Live-Embedding im JTL-Shop** mit SRI-Hash + Production-Test (kommt nach API-Anbindung)
- **Brevo-Integration** (eigener Phase-4-Plan)
- **Social-Sharing** (Insta/FB-Post-Generator)
- **Video-Reviews-Widget-Typ** (haben wir nicht)
- **Multi-User-Admin** (Phase 4+)
- **Audit-Log** wer wann was geändert hat (Phase 4+)
- **JTL-Reviews via direkt-SQL** (eigener Plan, wartet)
- **Cloudflare-Setup** (verworfen)

## Akzeptanzkriterien für "Pilzling-Go-Live-Bereit"

- [ ] Schema v2 eingespielt (User-Schritt nach Code-Commit)
- [ ] Widget rendert mit allen Standard-Features (Aggregat, Avatar, Datum, Plattform-Icon, Pfeil-Nav, CTA, Footer)
- [ ] Pilzling-Shop kann das Widget mit `<script src="https://sporeprint.pilzling.eu/widget.js" data-shop="pilzling">` einbinden — und sieht (mit Mock-Daten) das finale Look
- [ ] Admin-Reviews-Liste funktioniert mit Filter (auf Mock-Reviews, sobald API-Daten da sind dann echt)
- [ ] Reply lokal speichern funktioniert (Push an API kommt mit API-Anbindung)
- [ ] Analytics-Page zeigt Charts + Funnel (Mock-Daten)
- [ ] Widget-Konfigurator-UI lebt mit Live-Preview
- [ ] QR-Code für Bewertungslink generierbar + downloadbar
- [ ] Bewertungs-Landing-Page funktioniert + verlinkt auf Google/Trustpilot
- [ ] Hub-Tiles auf Dashboard führen alle auf existierende Pages (kein 404 mehr)

## Reihenfolge der Commits

Jede Phase ein Commit. Sequenz für die Nacht:

1. `feat(db): schema v2 — theme_overrides + feedback-fields + notification_emails`
2. `feat(widget): Komplett-Refactor mit Original-Prototyp-Features + Sporeprint-Spin`
3. `feat(admin): reviews.php — Reviews-Liste mit Filtern + Inline-Actions`
4. `feat(admin): replies.php + Reply-Modal + reply-API`
5. `feat(admin): analytics.php mit Charts + Funnel (Mock)`
6. `feat(admin): widget-config.php mit Live-Preview + Theme-Picker`
7. `feat(admin): qr.php — Bewertungslink-Konfigurator + QR-Generator`
8. `feat(admin): shops.php — Shop-Switcher + Stamm-Daten-Edit`
9. `feat(admin): settings.php — Benachrichtigungen + Integrationen + Konto`
10. `feat(public): feedback.php — Bewertungs-Landing-Page`
11. Plus Roadmap + ARCHITEKTUR.md + Memory-Updates

## Stand der Nacht (gepflegt während des Builds)

- **04:15** Plan-Anlage komplett, Backend-Screenshots inventarisiert (15 Stück durchgesehen)
- **04:35** Phase A: schema_v2.sql + Migrations-README-Update committed
- **04:50** Phase B: widget.js Komplett-Refactor (586 Zeilen, 11 Features) committed
- **05:05** Phase C: reviews.php mit Filter-Sidebar + 3 Inline-Actions committed
- **05:15** Phase D: replies.php (Editor + Liste) + Reply-Submit committed
- **05:30** Phase E: analytics.php mit Counter-Cards + Bar-Chart + Funnel + Line-Chart committed
- **05:45** Phase F: widget-config.php mit Live-Preview-Iframe + Theme-Override-Picker committed
- **05:55** Phase G: qr.php — Bewertungslink + QR-Code via QuickChart.io committed
- **06:10** Phase H: shops.php — Multi-Tenant-Switch + Stammdaten-Edit committed
- **06:25** Phase I: settings.php — 3-Tab-Konfig (Notifications/Integrationen/Konto) committed
- **06:40** Phase J: public/feedback.php — Bewertungs-Landing-Page committed
- **06:50** Dashboard-Update: alle Hub-Tile-Links funktional, Phase-3-Hinweis-Callout entfernt, Top-Nav um alle Pages erweitert
- **07:00** Doku-Updates (ARCHITEKTUR, ROADMAP, CLAUDE.md, MEMORY.md) committed

**Status:** Phase A-J komplett. Phase K (API-Anbindung) wartet auf Google + Trustpilot Freigaben.

## Referenzen

- Konzept Widget-Refactor: `_plans/2026-05-03-widget-refactor-konzept.md`
- Original-Prototyp: `references/widget_prototype.html`
- Sternfänger-Backend-Screenshots: `references/Orginal Structure/Backend_Features/`
- Live-Look: `references/Orginal Structure/Styling Screenshots/`
- Embed-Skript-Original: `references/Orginal Structure/widget/widget_skript`
- Architektur: `docs/ARCHITEKTUR.md`
- Design-System: `docs/DESIGN-SYSTEM.md`
- CLAUDE.md "Harte Regeln" — alle Konventionen
