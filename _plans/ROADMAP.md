# ROADMAP — Sporeprint Review-Tool

**Letzte Aktualisierung:** 2026-05-02

Roter Faden über alle Vorhaben im Sporeprint-Projekt. Pläne werden als Feature-Pläne unter `_plans/YYYY-MM-DD-<name>.md` detailliert (komplexe Features mit vorgeschaltetem `-konzept.md` nach 3-Stufen-Methodik) und nach Abschluss in `_archive/_plans/` verschoben. Dieses Dokument bleibt stehen und zeigt den Gesamt-Stand.

**Naming:** Branding extern = "Sporeprint", intern technisch = "reviews" (DB `pilzling_reviews_app`, Tabellen `reviews`/`review_replies`).

## Aktueller Fokus

→ **2026-05-03:** **Phase 1 Backend-Foundation + Design-System v2 + CI-Refactoring Code-seitig abgeschlossen.** Aktive Pläne: [`2026-05-03-phase-1-backend-foundation.md`](2026-05-03-phase-1-backend-foundation.md), [`2026-05-03-ci-refactoring.md`](2026-05-03-ci-refactoring.md). Implementiert: lib-Foundation, Admin-Login + CSRF, Public-API mit 6-Layer-Härtung, Dashboard-Stub, Widget-Skelett, Workspace-Pattern-SSOT. Plus komplettes Design-System mit Pilzling-CI (Rubik-Font self-hosted, Tokens, Buttons, Forms, Cards, Chips, Tables mit Spalten-Standards, Callouts, Toasts, Status-Block), Format-Helper SSOT (PHP+JS), Sprach-Standards (Umlaute hart, Genderneutralität, Du-/Sie-Form), Status-Mapping, Empty-States, Reference-Implementation. CI-Refactoring (Phase 1.9) hat alle ASCII-Drift in UI + Code-Kommentaren ausgemerzt, Card-Stub-Badge dezenter, Widget-Test nach Admin verschoben. Verifikations-Greps: 0 Treffer.

**Phase 0 cPanel-Setup ✅ erledigt** (2026-05-02): Subdomains, DB, Verzeichnisschutz (mit cp:ppd-Block-Fix), Bitwarden, WinSCP-Auto-Deploy.

**Wichtige Entscheidung 2026-05-03:** **JTL REST API zurückgestellt** — JTL-Beta wird vermutlich kostenpflichtig (~100 €/Monat). Workaround: JTL-Produktbewertungen später via direkt-SQL aus JTL-MSSQL-DB ziehen (production-app hat das Connection-Pattern bereits). Eigener Plan dafür kommt in Phase 1.5 oder 2.5.

**Wartet auf:** Google Reviews-API + Trustpilot Public-API Freigabe (3-14 Tage). Sobald **eine** der APIs grün ist, fehlen nur noch zwei API-Client-Files (`lib/api_clients/google.php` / `trustpilot.php`) + Cron-Skripte.

**Hinweis:** [`docs/ARCHITEKTUR.md`](../docs/ARCHITEKTUR.md) zeigt aktuell noch den ursprünglichen Vercel-Stand und wird in Phase 0 des Detailplans auf den neuen Soll-Zustand umgeschrieben.

## Phasen-Übersicht

### ☐ Phase 0 — Foundation

**Status:** in Arbeit (Detailplan: [`2026-05-03-phase-0-foundation.md`](2026-05-03-phase-0-foundation.md))

cPanel-Vorarbeit ✅ erledigt (2026-05-02): Subdomains, DB, Verzeichnisschutz, Bitwarden.

Im Detailplan:
- [x] Subdomains `sporeprint.pilzling.eu` + `admin-sporeprint.pilzling.eu` (cPanel) — done
- [x] DB `pilzling_reviews_app` — done
- [x] Verzeichnisschutz Admin — done
- [ ] `docs/ARCHITEKTUR.md` auf Server-Profis-Stack-Soll-Zustand umschreiben
- [ ] GitHub-Repo `pilzling-sporeprint-review-app` anlegen + Initial-Commit (Boilerplate aus production-app übernommen + angepasst)
- [ ] `_db/schema_v1.sql` schreiben + manuell via phpMyAdmin einspielen
- [ ] Google Business Profile API — OAuth-Setup + Test-Call (3 Shops)
- [ ] Trustpilot API-Key registrieren + Test ob Public API ausreicht
- [ ] Server-Profis-Support: Tarif-Details schriftlich bestätigen (DDoS, Bandbreite)
- [ ] WinSCP-Auto-Deploy-Tab für Sporeprint einrichten + Test-Push
- [ ] Phase-Verifikation (Code ↔ Doku-Abgleich)

