# Widget-Refactor — Konzept (Stufe 1)

**Erstellt:** 2026-05-03
**Status:** Konzept — kein Umsetzungsplan
**Roadmap-Bezug:** Phase 2a war zu kurz gedacht — Widget braucht mehr Features um mit Sternfänger-Original mitzuhalten

## Ausgangslage

Aktuelle `widget.js` hat nur Basis-Karussell-Layout. Beim Vergleich mit:
- `references/widget_prototype.html` (ursprünglicher Sporeprint-Plan, sehr durchdacht)
- `references/Orginal Structure/Styling Screenshots/widget_in_shop.png` (Sternfänger-Live-Look)
- `references/Orginal Structure/widget/widget_skript` (Original-Embed-Snippet mit allen Konfig-Flags)

…fällt auf dass **viele Standard-Features fehlen**, die User von der bisherigen Sternfänger-Lösung gewohnt ist.

## Feature-Audit — was fehlt

| # | Feature | Original-Sternfänger | Sporeprint-Prototyp (HTML) | Sporeprint-Live (widget.js) |
|---|---------|---------------------|---------------------------|------------------------------|
| 1 | Plattform-Aggregat oben (Google ★ 4.8, Trustpilot ★ 4.5 mit Logos) | ✅ via `stats="true"` | ✅ angelegt | ❌ fehlt |
| 2 | Gesamt-Anzahl ("63 Bewertungen") | ✅ | ✅ angelegt | ❌ fehlt |
| 3 | Avatar / Profile-Pic in Card | ✅ via `profile-pic="true"` | ✅ angelegt | ❌ fehlt |
| 4 | Plattform-Icon pro Card (oben rechts) | ✅ G / Stern | ✅ angelegt | ❌ aktuell nur Text "google" als Meta-Label unten |
| 5 | Datum unter Name | ✅ | ✅ "17. Dezember 2025" | ❌ fehlt |
| 6 | Pfeil-Navigation Prev/Next (SVG-Buttons) | ✅ | ✅ angelegt | ❌ aktuell nur Touch-Scroll |
| 7 | CTA "Hinterlassen Sie eine Bewertung" als Pill-Button | ✅ via `addReview="true"` | ✅ angelegt | ❌ fehlt |
| 8 | Sporen-Rating-Visual | – | ❌ klassische Sterne | ✅ (Phase 2a.1) |
| 9 | CI-Farben pro Shop | – | ❌ hardcoded Pilzling | ✅ (Phase 2a.2) |
| 10 | Produktbewertungs-Karten-Variante | – | – | ✅ (Phase 2a.3) — aber Look passt vielleicht nicht |
| 11 | Sporeprint-Footer mit Branding | (Sternfänger-Logo) | ✅ "STERNFÄNGER"-Box | ✅ (Phase 2a.4) — Sporen-Icon + "powered by Sporeprint" |
| 12 | Einleitungs-Hook ("Was ist Sporeprint") | ❌ nichts | ❌ fehlt | ❌ fehlt — User-Wunsch |

**Bewertung:** Wir haben das Marken-Element (Sporen-Rating + CI + Branding) gemacht, aber das **funktionale UX-Grundgerüst** vernachlässigt. Aktuell wirkt das Widget weniger informativ als das Original.

## User-Wünsche (aus aktueller Diskussion)

1. **Einleitungsteil oben** mit kurzer Erklärung was Sporeprint ist (Tooltip/Subtitle?)
2. **Plattform-Aggregat** wieder oben (Google + Trustpilot ★ Score)
3. **Karten-Layout aufräumen** — wo steht was
4. **Karten einheitlich** — auch Produktbewertungen sollen den gleichen Aufbau haben
5. **Produkt-Info als Label unten links** statt prominent oben (User-Spontan-Idee)
6. **Verschiedene Widget-Arten** — z.B. kleines "Right-Side-Widget" (Sticky-Element seitlich) als Alternative zum Karussell
7. **Aus Backend-Screenshots** weitere Widget-Typen identifizieren (Stern-Widget für Produktseiten? Vertikaler Feed?)

