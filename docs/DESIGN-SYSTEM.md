# Sporeprint — Design-System (Admin-UI)

**Stand:** Phase 1 Backend-Foundation, 2026-05-03.

SSOT für UI-Komponenten im Admin-Bereich (`admin-sporeprint.pilzling.eu`). Übernimmt Pilzling-CI 1:1 aus `production-app/docs/DESIGN-SYSTEM.md`, ist aber bewusst auf das **kondensierte Subset** reduziert das Sporeprint v1 braucht — kein Modal-Framework, keine View-Tabs, keine Stats-Cards. Die ergänzen wir, sobald Phase 3 echte Admin-Funktionen baut.

**Widget-Styling** (Public-Subdomain, embedded im JTL-Shop) ist eigenständig in `src/public/widget.js` — eigene CSS-Namespaces (`.sporeprint-*`), keine Abhängigkeit zu Admin-CSS. Diese Doku gilt **nur** fürs Admin-Backend.

## SSOT-Prinzip

- **Tokens** (Farben, Spacing, Typo, Radius, Shadows) leben **ausschließlich** in `src/admin/assets/tokens.css`
- **Komponenten** leben **ausschließlich** in `src/admin/assets/components.css`
- **Layout** (App-Header, App-Main, Login-Layout) lebt **ausschließlich** in `src/admin/assets/layout.css`
- Admin-Pages (PHP) haben **kein Inline-`<style>`** und keine `style="..."`-Attribute — alles über CSS-Klassen aus den Asset-Files

Wenn eine neue Komponente gebraucht wird:
1. Prüfen ob's bereits eine ähnliche gibt (grep `components.css`)
2. Wenn ja → bestehende nutzen
3. Wenn nein → in `components.css` ergänzen + hier dokumentieren

## CSS-Architektur

```
src/admin/assets/
├── admin.css        ← @import-Hub (Single Entry Point — von PHP-Pages geladen)
├── tokens.css       ← Custom-Properties (alle Werte SSOT)
├── base.css         ← Reset, Typography, Body-Defaults, Utility-Klassen
├── layout.css       ← App-Header, App-Main, Login-Layout, Page-Header, Grid
└── components.css   ← Buttons, Forms, Cards, Chips, Tables, Callouts, Toasts, Status-Block
```

Pages laden nur `admin.css`. Lade-Reihenfolge ist über @import gesetzt.

## 1. Farb-Palette (Pilzling CI)

### Tokens

```css
--color-dark: #151824;            /* Text, Dark-Fills */
--color-cream: #F2F0ED;           /* Canvas-Background */
--color-primary: #F85B05;         /* Orange — CTAs, Hover */
--color-accent-blau-dark: #5A74B8; /* Buttons, Links, Tabellen-Header */
--color-accent-gruen-dark: #507227; /* Success */
--color-error: #C62828;
```

### Hintergrund-Hierarchie (3-Tier)

| Layer | Token | Verwendung |
|-------|-------|-----------|
| Canvas | `--color-cream` | Seiten-Background, `<body>` |
| Content | `--color-white` | Cards, Tabellen, Status-Block, Modal-Body |
| Section-Hervorhebung | `--color-cream-dark` | Tabellen-Toolbar, Divider, Code-Blocks |

### Universal-Hover-Regel

Alle CTAs / klickbaren Elemente wechseln im Hover zu **Orange** (`--color-primary`). Inputs hingegen nutzen **Blau** für Focus (ruhig, navigierend).

## 2. Schriftart + Typografie

### Primär-Schriftart: Rubik

**Pilzling-CI-Font.** Übernommen 1:1 aus production-app. Self-hosted (kein Google-Fonts-CDN — DSGVO-freundlich, schneller, kein externes Tracking).

- **Datei:** `src/admin/assets/fonts/Rubik.woff2` (Variable Font, deckt 100-900 ab)
- **Definition:** `@font-face` in `base.css`
- **Token:** `--font-sans` in `tokens.css` mit System-Fallback-Stack falls Font nicht laden kann

**Niemals** Google Fonts via `<link rel="stylesheet" href="https://fonts.googleapis.com/...">` einbinden — DSGVO-Verstoß und Performance-Verschlechterung.

### Schrift-Hierarchie

| Element | Font-Size-Token | Verwendung |
|---------|----------------|------------|
| `h1` | `--font-size-2xl` (1.6rem) | Page-Title |
| `h2` | `--font-size-xl` (1.4rem) | Section-Title |
| `h3` | `--font-size-lg` (1.2rem) | Card-Title, Subsection |
| Body | `--font-size-base` (0.9rem) | Default |
| Labels / Meta | `--font-size-sm` (0.85rem) | Form-Labels, Captions |
| Chips, Captions, IDs | `--font-size-xs` (0.72rem) | Status-Chips, kleine Indikatoren |

Font-Weights: 400 (Regular), 500 (Medium für Labels/Buttons), 600 (Semibold für Headings/Primary-Buttons), 700 (Bold für `.app-header__brand`).

## 2b. Format-Standards (SSOT)

**Format ist so grundlegend wie Farben** — und genauso eine SSOT. Es gibt **eine Stelle pro Format-Frage**, nirgendwo sonst. Dupliziertes Formatieren führt unweigerlich zu Drift (`04.05.2026` hier, `2026-05-04` dort, `4. Mai 2026` woanders).

