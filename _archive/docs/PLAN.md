# Pilzling Review-System — Bauplan

**Ziel:** Eigenes Review-System als vollständiger Ersatz für onlinereviews.tech (80 €/Monat)
**Marken:** Pilzling · Pilzwald · Shroom Boom (je eigene Shop-Instanz, ein System)
**Shop-System:** JTL Shop
**Marketing:** Brevo (bereits im Einsatz — wird für Review-Automation genutzt)

---

## Die drei Komponenten

```
┌──────────────────────────────────────────────────────────┐
│  Admin Dashboard  (passwortgeschützt, nur intern)        │
│  pilzling-reviews.vercel.app  oder  admin.pilzling.shop  │
│                                                          │
│  · Alle Reviews einsehen (Google + Trustpilot + JTL)    │
│  · Auf Reviews antworten                                 │
│  · Analytics (Wachstum, Funnel, Durchschnitt)           │
│  · Widget konfigurieren (Farben, Filter, Layout)        │
│  · QR-Code generieren                                    │
│  · Shop wechseln: Pilzling / Pilzwald / Shroom Boom     │
└──────────────────────┬───────────────────────────────────┘
                       │
┌──────────────────────▼───────────────────────────────────┐
│  Backend API  (Vercel Serverless, kostenlos)             │
│                                                          │
│  · Cron alle 6h: Google + Trustpilot abrufen            │
│  · JTL REST API: Produktbewertungen abrufen             │
│  · Alles in DB speichern (Vercel KV)                    │
│  · GET /api/reviews?shop=pilzling → Widget-Daten        │
│  · POST /api/reply → Antwort an Google/Trustpilot       │
│  · Multi-Tenant: je Shop eigene Credentials             │
└──────────────────────┬───────────────────────────────────┘
                       │  ein <script>-Tag pro Shop
┌──────────────────────▼───────────────────────────────────┐
│  Widget  (eingebettet im JTL-Shop-Template)             │
│                                                          │
│  · Karussell: Google + Trustpilot + Produktbewertungen  │
│  · Feed: alle Bewertungen untereinander                 │
│  · Zwei direkte CTA-Buttons (Google / Trustpilot)       │
│  · Responsive, CSS Scroll Snap, CI-konform              │
└──────────────────────────────────────────────────────────┘
```

---

## Review-Quellen

| Quelle | Typ | API | Kosten |
|---|---|---|---|
| Google Business Profile API | Shop-Bewertungen | OAuth 2.0, alle Reviews paginiert | kostenlos |
| Trustpilot Business Units API | Shop-Bewertungen | API-Key (public endpoint) | voraussichtlich kostenlos |
| JTL REST API | Produkt-Bewertungen | JTL-eigene REST API | kostenlos |

**Produkt- + Shopbewertungen werden im Widget kombiniert** — Kunden sehen echte Produkterfahrungen direkt neben Google/Trustpilot-Bewertungen.

---

## Brevo-Integration (Review-Anfragen)

Kein eigenes E-Mail-System nötig — Brevo übernimmt das komplett.
Trigger aus JTL: Bestellstatus → „Geliefert" → Brevo-Automation startet.

```
Tag +7:   E-Mail „Wie war dein Einkauf?"
          → [Bei Google bewerten]  [Bei Trustpilot bewerten]

Tag +14:  E-Mail „Wie gefällt dir dein [Produktname]?"
          → [Produkt jetzt bewerten]  (direkt zur JTL-Produktseite)
```

**Aufwand:** 2 Brevo-Templates + 1 Automation — kein Code, ein Nachmittag.
**Wirkung:** höchste Conversion aller Review-Kanäle (personalisiert, richtiger Zeitpunkt).

---

## Multi-Shop / Multi-Tenant

Jeder Shop bekommt eine `shop-id`. Ein System, ein Dashboard, alles trennbar.

| Shop | Domain | Google Place ID | Trustpilot ID |
|---|---|---|---|
| Pilzling | pilzling.shop | (einzutragen) | (einzutragen) |
| Pilzwald | (einzutragen) | (einzutragen) | (einzutragen) |
| Shroom Boom | (einzutragen) | (einzutragen) | (einzutragen) |

Widget-Einbindung im JTL-Template je Shop:
```html
<script src="https://pilzling-reviews.vercel.app/widget.js"
        data-shop="pilzling">
</script>
```

---

## Features — Scope

### Bauen ✅
- Widget: Karussell + Feed (Prototyp bereits vorhanden)
- Kombinierte Anzeige: Shop-Reviews + Produktbewertungen
- Review-Anfragen per E-Mail (via Brevo — kein eigenes System)
- QR-Code-Generator (für Verpackung, Marktstand, etc.)
- Landing Page: Plattformwahl (Google / Trustpilot) mit eigenem Branding
- Analytics: Wachstumsgraph, Bewertungs-Funnel, Durchschnitt pro Plattform
- Admin: Reviews einsehen, antworten, filtern
- Multi-Tenant: Pilzling / Pilzwald / Shroom Boom
- Benachrichtigung per E-Mail bei neuen Reviews