**JTL REST API zurückgestellt** — drohende Kostenpflicht (~100 €/Monat für Beta). JTL-Produktbewertungen kommen später via direkt-SQL aus JTL-MSSQL-DB als eigener Plan.

---

### ☐ Phase 1 — Backend

**Status:** offen — wartet auf Abschluss Phase 0

- [ ] PHP-Grundgerüst: `lib/db.php`, `lib/auth.php` (Single-Admin), `lib/config.php`, `lib/public_api_guard.php`
- [ ] Cron-Skripte (PHP-CLI, getriggert von cPanel-Cronjobs alle 6h):
  - [ ] Google Business Profile Reviews fetchen (3 Shops)
  - [ ] Trustpilot Reviews fetchen (3 Shops)
  - [ ] JTL Produktbewertungen — **zurückgestellt**, später via direkt-SQL-aus-JTL-DB als eigener Sub-Plan
- [ ] Public-API-Endpoint `GET /api/reviews?shop=...` mit allen 6 Härtungs-Layern:
  - [ ] CORS-Whitelist pro Shop (Layer 1)
  - [ ] Referer-Check (Layer 2)
  - [ ] Rate-Limiting per IP via `rate_limits`-Tabelle (Layer 3)
  - [ ] Cache-Header `Cache-Control: public, max-age=21600` (Layer 4)
  - [ ] SRI-Hash für `widget.js` (Layer 5 — wird ab Widget-Stabilität in Phase 2 genutzt)
  - [ ] Datenminimierung im Response (Layer 6)