## Layout-Konzept (Vorschlag, User reviewt)

### Standard-Karussell-Widget (`carousel`)

```
┌──────────────────────────────────────────────────────────────────┐
│  Was unsere Kund:innen über Pilzling sagen                       │  ← Header (mit Akzent-Farbe für Shop-Name)
│                                                                  │
│   [G] 4.8 ★    [T-Stern] 4.5 ★   ←  Plattform-Aggregat         │
│   63 Bewertungen                  ←  Gesamtzahl                  │
│                                                                  │
│  ←  ┌───────────┐ ┌───────────┐ ┌───────────┐  →               │  ← Karussell + Pfeil-Nav
│     │ [Avatar]  │ │ [Avatar]  │ │ [Avatar]  │                   │
│     │ Marie K.  │ │ Tom R.    │ │ Steffi    │  [G/T/S-Icon]     │
│     │ 15.12.25  │ │ 12.12.25  │ │ 8.12.25   │  oben rechts      │
│     │           │ │           │ │           │                   │
│     │ ●●●●●     │ │ ●●●●●     │ │ ●●●●○     │                   │  ← Sporen-Rating
│     │           │ │           │ │           │                   │
│     │ Frische   │ │ Lions     │ │ Tolles    │                   │
│     │ Bio-Pilze │ │ Mane Steak│ │ Produkt…  │                   │
│     │ in Top-…  │ │ schmeckt… │ │           │                   │
│     │           │ │           │ │           │                   │
│     │ [Lions    │ │           │ │           │                   │  ← Produkt-Label nur bei JTL,
│     │  Mane]    │ │           │ │           │                   │     unten links als kleine Chip
│     └───────────┘ └───────────┘ └───────────┘                   │
│                                                                  │
│           [ Hinterlassen Sie eine Bewertung ]                    │  ← CTA-Pill-Button
│                                                                  │
│  ──────────────────────────────────────────                     │
│  ◌ powered by Sporeprint                                        │  ← Footer mit Sporen-Icon
└──────────────────────────────────────────────────────────────────┘
```

**Karten einheitlich:** alle haben den gleichen Aufbau (Avatar+Name+Datum oben links, Plattform-Icon oben rechts, Sporen-Rating, Text). Bei JTL-Produktbewertungen kommt **zusätzlich** unten links ein Produkt-Chip (`.sporeprint-card__product-chip`) — Layout-Höhe bleibt gleich, aber Produktbezug ist klar. Kein extra Border/Top-Highlight wie aktuell.

**Aggregat oben:** zeigt nur Plattformen für die Reviews vorhanden sind. Wenn Trustpilot 0 Reviews → wird ausgeblendet. Wenn nur Google → nur Google.

**Einleitungstext:** subtil, z.B. als Subtitle unter dem H1-Header. *"Echte Bewertungen aus den Plattformen, die unsere Kund:innen wirklich nutzen."* — nur kleine Zeile, nicht groß. Das eigentliche "Was ist Sporeprint" lebt im Footer-Tooltip (schon vorhanden).

### Mini-Widget für Produktseiten (`badge` / `inline`)

Kleiner, dezenter als das Karussell. Nur Aggregat-Info und ggf. 1 Highlight-Review. Ideal um auf Produktseiten unter dem Add-to-Cart-Button zu zeigen.

```
┌───────────────────────────┐
│  ●●●●● 4.8                │
│  basierend auf 63 Bew.    │
│  Alle Bewertungen ansehen │
└───────────────────────────┘
```

Aufruf: `<script src="widget.js" data-shop="pilzling" data-type="badge"></script>`

### Side-Widget (`sidebar`) — User-Idee, optional

Sticky-Element rechts/links am Bildschirm-Rand, klein, klappt auf bei Klick. Sehr unobtrusiv.