### Später / Nice-to-have 🔜
- Social Sharing: Review → Instagram-Post-Generator (für Content-Marketing)
- Sternchen-Widget: kleines Bewertungssymbol für Produktseiten (JTL-Template)

### Skip ❌
| Feature | Grund |
|---|---|
| SMS-Anfragen | DSGVO-Aufwand, Brevo deckt das ab |
| WhatsApp-Anfragen | WhatsApp Business API = teuer + komplex |
| Video-Reviews | habt ihr nicht, kein unmittelbarer Mehrwert |
| Popup-Widget | schlechte UX, meist ignoriert |

---

## Phasen

### Phase 0 — Voraussetzungen klären (1–2 Tage)
- [ ] Google Cloud Projekt anlegen, Business Profile API aktivieren
- [ ] OAuth 2.0 einrichten, ersten API-Call testen (alle Reviews abrufbar?)
- [ ] Trustpilot API-Key registrieren (developers.trustpilot.com), testen
- [ ] JTL REST API: Zugang prüfen, Produktbewertungen-Endpunkt testen
- [ ] Brevo: JTL-Integration für Trigger „Bestellung geliefert" prüfen

### Phase 1 — Backend (3–5 Tage)
- [ ] Vercel-Projekt anlegen, Grundstruktur (Next.js)
- [ ] Datenschicht: Vercel KV, Datenmodell (Shop · Plattform · Review · Antwort)
- [ ] Cron Job: Google + Trustpilot + JTL alle 6h abrufen und speichern
- [ ] API-Endpunkt: `GET /api/reviews?shop=pilzling` → JSON
- [ ] Multi-Tenant-Konfiguration: je Shop eigene API-Keys in Umgebungsvariablen

### Phase 2 — Widget finalisieren (2–3 Tage)
- [ ] Prototyp mit echten API-Daten verbinden
- [ ] Produktbewertungen als eigene Karten-Variante (mit Produktname)
- [ ] Styling-Final-Pass mit echten Daten
- [ ] JTL-Template-Integration: `<script>`-Tag einbauen
- [ ] Responsive-Test auf echten Geräten

### Phase 3 — Admin Dashboard (3–5 Tage)
- [ ] Login (einfaches Passwort-Auth, kein Nutzersystem nötig)
- [ ] Reviews-Übersicht mit Filter (Plattform, Shop, Sternanzahl, Datum)
- [ ] Reply-Funktion (Google Business Profile Reply API)
- [ ] Analytics-Seite: Wachstumsgraph, Funnel, Durchschnitt
- [ ] Widget-Konfigurator: Farben, Filter, Layout
- [ ] QR-Code-Generator
- [ ] Shop-Switcher: Pilzling / Pilzwald / Shroom Boom

### Phase 4 — Brevo-Automation (1 Tag)
- [ ] E-Mail-Template 1: Shop-Bewertungsanfrage (Google + Trustpilot)
- [ ] E-Mail-Template 2: Produktbewertungsanfrage (JTL-Link)
- [ ] Automation in Brevo aufbauen (Trigger + Timing)
- [ ] Test-Durchlauf mit echter Bestellung

### Phase 5 — Go Live
- [ ] Alle drei Shops einbinden
- [ ] Altes Sternfänger-Widget entfernen
- [ ] Abo kündigen 🎉

---

## Tech Stack

| Bereich | Technologie | Kosten |
|---|---|---|
| Hosting + API + Cron | Vercel (Free Tier) | 0 € |
| Datenbank | Vercel KV (Redis) | 0 € bis ~50k Reads/Monat |
| Admin + Widget | Next.js (React) | 0 € |
| E-Mail-Automation | Brevo (bereits vorhanden) | 0 € extra |
| Google Reviews | Business Profile API | 0 € |
| Trustpilot Reviews | Business Units API | 0 € (tbd) |
| Produktbewertungen | JTL REST API | 0 € |

**Gesamt laufend: 0 €/Monat** (vs. 80 €/Monat bisher)

---

## Offene Punkte vor Baustart

| Frage | Wer klärt das |
|---|---|
| Google Cloud Zugang: gleicher Account wie Business Profil? | Christian |
| Trustpilot: reicht die public API für alle Reviews? | Test in Phase 0 |
| JTL REST API: ist sie im aktuellen JTL-Setup aktiviert? | Christian / JTL-Admin |
| Brevo → JTL Trigger: läuft das über Webhook oder JTL-Automation? | Christian |
| Domain für Admin-Dashboard: eigene oder Vercel-Subdomain? | Christian |