### SSOT-Tabelle

| Was | PHP-SSOT | JS-SSOT |
|-----|----------|---------|
| Datum (TT.MM.JJJJ) | `lib/helpers.php::formatDate($iso)` | `AppFormat.date(iso)` |
| Datum + Uhrzeit (TT.MM.JJJJ, HH:MM) | `lib/helpers.php::formatDate($iso, true)` | `AppFormat.dateTime(iso)` |
| Uhrzeit allein (HH:MM) | (PHP: `date('H:i', strtotime(...))`) | `AppFormat.time(iso)` |
| Relative Zeit ("vor 3h") | `lib/helpers.php::humanTimeDiff($iso)` | `AppFormat.relative(iso)` |

`AppFormat` lebt in `src/admin/assets/format.js`. Wird ab Phase 3 von Pages eingebunden — vor allen anderen JS-Modulen.

### Anzeige-Format

- **Datum:** TT.MM.JJJJ mit **führender Null** (`05.04.2026`, niemals `5.4.2026`)
- **Datum + Zeit:** `04.05.2026, 14:30` (Komma + Leerzeichen)
- **Relative Zeit:** "gerade eben" / "vor 5 Min" / "vor 3h" / "vor 2 Tagen"
- **Locale:** **deutsche Konventionen** überall — Dezimal-Komma, Tausender-Punkt
- **Bei null/leer/ungültig:** Em-Dash `–` (kein "n/a", kein "null", kein leerer String)

### Vier harte Regeln

1. **Kein `date()` direkt im PHP-View-Code** — immer über `formatDate()` / `humanTimeDiff()`
2. **Kein `toLocaleDateString()` / `toLocaleString()` direkt im JS** — immer über `AppFormat.*`
3. **Kein `new Intl.DateTimeFormat(...)` direkt** — Pattern-Drift-Risiko
4. **Kein hardcoded Format-String** wie `'YYYY-MM-DD'` in UI-Code — nur in DB-Queries (dort ISO erwartet)

### Erkennungs-Greps (für CI / Reviews)

```bash
# In Page-Code (außerhalb lib/) sollten diese 0 Treffer zeigen
rg "date\('d\." src/admin/ src/public/                # 0 außerhalb lib/helpers.php
rg "toLocaleString|toLocaleDateString" src/admin/     # 0 außerhalb format.js
rg "new Intl\." src/admin/                            # 0 außerhalb format.js
```

## 3. Buttons (5 Basis-Typen + Modifier)

### Hierarchie

| Klasse | Stil | Wann |
|--------|------|------|
| `.btn-primary` | Blau-Dark gefüllt, weißer Text | Haupt-Aktion (Submit, Anlegen). Eine pro View, max. zwei. |
| `.btn-secondary` | Weiß + Blau-Dark Outline | Alternativ-Aktion (Abbrechen, Zurück) |
| `.btn-tertiary` | Ghost (transparent, Text in Blau-Dark) | Toolbar, Filter, dezente Aktionen |
| `.btn-icon` | 28x28 Icon-only mit Border | Inline-Table-Actions, Edit/Delete |
| `.btn-link` | Text-Link-Style | Inline "mehr anzeigen", dezente Verknüpfungen |

Alle wechseln im Hover auf **Orange** (Primary, Border, oder Text je nach Typ).

### Modifier

| Klasse | Wirkung |
|--------|---------|
| `.btn--sm` | Kompakt — Padding `var(--space-1) var(--space-3)`, Font-Size `xs`. Für Inline-Actions, Toolbars. |
| `.btn--block` | 100% Breite |
| `.btn--spaced` | `margin-left: var(--space-3)` für visuellen Gap zu Vorgänger-Buttons |

### Spezial-Varianten

| Klasse | Wann |
|--------|------|
| `.btn-danger` | Destruktive Aktionen (Delete) — Rot gefüllt |
| `.btn-success` | Bestätigung von positiver Aktion — Grün gefüllt |
| `.btn-icon--danger` | Icon-only Delete (auf Hover Rot) |

### Anti-Patterns

- ❌ `<button style="background: red;">` — niemals Inline-Style. Stattdessen `.btn-danger`.
- ❌ Eigene `.my-cta-button` lokal in einer Page-CSS — niemals lokales CSS, immer in `components.css` ergänzen.
- ❌ Mehrere `.btn-primary` auf einer Seite — Hierarchie verwässert (Ausnahme: zwei klar getrennte Sektionen).

## 4. Forms

### Inputs / Selects / Textareas

Standard-Styling identisch über alle Form-Elemente:

```css
border: 1px solid var(--color-border);
border-radius: var(--radius);
padding: var(--space-2) var(--space-3);
font-size: var(--font-size-base);
```

**Focus-State:** Blau-Border + Blau-Ring (`--shadow-focus-blau`). Bewusst nicht Orange — Inputs sind keine CTAs.

### Form-Strukturen

```html
<div class="form-row">
    <label for="username">Benutzername</label>
    <input type="text" id="username" name="username">
    <p class="form-help">Optional: erklärender Hilfetext</p>
</div>

<div class="form-row">
    <label for="email">E-Mail</label>
    <input type="email" id="email" class="is-invalid">
    <p class="form-error">Ungueltige E-Mail-Adresse</p>
</div>

<div class="form-actions">
    <button type="submit" class="btn-primary">Speichern</button>
    <button type="button" class="btn-secondary">Abbrechen</button>
</div>
```