```
        [★★★★★]  ←  collapsed sticky-tab
        [4.8]
        [→]

       beim Klick: aufgeklappt mit
       letzten 3 Reviews
```

Aufruf: `<script src="widget.js" data-shop="pilzling" data-type="sidebar"></script>`

### Stern-Widget (`stars-only`) — Original-Plan-Nice-to-have

Nur Sterne ohne Text, für Header-Bereich der Produktseite (z.B. neben Produkt-Titel).

```
●●●●● 4.8 (63)
```

Aufruf: `<script src="widget.js" data-shop="pilzling" data-type="stars-only"></script>`

## Widget-Typen-Roadmap

| Typ | Priorität | Phase |
|-----|-----------|-------|
| `carousel` (Standard) | hoch | Phase 2a-Refactor jetzt |
| `badge` (Mini-Widget) | mittel | Phase 2c |
| `stars-only` (Sterne-Inline) | niedrig | Phase 2c |
| `sidebar` (Sticky) | niedrig | Phase 4+ (eventuell) |

Architektur: ein `widget.js`, schaltet anhand von `data-type` auf den entsprechenden Renderer um. Spart separates Embed-Skript pro Typ.

## Umsetzungs-Vorschlag (Refactor-Plan-Skeleton)

**Wenn Konzept akzeptiert:**

| Schritt | Inhalt |
|---------|--------|
| 1 | widget.js komplett refactoren auf Original-Prototyp-HTML als Basis (Avatar, Datum, Plattform-Icon, Pfeil-Nav, CTA, Aggregat) |
| 2 | Sporen-Rating + CI-Pro-Shop + Produkt-Label-Behandlung darin neu integrieren |
| 3 | Mock-Reviews um Avatar-URLs (Default-No-Image-Pic) + Datum (formatiert) erweitern |
| 4 | Aggregat-Berechnung: Frontend rechnet Durchschnitt + Count aus dem Reviews-Array. Backend liefert die zusätzlichen Stats später als Teil der API-Response |
| 5 | CTA-Link-Konfiguration: `data-add-review-url="..."` als optionales Attribut, default geht auf Brevo-/Bewertungs-Landing-Page |
| 6 | Produkt-Chip unten links als kleine `.sporeprint-card__product-chip` (statt aktueller prominent-Variante) |

**Bewusst nicht jetzt:**
- Mini-Widget-/Side-Widget-Varianten (eigener Folge-Plan)
- Theme-Light vs. Dark (aktuell nur Dark, später togglebar)

## Was du jetzt entscheiden musst

1. **Layout-Konzept oben** so OK? Oder Anpassungen am Aufbau?
2. **Einleitungs-Subtitle** — gute Idee oder weglassen?
3. **Aggregat oben** — anzeigen wenn vorhanden, sonst ausblenden — passt?
4. **Karten-Einheitlichkeit** — Produkt-Chip unten links bei JTL-Reviews, sonst wie Standard-Card — passt?
5. **CTA "Hinterlassen Sie eine Bewertung"** — wo soll der hinzeigen? Brevo-Page? Eigene Landing-Page (`feedback.pilzling.eu`)? Oder konfigurierbar?
6. **Widget-Typen** — `carousel` jetzt refactoren, andere als Folge-Pläne?
7. **Sporen-Rating bleibt** oder wieder klassische Sterne (sind Original-Standard)?

Wenn 1-7 geklärt, schreibe ich den Refactor-Plan als Detailplan und gehe los.

## Referenzen

- Original-Prototyp: `references/widget_prototype.html`
- Sternfänger-Embed: `references/Orginal Structure/widget/widget_skript`
- Live-Look-Screenshot: `references/Orginal Structure/Styling Screenshots/widget_in_shop.png`
- Backend-Features: `references/Orginal Structure/Backend_Features/` (15 PNG-Screenshots — können noch durchgesehen werden für weitere Widget-Typ-Inspiration)
- Aktueller Stand: `src/public/widget.js`, `src/admin/widget-test.php`
