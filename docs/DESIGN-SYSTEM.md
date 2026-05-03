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

## 2. Typografie

System-Font-Stack, keine externen Fonts. Schrift-Hierarchie:

| Element | Font-Size-Token | Verwendung |
|---------|----------------|------------|
| `h1` | `--font-size-2xl` (1.6rem) | Page-Title |
| `h2` | `--font-size-xl` (1.4rem) | Section-Title |
| `h3` | `--font-size-lg` (1.2rem) | Card-Title, Subsection |
| Body | `--font-size-base` (0.9rem) | Default |
| Labels / Meta | `--font-size-sm` (0.85rem) | Form-Labels, Captions |
| Chips, Captions | `--font-size-xs` (0.72rem) | Status-Chips, kleine Indikatoren |

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
<span class="chip chip--green">Visible</span>
<span class="chip chip--orange">Pending</span>
<span class="chip chip--red">Error</span>
<span class="chip chip--blue">Info</span>
<span class="chip chip--peach">Bestellt</span>
<span class="chip chip--gray">Storniert</span>
```

| Variante | Wann |
|----------|------|
| `chip--green` | Erfolg, OK, sichtbar, geliefert |
| `chip--orange` | Pending, offen, in Arbeit, neue Review |
| `chip--blue` | Info, exportiert, in Sync |
| `chip--peach` | Bestellt, erwartet |
| `chip--red` | Fehler, storniert, geflagged |
| `chip--gray` | Inaktiv, neutral, archiviert |

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

**Stub-Variante** (für Phase-3-Vorab-Karten):

```html
<article class="card card--stub">
    <h3>Reviews-Übersicht</h3>
    <p class="text-muted">Filter, antworten, …</p>
</article>
```

`card--stub` → gestrichelter Border, Cream-Background, automatisches "Phase 3 — kommt"-Badge unten rechts.

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