### Validation-Klassen

- `.is-invalid` an Input → roter Border + roter Focus-Ring
- `.form-error` (kleiner roter Text unter dem Input)
- `.form-help` (kleiner grauer Hilfetext)

## 5. Chips / Badges

```html
<span class="chip chip--green">Sichtbar</span>
<span class="chip chip--orange">Neu</span>
<span class="chip chip--red">Geflagged</span>
<span class="chip chip--blue">Gepostet</span>
<span class="chip chip--peach">Wartend</span>
<span class="chip chip--gray">Versteckt</span>
```

### Sporeprint-Status-Mapping (SSOT)

| Datenfeld + Wert | Chip-Klasse | Anzeige-Label |
|------------------|-------------|---------------|
| `reviews.visibility = 'visible'` | `chip--green` | Sichtbar |
| `reviews.visibility = 'hidden'` | `chip--gray` | Versteckt |
| `reviews.visibility = 'flagged'` | `chip--red` | Geflagged |
| `reviews.source = 'google'` | `chip--blue` | Google |
| `reviews.source = 'trustpilot'` | `chip--green` | Trustpilot |
| `reviews.source = 'jtl'` | `chip--peach` | JTL |
| `review_replies.external_status = 'pending'` | `chip--orange` | Wartend |
| `review_replies.external_status = 'sent'` | `chip--green` | Gepostet |
| `review_replies.external_status = 'failed'` | `chip--red` | Fehlgeschlagen |
| `sync_runs.status = 'running'` | `chip--blue` | Läuft |
| `sync_runs.status = 'ok'` | `chip--green` | OK |
| `sync_runs.status = 'error'` | `chip--red` | Fehler |
| Neue Review (< 24h alt) | `chip--orange` | Neu |

Bei jedem neuen Status-Feld in der DB: hier eintragen. Dieser Mapping ist **SSOT** — Code referenziert die Klasse über zentrale Render-Funktion (kommt in Phase 3).

### Generisches Variant-Mapping (für unbekannte Status)

| Variante | Wann (semantisch) |
|----------|-------------------|
| `chip--green` | Erfolg, OK, sichtbar, geliefert, abgeschlossen |
| `chip--orange` | Pending, offen, neu, in Arbeit, Aufmerksamkeit |
| `chip--blue` | Info, in Sync, neutral-positiv |
| `chip--peach` | Wartend, erwartet, Zwischenzustand |
| `chip--red` | Fehler, geflagged, storniert, kritisch |
| `chip--gray` | Inaktiv, archiviert, versteckt, neutral |

## 5a. Empty-States

Wenn eine Liste/Tabelle/Sektion **keine Daten** hat, niemals einen leeren Block stehen lassen — immer eine **klare, kurze Erklärung** was da hin müsste und ggf. wie:

```html
<div class="data-table__empty">
    Noch keine Reviews. Sobald eine API freigegeben ist und der Cron gelaufen ist,
    erscheinen sie hier.
</div>
```

Drei Empty-State-Typen:

| Typ | Wann | Beispiel-Text |
|-----|------|---------------|
| **Wartend** | System läuft, Daten kommen noch | "Noch keine Reviews. Erste Daten ab dem ersten Cron-Lauf." |
| **Leer durch Filter** | User hat gefiltert, kein Treffer | "Keine Reviews mit dieser Filter-Kombination. [Filter zurücksetzen]" |
| **Echt leer** | Es gibt nichts und es ist OK | "Keine Antworten verfasst." |

Empty-States sind **kein Fehler-State** — sie sind erwartete Zustände, keine Callout-Error-Box, sondern dezente muted-Texte oder gestylte `.data-table__empty`-Container.

## 5b. Sprache, Schreibweise, Tone

UI-Strings, Doku, Commit-Messages — überall gilt:

### Umlaut-Pflicht (hart)

**Eindeutiger Test für Grenzfälle:** Wenn der Text theoretisch von einem Menschen gelesen werden könnte → Umlaute. Wenn der Text als String vom System interpretiert wird (Code-Identifier, URL, Datei-Name) → ASCII.

| Kategorie | Schreibweise | Beispiele |
|-----------|--------------|-----------|
| **ASCII (technische Identifier)** | nur a-z, 0-9, `_`, `-` | PHP-Variablen, Funktionen, DB-Tabellen/-Spalten, Datei-/Ordnernamen, CSS-Klassen, HTTP-Header, ENV-Variablen, JSON-Keys, URL-Parameter |
| **Umlaute (alles andere, kein Kompromiss)** | `äöüÄÖÜß` korrekt | HTML-Output, UI-Labels, Form-Placeholder, Error-Messages, HTML-Title, Toast-Texte, Code-Kommentare, Doku-Files, Commit-Messages, Plan-Files |

### Substitutions-Tabelle (häufige Fehler — niemals so schreiben)