- [ ] **Pattern-Heben:** Public-API-Härtungs-Snippets nach `C:\AI-Workspace\references\php-patterns\public-api-hardening\` als Workspace-SSOT (Pre-Check D7)

---

### ☐ Phase 2 — Widget finalisieren

**Status:** offen

- [ ] Prototyp ([`src/widget_prototype.html`](../src/widget_prototype.html)) mit echten API-Daten verbinden
- [ ] `widget.js` als deploybare Datei aufbauen (vanilla JS, kein Framework — minimaler Footprint)
- [ ] Produktbewertungen als eigene Karten-Variante (mit Produktname)
- [ ] Styling-Final-Pass mit echten Daten + CI-Farben pro Shop (aus `widget_configs`)
- [ ] Sporeprint-Branding einbauen: kleiner Footer-Schriftzug "powered by Sporeprint" + Hover-Tooltip mit Erklärung "Was ist ein Sporeprint?"
- [ ] **Sporen-Rating-Spielerei** (UI-Polish, optional): statt 5 Sterne ein eigenes Rating-Visual mit Sporenabdruck-Motiv. A/B-Test gegen klassische Sterne, falls Conversion-Daten irgendwann messbar werden
- [ ] JTL-Template-Integration: `<script>`-Tag einbauen (alle 3 Shops, mit SRI-Hash)
- [ ] Responsive-Test auf echten Geräten

---

### ☐ Phase 2.5 — Widget-Konfigurator-Backend (Schema + API)

**Status:** offen — kommt zwischen Phase 2 (Widget-Polish) und Phase 3 (Admin-UI). Schema-Erweiterung + GET-Endpoint, ohne Konfigurator-UI.

- [ ] Migration v2: `widget_configs.theme_overrides JSON NULL` hinzufügen
- [ ] Public-API erweitern oder neuer Endpoint `GET /api/shop-config?shop=...` der Shop-Branding-Daten ausliefert (für Widget-fetch)
- [ ] Widget Phase-2b nutzt diese Daten statt Mock

### ☐ Phase 3 — Admin Dashboard

**Status:** offen

- [ ] App-Login (Layer 2, Single-Admin-Passwort initial; User-Stamm später falls nötig)
- [ ] Reviews-Übersicht mit Filter (Plattform, Shop, Sternanzahl, Datum, Sichtbarkeit)
- [ ] Reply-Funktion: Antwort verfassen, an Google Business Profile zurück-pushen via API
- [ ] Visibility-Toggle pro Review (sichtbar / versteckt / geflagged)
- [ ] Analytics-Seite: Wachstumsgraph, Funnel, Durchschnitt pro Plattform/Shop
- [ ] Widget-Konfigurator-UI: Layout, Filter (`min_stars`, `max_items`), Custom-CSS — schreibt in `widget_configs`-Tabelle. **Plus Theme-Override-Picker:** pro Shop CSS-Variablen punktuell überschreiben (siehe ARCHITEKTUR.md "Widget-Theming-Strategie 2-stufig"). Live-Preview neben Color-Picker, "Auf Default zurücksetzen"-Button pro Override-Slot
- [ ] QR-Code-Generator (für Verpackung, Marktstand, etc.)
- [ ] Shop-Switcher: Pilzling / Pilzwald / Shroom Boom
- [ ] Benachrichtigung per E-Mail bei neuen Reviews (über Brevo oder simples PHP-mail)

---

### ☐ Phase 4 — Brevo-Integration (Stufenmodell)

**Status:** offen — Stufen 1-3 konkret eingeplant, Stufe 4 perspektivisch

Brevo-Integration ist mehr als nur "E-Mail-Versand" — es geht um die Verzahnung zwischen Marketing-Automation und Review-System. Die Stufen werden inkrementell aufgebaut, jede Stufe hat eigenen Mehrwert.

#### Stufe 1 — Brevo unabhängig (einfacher Start)

- [ ] E-Mail-Template "Shop-Bewertungsanfrage" in Brevo (Buttons: Google / Trustpilot)
- [ ] E-Mail-Template "Produktbewertungsanfrage" in Brevo (Link zur JTL-Produktseite)
- [ ] Brevo-Automation: Trigger aus JTL bei Status "Bestellung geliefert" → Tag +7 (Shop-Review-Mail), Tag +14 (Produkt-Review-Mail)
- [ ] Test-Durchlauf mit echter Bestellung
- **Resultat:** Reviews kommen rein, aber keine Verbindung zwischen Mail und Review

#### Stufe 2 — Sporeprint empfängt Brevo-Events

- [ ] Brevo-Webhook-Empfänger-Endpoint in Sporeprint (`POST /api/brevo/event`)
- [ ] Aggregierte Counter (Open-Rate, Click-Rate) pro Template + Zeitraum
- [ ] Anzeige im Admin-Dashboard: "Open-Rate diese Woche / Click-Rate"
- [ ] Datenminimierung: Empfänger-E-Mail bleibt in Brevo, Sporeprint speichert nur Event-Counter
- **Resultat:** Funnel-Sichtbarkeit ohne personenbezogene Speicherung

#### Stufe 3 — Sporeprint triggert Brevo (Funnel-Tracking)

- [ ] `review_requests`-Tabelle in DB (mit gehashter Email, Brevo-Message-ID, Bestellbezug, Open/Click-Status, später ggf. Verknüpfung zur entstandenen Review-ID)
- [ ] Sporeprint-Trigger ersetzt JTL-Trigger: bei "Bestellung geliefert" (per Webhook von JTL nach Sporeprint) startet Sporeprint die Brevo-Mail über die Brevo-Transactional-API
- [ ] Korrelation Mail → Review (Heuristik: Email-Match oder Click-Through-Tracking-Token)
- [ ] Conversion-Funnel im Dashboard: "Wie viele Mails werden zu Reviews?"
- [ ] DSGVO-Hygiene: Email gehashed (SHA-256), Tracking-Daten max. 24 Monate Retention, Datenschutzerklärung erweitern
- **Resultat:** datengetriebene Optimierung der Review-Anfragen

#### Stufe 4 — Bidirektionales Tagging (perspektivisch)

- [ ] Sporeprint taggt Brevo-Kontakte automatisch ("hat bewertet", "5-Sterne-Reviewer", "Re-Engagement-Kandidat")
- [ ] Brevo nutzt Tags für Segmentierung in anderen Kampagnen (Newsletter, Re-Marketing)
- [ ] Reviews als Content-Block in Brevo-Newslettern ("Das schreiben unsere Kunden")
- **Status: perspektivisch** — wird angestoßen wenn Stufen 1-3 stabil laufen und das Marketing-Team konkret danach fragt. Eigenes Konzept-Dokument vor Umsetzung empfohlen (komplexe Verzahnung, mehrere Stakeholder).

---

### ☐ Phase 5 — Go Live

**Status:** offen

- [ ] Alle drei Shops einbinden
- [ ] Altes onlinereviews.tech-Widget entfernen
- [ ] onlinereviews.tech-Abo kündigen 🎉
- [ ] Datenschutzerklärungen der drei Shops erweitern (IP-basiertes Rate-Limiting Hinweis)

---

## Offene Punkte / Entscheidungen

| Frage | Wer klärt das | Status |
|-------|---------------|--------|
| Google Cloud Zugang: gleicher Account wie Business Profil? | Christian | offen |
| Trustpilot: reicht die public API für alle Reviews? | Test in Phase 0 | offen |
| JTL REST API: ist sie im aktuellen JTL-Setup aktiviert? | Christian / JTL-Admin | offen |
| Server-Profis-Tarif L 5.1: DDoS-Schutz + Bandbreiten-Limits | Server-Profis-Support | offen |
| Wann Folder-Rename `sternfaenger-review-tool` → `sporeprint`? | Christian (außerhalb der Session) | offen |
| Repo `pilzling-sporeprint-review-app` anlegen | Christian | offen |