| Falsch | Richtig |
|--------|---------|
| `fuer` | `für` |
| `ueber`, `ueberall`, `ueberhaupt` | `über`, `überall`, `überhaupt` |
| `Aenderung`, `aendern`, `geaendert` | `Änderung`, `ändern`, `geändert` |
| `moeglich`, `unmoeglich` | `möglich`, `unmöglich` |
| `naechst`, `naechste` | `nächst`, `nächste` |
| `Stueck`, `Stueckzahl` | `Stück`, `Stückzahl` |
| `oeffnen`, `geoeffnet` | `öffnen`, `geöffnet` |
| `Schluessel` | `Schlüssel` |
| `gehoert`, `gehoeren` | `gehört`, `gehören` |
| `muessen`, `muesste` | `müssen`, `müsste` |
| `koennen`, `koennte` | `können`, `könnte` |
| `duerfen`, `duerfte` | `dürfen`, `dürfte` |
| `haetten`, `haette` | `hätten`, `hätte` |
| `Pruefung`, `pruefen`, `geprueft` | `Prüfung`, `prüfen`, `geprüft` |
| `fuehrt`, `fuehren`, `gefuehrt` | `führt`, `führen`, `geführt` |
| `haeufig` | `häufig` |
| `ausfuehrlich`, `ausfuehren` | `ausführlich`, `ausführen` |
| `waehrend` | `während` |
| `waehlen`, `gewaehlt` | `wählen`, `gewählt` |
| `erklaeren`, `erklaert` | `erklären`, `erklärt` |
| `ergaenzen`, `ergaenzt` | `ergänzen`, `ergänzt` |
| `vollstaendig` | `vollständig` |
| `Loeschen`, `loescht` | `Löschen`, `löscht` |
| `Ruecksprache` | `Rücksprache` |
| `zurueck` | `zurück` |
| `ungefaehr` | `ungefähr` |
| `gemaess` | `gemäß` |
| `verfuegbar` | `verfügbar` |
| `nuetzlich` | `nützlich` |
| `noetig` | `nötig` |
| `ungueltig` | `ungültig` |
| `spaeter`, `spaet` | `später`, `spät` |
| `Kraeuter` | `Kräuter` |

**Erzwingung:** Pre-Commit-Hook in `_tools/check_umlauts.py` prüft staged `.md`-Files + Commit-Messages gegen `_tools/umlauts-patterns.txt`. Allowlist in `_tools/umlauts-allowlist.txt` für Eigennamen die zufällig wie ASCII-Substitution aussehen (z.B. "Bauer", "Goethe", "Boeing").

### Genderneutrale Sprache

- **Im Zweifel `:innen-Form`** — `Kund:innen`, `Mitarbeiter:innen`, `Bewerter:innen`, `Nutzer:innen`
- Alternativ neutrale Begriffe wo natürlich möglich: "Personal" statt "Mitarbeiter:innen", "Team" statt "Mitarbeiter:innen-Gruppe"
- **Niemals nur männliche Form** ("der Kunde") außer es ist eine konkrete Person bekannten Geschlechts

### Du-Form (intern) / Sie-Form (Widget)

| Kontext | Anrede |
|---------|--------|
| **Admin-UI** (intern, wir sind unter uns) | **Du** ("Logge dich ein", "Deine Reviews") |
| **Widget** (im Shop, externe Endkund:innen) | **Sie** ("Hinterlassen Sie eine Bewertung") oder neutral |
| **Doku, Commit-Messages, Code-Kommentare** | **Du** wenn überhaupt Anrede nötig — sonst neutral formulieren |

### Tone

- **Sachlich, freundlich, kompakt.** Keine Buzzwords, keine Marketing-Sprache, keine Emojis im Admin-UI.
- **Konkret statt abstrakt** — "Login nicht möglich" statt "Authentifizierung fehlgeschlagen"
- **Aktiv statt passiv** — "Speichern" statt "Es wird gespeichert"
- **Fehlermeldungen lösungsorientiert** — sagen *was* zu tun ist, nicht nur *was* nicht funktioniert
- **Datenschutz-konform** — niemals personenbezogene Daten in UI-Strings ohne Rechtsgrundlage anzeigen

## 6. Tables (`.data-table`)

Standard-Pattern für strukturierte Daten:

```html
<table class="data-table">
    <thead>
        <tr>
            <th>Datum</th>
            <th>Shop</th>
            <th class="col-numeric-header">Sterne</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>2026-04-28</td>
            <td>Pilzling</td>
            <td class="col-numeric">5</td>
            <td><span class="chip chip--green">Visible</span></td>
        </tr>
    </tbody>
</table>
```

Header: Blau-Dark gefüllt, weißer Text uppercase. Alternate-Rows: dezenter Blau-Tint (4% Opacity). Hover: stärkerer Blau-Tint (8%).

Numerische Spalten bekommen `.col-numeric` (Klasse auf `<td>`) bzw. `.col-numeric-header` (auf `<th>`) → rechtsbündig + tabular-nums (Mono-Spacing).

Leere Tabelle: `<div class="data-table__empty">Keine Daten vorhanden.</div>` direkt unter dem Table-Body oder statt der Tabelle.

### Spaltenbreiten-Standard (3 Kategorien)

Klassen auf `<th>` UND `<td>`. Browser verteilt den restlichen Platz — Flex-Spalten füllen den Rest auf.

**Fix** (kaum Flexibilität):

| Klasse | Breite | Verwendung |
|--------|--------|-----------|
| `.col-stars` | 60px | 5 ★ — rechtsbündig |

**Kompakt** (Wunschbreite mit min/max-Range):

| Klasse | Breite | Range | Verwendung |
|--------|--------|-------|-----------|
| `.col-id` | 100px | 80–130px | Review-IDs, Sync-Run-IDs |
| `.col-status` | 100px | 85–130px | Chip-Spalten (Visibility, Sync-Status) |
| `.col-datum` | 110px | 90–130px | `04.05.2026` — rechtsbündig |
| `.col-source` | 100px | 80–120px | google / trustpilot / jtl |
| `.col-shop` | 110px | 90–140px | pilzling / pilzwald / shroom-boom |
| `.col-author` | 140px | 100–200px | Bewerter-Vorname/-Initialen |

**Flex** (nur Minimum, füllt Rest):

| Klasse | Min | Verwendung |
|--------|-----|-----------|
| `.col-content` | 200px | Review-Text — visueller Anker, Hauptspalte |
| `.col-product` | 120px | Produktname bei JTL-Reviews |
| `.col-actions` | auto | Inline-Buttons, niemals abschneiden |

### Textüberlauf-Standard

Default: einzeilig + `text-overflow: ellipsis` + `white-space: nowrap`. Voller Inhalt im `title="…"`-Attribut für Hover-Tooltip.

Mehrzeilige Spalten (z.B. lange Antworten in einem Detail-View) bekommen `.is-multiline` als zusätzliche Klasse → opt-in für Wrap.

### Einheitliche Spalten-Labels (App-weit konsistent)

Gleiche Daten = gleicher Spaltenname, egal in welcher Tabelle:

| Datenfeld | Spalten-Label | Niemals |
|-----------|--------------|---------|
| `review_id` | **Review-Nr.** | "ID", "Nr." |
| `posted_at` | **Datum** | "Erstellt", "Geschrieben am" |
| `stars` | **Sterne** | "Bewertung", "Rating" |
| `source` | **Plattform** | "Quelle", "Source", "Service" |
| `shop_id` / `shop` | **Shop** | "Mandant", "Brand" |
| `author` | **Autor:in** | "Bewerter", "Name", "Von" |
| `visibility` | **Sichtbarkeit** | "Visible", "Status", "Anzeige" |
| `content` | **Bewertung** | "Text", "Inhalt", "Comment" |
| `product_name` | **Produkt** | "Artikel", "Item" |
| `external_status` | **Push-Status** | "Sync-Status", "API-Status" |

Bei jeder neuen Tabelle: diese Labels nutzen — keine Synonyme erfinden.

### ID-Rendering-Standard

IDs werden **muted + kleiner** gerendert (visueller Anker ist die Inhalt-Spalte daneben — z.B. der Review-Text):

```html
<td class="col-id id-cell">REV-00042</td>
<td class="col-content">Frische Bio-Pilze in Top-Qualität...</td>
```

`.id-cell` setzt `color: muted`, `font-size: xs`, `tabular-nums`. Nie bold, nie default-size.

### Datumsformat-Standard

In Tabellen: **TT.MM.JJJJ** rechtsbündig (Klasse `.col-datum`). Mit Uhrzeit nur wenn explizit relevant — sonst Hover-Tooltip mit voller Zeit.

```html
<td class="col-datum" title="04.05.2026, 14:30:22"><?= formatDate($row['posted_at']) ?></td>
```

### Ausrichtung-Standard

| Spalten-Typ | Ausrichtung | Begründung |
|-------------|-------------|-----------|
| Sterne, Datum, IDs (numerisch), `.col-actions` | rechts | scannbar, tabular-nums |
| Namen, Texte, Plattformen, Shops, Status-Chips | links | natürlicher Lesefluss |
| Mehrzeilige Inhalte | links | Wrap-Beginn linksbündig |

### Toolbar (über der Tabelle)

```html
<div class="table-toolbar">
    <input type="search" placeholder="Suchen…">
    <select>
        <option>Alle Plattformen</option>
        <option>Google</option>
    </select>
</div>
<table class="data-table">…</table>
```

## 7. Cards

```html
<article class="card">
    <div class="card__header">
        <h3>Titel</h3>
        <div class="card__actions">
            <button class="btn-tertiary btn--sm">Bearbeiten</button>
        </div>
    </div>
    <p>Card-Inhalt</p>
</article>
```

### Sporeprint-Architektur-Hinweis: Hub vs. Sub-Dashboard

**Wichtig — wo wir uns von production-app unterscheiden:**

production-app ist als **Sub-Dashboard-Layout** aufgebaut: eine Page (z.B. Wareneingang) hat **View-Tabs** intern, in denen unterschiedliche Funktionen erscheinen. Dafür hat production-app `.action-tile` (Outline-Style) als Sektions-interne Quick-Access.

Sporeprint ist als **flacher Hub** aufgebaut: Dashboard zeigt 6 Top-Level-Bereiche (Reviews, Antworten, Analytics, Widget-Konfig, QR-Code, Shop-Switcher), jeder führt auf eine **eigene Sub-Page** mit der vollständigen Funktion. Keine View-Tabs.

**Konsequenz:** Sporeprint nutzt **`.hub-tile`** (eigenständige Komponente, gefüllter Blau-Dark-Style) statt production-app's `.action-tile`. Die Hub-Tiles sind die **zentrale Top-Level-Navigation** — daher mehr visuelles Gewicht (filled statt outline). Dokumentation siehe direkt nach den Card-Patterns unten.

**Lehre:** Production-app als Pattern-Quelle nutzen, aber **bewusst** abweichen wo Sporeprint anders strukturiert ist. Nicht blind übernehmen.

### Stub-Variante (Card)

**Stub-Variante** (für Phase-3-Vorab-Karten — eigenständige Card auf Canvas):

```html
<article class="card card--stub">
    <h3>Reviews-Übersicht</h3>
    <p class="text-muted">Filter, antworten, …</p>
</article>
```

`card--stub` → gestrichelter Border, Cream-Background, automatischer italic muted "Kommt in Phase 3"-Marker unten.

### Card-Layout-Entscheidung — eigenständig vs. Wrapper

Zwei Patterns je nach Inhalt:

| Pattern | Wann |
|---------|------|
| **Eigenständige Cards** (`.card` direkt im Section-Container, mit `subsection-header` als Section-Titel) | Übersichts-/Hub-Karten die jeweils einen eigenen Funktions-Bereich darstellen — z.B. Dashboard-Funktions-Übersicht, Settings-Bereiche. Jede Karte ist visuell eigenständig. |
| **Card-Wrapper mit Tiles drin** | Statistik-Sektionen, Sub-Dashboards mit zusammengehörigen Mini-Items (z.B. "Heute / Diese Woche / Diesen Monat" als Tiles innerhalb einer Stats-Card). Hier wäre Card-in-Card visuell zu schwer. |

**Standardfall ist eigenständige Cards** — der Wrapper-Pattern kommt nur wenn die Sub-Items so klein sind, dass sie eigene Cards optisch dominieren würden.

### Card als Section-Wrapper (für Sub-Inhalte mit Grid)

**Wann:** Mehrere zusammengehörige Sub-Items werden in einem Section-Block gruppiert. Statt jedes Item zu einer eigenen Card zu machen (Card-in-Card-Verschachtelung mit doppeltem Border/Shadow): die ganze Section wird zur `.card`, die Sub-Items werden zu `.tile`-Komponenten innen.

```html
<section class="card">
    <header class="card__header">
        <h2>Funktions-Bereiche</h2>
    </header>
    <div class="grid grid--2">
        <article class="tile tile--stub">
            <h3>Reviews-Übersicht</h3>
            <p>Filter nach Plattform, Shop, …</p>
        </article>
        <article class="tile tile--stub">
            <h3>Antworten verwalten</h3>
            <p>Reply auf Google / Trustpilot …</p>
        </article>
        <!-- weitere Tiles -->
    </div>
</section>
```

**Tile-Eigenschaften:**
- Cream-Background statt weiß (visuelle Hierarchie: Card-weiß außen, Tile-cream innen)
- 1px Border, kein Shadow (im Gegensatz zur Card mit `--shadow-card`)
- `.tile--stub` analog `.card--stub` — gestrichelter Border + italic muted "Kommt in Phase 3"-Marker

**Anti-Pattern:** Niemals `.card .card` direkt verschachtelt — verdoppelt visuelle Schwere und wirkt unruhig. Wenn doppelte Schichtung nötig: äußere Schicht ist Card mit `__header`, innere Items sind Tiles.

## 7b. Hub-Tiles (`.hub-tile`)

**Sporeprint-spezifisch** — siehe Architektur-Hinweis oben.

### Zweck

Top-Level-Navigation auf dem Dashboard. Jeder Tile führt auf eine eigene Sub-Page (Reviews, Antworten, Analytics, etc.). Visuell prominent — dem User wird sofort klar: das ist eine klickbare Aktion, nicht ein Content-Block.

### Markup

```html
<a href="/reviews.php" class="hub-tile">
    <h3>Reviews-Übersicht</h3>
    <p>Filter nach Plattform, Shop, Sternanzahl, Datum.</p>
</a>
```

Wird üblicherweise in einem `.grid.grid--2` (Desktop) oder `.grid--3` angeordnet.

### Stub-Variante

Für Phase-3-Vorab-Tiles (Funktion noch nicht gebaut):

```html
<article class="hub-tile hub-tile--stub" aria-disabled="true">
    <h3>Analytics</h3>
    <p>Wachstumsgraph, Funnel, Durchschnitt pro Plattform.</p>
</article>
```

`hub-tile--stub` rendert mit cream-Background, gestricheltem Border, dezentem italic "Kommt in Phase 3"-Marker, `pointer-events: none` (nicht klickbar). Wechsel auf `<a>`-Element + entfernen der Stub-Klasse aktiviert die Page sobald sie gebaut ist.

### Styling-Details

| Eigenschaft | Aktiv | Stub |
|-------------|-------|------|
| Background | Blau-Dark | Cream |
| Text | Weiß | Dark + Muted |
| Border | keine | gestrichelt 1.5px |
| Shadow | Card-Shadow | keiner |
| Hover | wechselt zu Orange + leichter Lift | nicht klickbar |
| Min-Height | 140px | 140px |

### Wann NICHT Hub-Tile sondern Card

| Use-Case | Komponente |
|----------|-----------|
| Top-Level-Navigation auf Hub-Page | `.hub-tile` |
| Content-Block mit Daten/Tabelle/Form | `.card` |
| Section-Heading mit Sub-Funktionen, intern | `.card.card--stub` |
| Mini-Items in einer größeren Card | `.tile` (siehe oben) |

## 8. Layout

### App-Header

Auf jeder eingeloggten Admin-Seite. Brand links, Nav in Mitte, User+Logout rechts.

```html
<header class="app-header">
    <a href="/dashboard.php" class="app-header__brand">Sporeprint</a>
    <nav class="app-header__nav">
        <a href="/dashboard.php" class="is-active">Dashboard</a>
        <a href="/reviews.php">Reviews</a>
    </nav>
    <div class="app-header__user">
        <span>admin</span>
        <a href="/logout.php">Logout</a>
    </div>
</header>
```

`.is-active` markiert den aktuellen Nav-Eintrag (Orange).

### App-Main

```html
<main class="app-main">
    <div class="page-header">
        <h1>Reviews</h1>
        <div class="page-header__actions">
            <button class="btn-secondary btn--sm">Export CSV</button>
            <button class="btn-primary btn--sm">Neue Antwort</button>
        </div>
    </div>
    <!-- Inhalt -->
</main>
```

Container-Max-Width: `--container-max-width` (1080px). Zentriert via `margin: 0 auto`.

### Login-Layout

Eigene `.login-page`-Klasse auf `<body>` → zentriertes Card-Layout, kein App-Header.

## 9. Callouts

Hervorgehobene Hinweis-Boxen (statt `alert()` o.ä.):

```html
<div class="callout callout--info">
    <strong>Info:</strong> Trustpilot-API wartet noch auf Freigabe.
</div>

<div class="callout callout--success">
    Antwort erfolgreich an Google gepostet.
</div>

<div class="callout callout--warning">
    <strong>Achtung:</strong> Cron-Job ist seit 24h nicht gelaufen.
</div>

<div class="callout callout--error">
    <strong>Fehler:</strong> Datenbank-Verbindung fehlgeschlagen.
</div>
```

## 10. Toast (Notification, oben rechts)

JS-API folgt in Phase 3 (gemeinsam mit ersten POST-Endpoints). CSS ist vorbereitet:

```html
<div class="toast-container">
    <div class="toast toast--success">
        Antwort gepostet.
        <button class="toast__close">×</button>
    </div>
</div>
```

Varianten: `--success` (grün), `--error` (rot), `--info` (blau), `--warning` (orange).

**Harte Regel:** `alert()` ist in Sporeprint **verboten**. Stattdessen entweder Callout (statisch in der Page) oder Toast (dynamisch nach Aktion). Übernimmt sich aus production-app — siehe DESIGN-SYSTEM.md dort Sektion 7e für ausführliche Begründung.

## 11. Status-Block

Spezial-Komponente für Foundation-Stand-Anzeige (aktuell nur auf Dashboard). Items mit Status-Indikator-Icon:

```html
<section class="status-block">
    <h2>Phase 1 — Foundation</h2>
    <ul>
        <li class="status-ok">Backend-Helper live</li>
        <li class="status-pending">API-Antrag wartet</li>
        <li class="status-error">Cron läuft nicht</li>
    </ul>
</section>
```

## 12. Reference-Implementation (Copy-Vorlagen für neue Pages)

Drei Skelette zum 1:1-Kopieren wenn eine neue Page angelegt wird. **Niemals von Null anfangen** — immer von einer dieser Vorlagen aus.

### 12.1 Standard-Admin-Page (mit Login + App-Header)

```php
<?php
declare(strict_types=1);

// Sporeprint Admin — <Page-Beschreibung>.
// <Was diese Page macht — 1-2 Sätze>

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';

requireLogin();

$user = currentUser();

// === Daten laden ===
// $rows = dbQueryAll("SELECT ...");

?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sporeprint Admin — <Page-Titel></title>
<link rel="stylesheet" href="/assets/admin.css">
</head>
<body>

<header class="app-header">
    <a href="/dashboard.php" class="app-header__brand">Sporeprint</a>
    <nav class="app-header__nav">
        <a href="/dashboard.php">Dashboard</a>
        <a href="/<aktuelle-page>.php" class="is-active"><Nav-Label></a>
    </nav>
    <div class="app-header__user">
        <span><?= htmlspecialchars($user ?? '') ?></span>
        <a href="/logout.php">Logout</a>
    </div>
</header>

<main class="app-main">
    <div class="page-header">
        <h1><Page-Titel></h1>
        <div class="page-header__actions">
            <!-- optional: Action-Buttons rechts oben -->
            <button class="btn-secondary btn--sm">Sekundär-Aktion</button>
            <button class="btn-primary btn--sm">Haupt-Aktion</button>
        </div>
    </div>

    <!-- Page-Inhalt: nur Standard-Komponenten aus components.css nutzen.
         Niemals <style> Inline-Block, niemals style="..."-Attribute. -->

    <section class="section">
        <div class="subsection-header">
            <h2>Sektion-Titel</h2>
        </div>
        <!-- .card / .data-table / .callout / etc. -->
    </section>

</main>

</body>
</html>
```

**Pflicht-Checks für neue Page:**
- `requireLogin();` als allererste Zeile nach den `require_once`-Aufrufen
- `<title>` enthält "Sporeprint Admin" + Page-Bezeichnung
- `<meta name="robots" content="noindex, nofollow">` (Admin nie indexiert)
- `<link rel="stylesheet" href="/assets/admin.css">` als einziges Stylesheet
- Nav-Eintrag mit `is-active` für die aktuelle Page
- Alle UI-Strings mit Umlauten

### 12.2 Login-Page (zentriertes Layout, kein App-Header)

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';

if (currentUser() !== null) {
    header('Location: /dashboard.php');
    exit;
}

// ... Login-Logik mit CSRF-Check ...

?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sporeprint Admin — Login</title>
<link rel="stylesheet" href="/assets/admin.css">
</head>
<body class="login-page">

<main class="login-card">
    <h1 class="login-card__brand">Sporeprint</h1>
    <p class="login-card__subtitle">Admin-Bereich</p>

    <!-- Optional: Error-Callout -->
    <div class="callout callout--error">…</div>

    <form method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="…">

        <div class="form-row">
            <label for="username">Benutzername</label>
            <input type="text" id="username" name="username" autofocus required>
        </div>

        <div class="form-row">
            <label for="password">Passwort</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary btn--block">Einloggen</button>
        </div>
    </form>

    <p class="login-card__footer">Sporeprint &middot; intern</p>
</main>

</body>
</html>
```

**Charakteristisch:** `<body class="login-page">` (zentriertes Cream-BG-Layout statt App-Header), `.login-card` als Container.

### 12.3 Public-Stub (sporeprint.pilzling.eu/)

```php
<?php
declare(strict_types=1);

// Sporeprint Public — Platzhalter-Index.

http_response_code(200);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Sporeprint</title>
<style>
/* === PUBLIC-STUB INLINE-CSS — bewusst kein Cross-Subdomain-Asset-Sharing ===
 * Bei Änderungen in admin/assets/tokens.css die hier genutzten Werte
 * manuell nachziehen. Public-Stub wird selten geändert.
 */
body {
    font-family: "Rubik", system-ui, sans-serif;
    background: #F2F0ED;       /* --color-cream */
    color: #151824;            /* --color-dark */
    /* ... weitere Tokens 1:1 aus tokens.css ... */
}
</style>
</head>
<body>
<h1>Sporeprint</h1>
<!-- minimaler Content -->
</body>
</html>
```

**Charakteristisch:** Inline-CSS (nicht aus `admin.css` weil andere Subdomain), aber Werte exakt aus `tokens.css` kopiert mit Sync-Pflicht-Kommentar.

### Anti-Patterns (NIEMALS machen)

- ❌ Inline-`<style>`-Block in einer Admin-Page (außer Login? **Auch da nicht**, ist `.login-card` in `layout.css`)
- ❌ `style="..."`-Attribute irgendwo
- ❌ Eigene page-spezifische CSS-Datei (z.B. `admin/dashboard.css`) — alles in `components.css`
- ❌ Hardcoded Farb-Hex-Werte oder Pixel-Spacings in Admin-PHP — immer `var(--token)`
- ❌ Endpoint ohne `requireLogin()` als allererste Zeile (Admin) bzw. `enforcePublicApiHardening()` (Public)
- ❌ HTML-Output mit ASCII-Substitutionen (`fuer`, `moeglich`, etc.) — siehe Sektion 5b

### Wenn die Vorlagen nicht reichen

Wenn eine neue Page-Art entsteht die in keinem der drei Skelette passt (z.B. Print-View, Embed-Iframe, Admin-Modal-Lightbox): **erst überlegen ob das System sie wirklich braucht**, dann das Skelett **hier in DESIGN-SYSTEM.md ergänzen** als 12.4 — bevor der Code geschrieben wird. Doku-First.

## Was kommt später (Phase 3+)

- **Modal-Framework** — wenn Reply-Funktion gebaut wird (POST-Modale, Form-Edit-Pattern)
- **View-Tabs** — wenn Reviews nach Plattform/Shop in Tabs gruppiert werden
- **Stats-Cards** — wenn Analytics-Seite mit Counter-Anzeigen
- **Inline-Actions-Standard** — wenn Reviews-Tabelle Edit/Delete/Reply Inline-Buttons hat
- **Pagination-JS** — bei größeren Reviews-Listen (`> 50 Zeilen`)
- **Toast-JS-API** — gleichzeitig mit ersten POST-Aktionen

Wenn diese Komponenten gebaut werden: das Pattern aus `production-app/docs/DESIGN-SYSTEM.md` als Vorlage übernehmen, in `components.css` ergänzen, hier dokumentieren.

## Design-Drift-Prävention

Bei jedem PR / Commit der UI-Code anfasst:
- [ ] Keine Inline-Styles in PHP
- [ ] Keine neuen Custom-Properties außer in `tokens.css`
- [ ] Neue Komponenten in `components.css` UND hier dokumentiert
- [ ] Keine doppelten Komponenten ähnlicher Funktionalität (z.B. zwei Card-Stile)
- [ ] Klassennamen folgen BEM-light: `.komponente`, `.komponente__teil`, `.komponente--variante`

## Pattern-Quelle

production-app `docs/DESIGN-SYSTEM.md` (~2600 Zeilen). Sporeprint nutzt das **kondensierte Subset**. Bei Erweiterung: Original konsultieren, Pattern adaptieren, hier dokumentieren.
