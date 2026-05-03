# CI-Refactoring — alle Pages auf Design-System v2

**Erstellt:** 2026-05-03
**Vorgaenger:** Phase 1 Backend-Foundation + Design-System v2 (Commits bis `5a6239d`)
**Stufe:** 3 (Detailplan) — Implementierung
**Roadmap-Bezug:** [ROADMAP.md](ROADMAP.md) → Phase 1 (Sub-Phase 1.9)

## Ziel

Vollstaendige Konsistenz aller existierenden PHP/HTML/JS-Pages mit dem Design-System v2 + Sprach-Standard. Zwei Drift-Klassen ausloeschen:

1. **ASCII-Substitutionen** wo Umlaute hingehoeren (mein Fehler beim Schreiben der Files — habe `fuer/moeglich/Stueck` etc. genutzt weil ich faelschlicherweise dachte "PHP-File = Code = ASCII"). Korrekt ist: ASCII **nur** in Code-Identifiern (Variablen, Funktionen, DB-Spalten, Datei-/Ordnernamen). UI-Strings, Code-Kommentare, Doku, Commit-Messages bekommen Umlaute.

2. **Inline-CSS in Public-Pages** + ein **klobiges Card-Stub-Badge**. Refactor auf Standard-Komponenten.

Nach dem Refactor: jeder Page-Render lebt durchgaengig im Design-System, keine Drift, keine Doppelungen, alles SSOT.

## Ausgangslage (Audit-Tabelle)

### A1 — UI-Strings mit ASCII-Substitution (sichtbar fuer User)

| Datei | Zeile | Aktuell | Zielt auf |
|-------|-------|---------|-----------|
| `src/admin/dashboard.php` | 97 | `QR-Codes fuer Verpackung, Marktstand, etc. — fuehren direkt zur Bewertungsseite.` | `fuer` → `fuer` ❌ → `fuer` ❌ — sorry, Markdown Auto-Replace: gemeint ist `für` und `führen` |
| `src/admin/dashboard.php` | 80 | `Reviews-Uebersicht` | `Reviews-Übersicht` |
| `src/admin/dashboard.php` | 88 | `Antworten verwalten` (text "zurueckpushen") | `zurückpushen` |
| `src/admin/dashboard.php` | 96 | `fuer Verpackung` + `fuehren` | `für Verpackung` + `führen` |
| `src/admin/dashboard.php` | 100 | `Oberflaeche, Multi-Tenant` | `Oberfläche, Multi-Tenant` |
| `src/admin/index.php` | 32 | `Login nicht moeglich. Pruefe Benutzername und Passwort.` | `Login nicht möglich. Prüfe Benutzername und Passwort.` |
| `src/public/widget-test.html` | 62 | intro: `fuers Widget` | `fürs Widget` |
| `src/public/widget.js` | 61, 64 | `Kraeuterseitlinge` (in Mock-Reviews) | `Kräuterseitlinge` |

### A2 — Code-Kommentare mit ASCII-Substitution (Doku-Drift)

Alle PHP-Dateien haben Kommentare mit `fuer/koennte/laeuft/ueberschritten/etc.`. Komplette Liste betroffener Dateien:

- `src/admin/dashboard.php` (Z. 16)
- `src/admin/index.php` (eingebettet)
- `src/admin/logout.php` (klein)
- `src/config/database.php` (Z. 5)
- `src/lib/auth.php` (mehrere)
- `src/lib/db.php` (Z. 14, 25, 37)
- `src/lib/helpers.php` (Z. 7, 15, 34, 58, 63, 112)
- `src/lib/public_api_guard.php` (Z. 23, 40, 67, 74)
- `src/lib/rate_limit.php` (Z. 10, 18, 21)
- `src/public/api/reviews.php` (Z. 4, 10, 46)
- `src/public/index.php`
- `src/public/widget-test.html` (Z. 81, JS-Kommentar)
- `src/public/widget.js` (mehrere)
- `src/_tools/cron-cleanup-rate-limits.php` (Z. 11)
- `src/admin/_setup`-Dateien — eh geloescht, OK

Sowie alle Dateien in `_db/`, `_tools/` und `docs/`. Kommentare in JS-Files (`format.js`, `widget.js`).

### A3 — Inline-CSS / `<style>`-Bloecke (gegen SSOT-Prinzip UI)

| Datei | Issue | Plan |
|-------|-------|------|
| `src/public/index.php` | Eigener `<style>`-Block mit dupliziertem Token-Subset | Refactor — siehe Phase 4 |
| `src/public/widget-test.html` | Eigener `<style>`-Block + Inline-Form-Styles | Refactor + ggf. Verschiebung — siehe Phase 4 |

### A4 — Komponenten-Drift / Visual-Issues

| Element | Issue | Fix |
|---------|-------|-----|
| `.card--stub::after` Badge "Phase 3 — kommt" | Mit Border + festem Background wirkt es wie ein Button (wie im Screenshot vom User) | Re-Design: dezenter Marker — nur muted Text mit Hintergrund-Tint, kein Border, keine Uppercase-Trennung |
| Card-Titel-Schriftgroesse vs. h1 | `.card h3` ist `--font-size-lg` (1.2rem), wirkt im Verhaeltnis zur h1 (1.6rem) und zu den Stub-Cards eher massig | Pruefen — ggf. auf `--font-size-md` herunter |
| `.app-header__nav` aktuell nur ein Eintrag "Dashboard" | Kein Refactor noetig, aber Nav-Eintraege fuer kommende Pages vorbereiten (Reviews, Antworten) als Stub mit `disabled`-State? | Optional in Phase 5 |

### A5 — Fehlende Konventions-Zuordnung

- DESIGN-SYSTEM.md beschreibt das **System**, aber zeigt kein **konkretes Beispiel** wie eine Standard-Page aufgebaut ist. Eine Reference-Implementation-Sektion fehlt — als visuelles "so sieht ein Page-Skelett aus, hier ist der Code dazu".
- CLAUDE.md "Sprache + Schreibweise"-Sektion erwaehnt zwar "ASCII nur in Code-Variablennamen", aber war nicht klar genug fuer mich — heisst: ich habe die Regel falsch interpretiert. Praezisierung noetig.

## Konventionen / Harte Regeln (greifen sofort beim Refactor)

### Schreibweise — ASCII vs. Umlaute (verschaerft)

**ASCII (nur in technischen Identifiern):**
- PHP-/JS-Variablennamen, Funktionsnamen, Klassennamen
- DB-Tabellen-/Spalten-Namen
- Datei-/Ordnernamen
- CSS-Klassennamen
- HTTP-Header-Namen, ENV-Variablen, JSON-Keys (API-Felder)
- URL-Parameter

**Umlaute (ueberall sonst, kein Kompromiss):**
- HTML-Output / `echo`-Strings / Page-Inhalt
- UI-Labels, Button-Texte, Form-Placeholder, Form-Help-Texte
- Error-Messages (`apiError("Bestellung nicht gefunden")`)
- HTML-Title, Meta-Description, Alt-Texte
- Page-Comments fuer User (z.B. Toast-Texte)
- **Code-Kommentare** (`// Dies ist eine Erklaerung fuer Entwickler:innen`)
- Doku-Files (`.md`)
- Commit-Messages
- Plan-Files, Konzept-Dokumente

**Eindeutiger Test:** Wenn der Text **theoretisch** von einem Menschen gelesen werden koennte (egal ob Endnutzer:in oder Entwickler:in beim Code-Review) → Umlaute. Wenn der Text als String von der Programmiersprache/Datenbank/URL **interpretiert** wird → ASCII (auch wenn er sich liest wie deutsches Wort).

Pre-Commit-Hook (`_tools/check_umlauts.py`) erzwingt das fuer `.md`-Files und Commit-Messages — fuer PHP/JS gilt's manuell + bei Code-Review.

### Standard-Page-Struktur (zur Vermeidung kuenftiger Drift)

Jede Admin-Page hat dieses Skelett:

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
requireLogin();
// ... Datenladen, Logik ...
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
        <a href="/dashboard.php" class="<is-active wenn dashboard.php>">Dashboard</a>
        <!-- weitere Nav-Punkte -->
    </nav>
    <div class="app-header__user">
        <span><?= htmlspecialchars($user ?? '') ?></span>
        <a href="/logout.php">Logout</a>
    </div>
</header>
<main class="app-main">
    <div class="page-header">
        <h1>Page-Titel</h1>
        <div class="page-header__actions"><!-- optional --></div>
    </div>
    <!-- Page-Inhalt mit .card / .data-table / .callout / etc. — alles aus components.css -->
</main>
</body>
</html>
```

Login-Page ist die Ausnahme (kein App-Header, eigenes `.login-page`-Layout).

Public-Pages (`public/index.php`) sind Stubs — minimale Struktur, kein Login, eigene minimale CSS (Phase 4).

## Scope — was in diesem Plan ist

**Drin:**
- Komplett-Refactor aller existierenden Pages auf Design-System v2 + Sprach-Standard
- Card-Stub-Badge re-designen
- Public-Pages-CSS-Strategie klaeren + umsetzen
- DESIGN-SYSTEM.md um Reference-Implementation-Sektion ergaenzen
- CLAUDE.md "Sprache + Schreibweise" praezisieren
- Verifikations-Greps die kuenftig Drift erkennen

## NICHT in diesem Plan (bewusste Auslassungen)

| Was | Grund |
|-----|-------|
| Neue Komponenten in `components.css` | nur was vorhanden ist auf konsistente Nutzung pruefen |
| Modal-Framework / View-Tabs / Stats-Cards | Phase 3 territorialer |
| API-Anbindung (Google/Trustpilot) | warten auf Anbieter-Freigaben |
| Production-app `.htaccess`-Fix (cp:ppd-Block) | macht User in separater Session, ist hier nicht in scope |
| Widget Public-Sub-Domain CSS-Standardisierung mit Pilzling-CI fuer Endkund:innen-Look | bewusst eigenstaendig (siehe ARCHITEKTUR.md — Widget muss in fremden JTL-Templates konfliktfrei laufen, eigene `.sporeprint-*`-Namespaces). Texte in widget.js aber Umlaut-fixen. |

## Phase 0 — Docs-Review + Konventions-Praezisierung

**Ziel:** Bevor Code refactored wird, die Konvention so klar formulieren dass es kein zweites Mal passiert.

### Konkrete Aenderungen

1. **`CLAUDE.md` Sektion "Sprache + Schreibweise" erweitern:**
   - Klare Tabelle "ASCII (technische Identifier) vs. Umlaute (alles andere inkl. Code-Kommentare)"
   - Eindeutiger Test fuer Grenzfaelle: "Theoretisch fuer Mensch lesbar? → Umlaute. Wird vom System interpretiert? → ASCII."
   - Konkrete Beispiele was wo gilt

2. **`docs/DESIGN-SYSTEM.md` Sektion 5b ("Sprache, Schreibweise, Tone") erweitern:**
   - Selbe ASCII-vs-Umlaute-Tabelle (DESIGN-SYSTEM ist UI-fokussiert, CLAUDE-Regel ist allgemein)

3. **`docs/DESIGN-SYSTEM.md` neue Sektion "Reference-Implementation":**
   - Komplettes Beispiel-PHP-Skelett wie oben unter "Standard-Page-Struktur"
   - Login-Page als Beispiel fuer Login-Layout
   - Dashboard-Page als Beispiel fuer Standard-Layout

4. **`MEMORY.md` Eintrag:** "ASCII-Drift in PHP-Files am 2026-05-03 als Folge falscher Konvention-Interpretation aufgeraeumt — Konvention in CLAUDE.md praezisiert, kuenftig: ASCII nur in Identifiern."

**Akzeptanzkriterium 0:** CLAUDE.md hat eindeutige Tabelle, DESIGN-SYSTEM.md hat Reference-Implementation, MEMORY.md hat Eintrag. Kein Code-Refactor noch.
**Commit-Ende 0:** `docs: Konventions-Praezisierung Sprache+Schreibweise (Phase 1.9.0)`

## Phase 1 — Umlaute-Fix in allen Page-UI-Strings

**Ziel:** Alle sichtbaren UI-Strings in PHP-/HTML-/JS-Pages haben korrekte Umlaute.

### Files (UI-Strings, oberste Prio)

1. **`src/admin/dashboard.php`:**
   - `Phase 1 — Foundation` Status-Block: alle Items auf Umlaute (`fuer` → `für` etc.)
   - Card-Titel: `Reviews-Uebersicht` → `Reviews-Übersicht`
   - Card-Beschreibungen: alle Saetze
   - HTML-Title-Tag

2. **`src/admin/index.php`:**
   - `Login nicht moeglich. Pruefe Benutzername und Passwort.` → `Login nicht möglich. Prüfe Benutzername und Passwort.`
   - HTML-Title-Tag, Form-Labels, Footer-Text

3. **`src/admin/logout.php`:**
   - Nur Code-Kommentare (kein UI)

4. **`src/public/widget.js`:**
   - Mock-Reviews: `Kraeuterseitlinge` → `Kräuterseitlinge`
   - Andere Mock-Texte ueberpruefen
   - Footer-Tooltip-Text "Sporenabdruck" — schon mit Umlauten? pruefen

5. **`src/public/widget-test.html`:**
   - Intro-Text auf Umlaute
   - Hinweis-Kasten auf Umlaute

6. **`src/public/index.php`:**
   - Public-Stub-Texte auf Umlaute

**Akzeptanzkriterium 1:** Browser-Aufruf jeder Page zeigt nur Umlaute, keine ASCII-Substitutionen mehr. Verifikations-Grep `rg "fuer|moeglich|Stueck|Aenderung..." src/` zeigt 0 Treffer in UI-Output-Strings (Code-Kommentare in Phase 2).
**Commit-Ende 1:** `fix(ui): Umlaute in allen Page-UI-Strings (Phase 1.9.1)`

## Phase 2 — Umlaute-Fix in allen Code-Kommentaren

**Ziel:** Alle PHP-/JS-/CSS-Kommentare auf korrekte Umlaute.

### Files

Komplett-Liste aus Audit A2:

- `src/admin/dashboard.php`
- `src/admin/index.php`
- `src/admin/logout.php`
- `src/config/database.php`
- `src/lib/auth.php`
- `src/lib/db.php`
- `src/lib/helpers.php`
- `src/lib/public_api_guard.php`
- `src/lib/rate_limit.php`
- `src/public/api/reviews.php`
- `src/public/index.php`
- `src/public/widget.js`
- `src/public/widget-test.html` (JS-Kommentare im `<script>`-Block)
- `src/_tools/cron-cleanup-rate-limits.php`
- `src/admin/assets/format.js`
- `src/admin/assets/admin.css` + `tokens.css` + `base.css` + `layout.css` + `components.css` (CSS-Kommentare)

**Akzeptanzkriterium 2:** Verifikations-Grep `rg "fuer|moeglich|Stueck|Aenderung|naechst|laesst|haeuf|gehoer|spaet|muess|koenn|fuehr|haet|oeff|waehl|erklaer|ausfueh|vollstaend|durchlaeuf|einfueh|ergaenz|spaeter|geprueft|gemaess|haert|verfuegbar|aerg|ungueltig|nuetzlich|noet" src/` (case-insensitive) zeigt 0 Treffer.
**Commit-Ende 2:** `fix(comments): Umlaute in allen Code-Kommentaren (Phase 1.9.2)`

## Phase 3 — Card-Stub-Badge re-design

**Ziel:** `.card--stub` mit dezenterem visuellem Marker — nicht wie ein Button-Etikett.

### Aktueller Zustand

```css
.card--stub::after {
    content: "Phase 3 — kommt";
    margin-top: var(--space-3);
    padding: 0.15rem 0.5rem;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    /* ... */
}
```

### Geaenderter Zustand

```css
.card--stub::after {
    content: "Kommt in Phase 3";
    display: block;
    margin-top: var(--space-3);
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
    font-style: italic;
    /* kein border, kein background, keine uppercase */
}
```

Zusaetzlich **Hover-Behavior**: card--stub ist visuell etwas dezenter (z.B. Opacity 0.85), kein klickbares Verhalten.

**Akzeptanzkriterium 3:** Browser-Test: Dashboard zeigt 6 Stub-Cards mit dezentem italic-Marker statt klobigem Badge. Visual-Vergleich ist klarer.
**Commit-Ende 3:** `style(card-stub): Badge dezenter (Phase 1.9.3)`

## Phase 4 — Public-Pages CSS-Strategie

**Ziel:** Inline-CSS aus Public-Pages raus, Pilzling-Look konsistent.

### Strategie

**Option A — gewaehlt:** Verschiebung + leichte Auslagerung.

1. **`widget-test.html` ist eine interne Test-Seite** → nach `src/admin/widget-test.php` (PHP statt HTML, `requireLogin()` als erste Zeile, hinter Verzeichnisschutz). Nutzt admin.css. Bleibt eingebettet aufrufbar via `https://admin-sporeprint.pilzling.eu/widget-test.php`.

2. **`public/index.php` bleibt minimaler Public-Stub** — aber mit eigener kleiner `public/assets/style.css` die nur die noetigen Tokens + Body-Style enthaelt. Wird beim Setup einmal aus `admin/assets/tokens.css` + Body-Snippet abgeleitet, danach einfacher manueller Sync-Pflicht-Check.

   Alternativ: **Inline-CSS akzeptieren** weil Public-Stub praktisch nie geaendert wird und nur eine Page ist. Kommentar im File markiert "Stub-Inline-CSS — bei Aenderung in admin/assets/tokens.css nachpflegen".

   **Empfehlung Option A:** Inline-CSS akzeptieren, Kommentar drin. Kein eigenes File anlegen, weil Aufwand > Nutzen.

3. **Widget-Loader + widget.js** bleiben unveraendert — eigene Namespaces (`.sporeprint-*`), self-contained inline-CSS in JS. Bewusste Architektur-Entscheidung (siehe ARCHITEKTUR.md).

### Aenderungen

1. `src/public/widget-test.html` → loeschen, ersetzen durch `src/admin/widget-test.php`
   - PHP-Datei mit `requireLogin()` als erste Zeile
   - Standard-Page-Skelett (App-Header, App-Main, page-header)
   - admin.css fuer Layout
   - Widget per `<script src="https://sporeprint.pilzling.eu/widget.js">` einbinden (cross-subdomain ok, Widget liefert eh via CORS)
   - Shop-Switcher-Buttons aus Standard-Komponenten (`.btn-secondary` mit `.is-active`-Variante? oder eigene `.shop-switcher`?)

2. `src/public/index.php` Inline-CSS minimal halten + Kommentar:
   ```php
   <style>
   /* Public-Stub — bei Aenderung in admin/assets/tokens.css nachpflegen.
      Bewusst inline weil Public-Page selten geaendert wird und kein
      Cross-Subdomain-CSS-Sharing noetig ist. */
   </style>
   ```
   Inline-Tokens identisch zu `tokens.css` halten (nur die wenigen genutzten).

**Akzeptanzkriterium 4:** Widget-Test ist hinter Verzeichnisschutz erreichbar, gleiches Layout wie Dashboard. Public-Stub `sporeprint.pilzling.eu/` zeigt Pilzling-Look (cream BG, dark Text, korrekte Schrift). Keine `<style>`-Bloecke in Admin-Pages.
**Commit-Ende 4:** `refactor(public): Widget-Test in Admin verschoben, Public-Stub konsolidiert (Phase 1.9.4)`

## Phase 5 — Reference-Implementation in DESIGN-SYSTEM.md

**Ziel:** DESIGN-SYSTEM.md zeigt konkretes Page-Skelett als Referenz, damit kuenftige Pages 1:1 davon abkupfern.

### Aenderungen

Neue Sektion **"12. Reference-Implementation"** in `docs/DESIGN-SYSTEM.md`:

- Komplettes Standard-Page-Skelett (PHP)
- Login-Page-Skelett
- Public-Stub-Skelett
- "Wie eine neue Page anlegen" — Checkliste

**Akzeptanzkriterium 5:** Sektion 12 in DESIGN-SYSTEM.md hat 3 vollstaendige Page-Skelette als Copy-Vorlage.
**Commit-Ende 5:** `docs(design-system): Reference-Implementation-Sektion (Phase 1.9.5)`

## Phase 6 — Verifikation (Drift-Check)

**Checks:**

1. **ASCII-Substitutions-Grep:**
   ```bash
   rg -i "fuer|moeglich|Stueck|Aenderung|naechst|haeuf|gehoer|spaet|muess|koenn|fuehr|haet|oeff|waehl|erklaer|ausfueh|vollstaend|geprueft|gemaess|verfuegbar|nuetzlich|noet" src/
   ```
   → Erwartung: 0 Treffer (oder nur in `.gitignore`-relevanten Pfaden)

2. **Inline-CSS-Grep:**
   ```bash
   rg "<style" src/admin/  # erwartet: 0 Treffer
   rg 'style="' src/admin/ src/public/  # erwartet: 0 Treffer
   ```

3. **Hardcoded-Color-Grep:**
   ```bash
   rg "#[0-9a-fA-F]{3,8}" src/admin/ src/public/ --type-not css  # erwartet: 0 in PHP/HTML
   ```

4. **`var(--`-Nutzung in CSS pruefen:** Tokens werden nur in `tokens.css` definiert, ueberall sonst nur referenziert.

5. **Browser-Tests:**
   - Login-Page: Cream BG, korrekte Rubik-Font, Umlaute drin, Card-Layout, Submit-Button
   - Dashboard: App-Header, Status-Block, 6 Stub-Cards mit dezentem Marker
   - Widget-Test (Admin-geschuetzt): Sporeprint-Layout, Shop-Switcher
   - Public-Stub: Cream BG, dark Text, kein Login-Style

6. **Doku-Konsistenz:**
   - DESIGN-SYSTEM.md Sektion 12 vollstaendig
   - CLAUDE.md "Sprache + Schreibweise" praezisiert
   - SSOT-Tabelle in CLAUDE.md zeigt aktuelle Doku-Files

**Akzeptanzkriterium 6:** Alle 6 Checks gruen. Falls Grep-Ergebnisse > 0: Datei + Zeile dokumentieren, im naechsten Commit nachfixen.
**Commit-Ende 6:** `chore: CI-Refactoring Verifikation (Phase 1.9.6)`

## Akzeptanzkriterien (Plan gilt als abgeschlossen wenn…)

- [ ] CLAUDE.md "Sprache + Schreibweise" hat eindeutige ASCII-vs-Umlaute-Tabelle + Test-Frage fuer Grenzfaelle
- [ ] DESIGN-SYSTEM.md Sektion 5b erweitert + neue Sektion 12 "Reference-Implementation" mit 3 Page-Skeletten
- [ ] Alle UI-Strings in `src/admin/*.php` und `src/public/*.php`/`*.html` haben Umlaute (kein `fuer`, `moeglich`, etc.)
- [ ] Alle Code-Kommentare in `src/`-PHP-/JS-/CSS-Files haben Umlaute
- [ ] `.card--stub::after` ist dezent (italic, muted, kein Border, kein Background)
- [ ] `widget-test.html` ist nach `admin/widget-test.php` verschoben (mit `requireLogin()`)
- [ ] `public/index.php` Inline-CSS hat Sync-Pflicht-Kommentar
- [ ] Verifikations-Greps zeigen 0 Treffer fuer ASCII-Substitutionen + Inline-CSS in Admin
- [ ] Browser-Tests: alle Pages konsistent im Pilzling-Look mit Rubik
- [ ] Plan in `_archive/_plans/` nach Abschluss verschoben

## Reihenfolge der Implementation

```
Phase 0 → Docs-Konvention praezisieren (kein Code-Refactor noch)
   ↓
Phase 1 → UI-Strings Umlaute-Fix (sichtbar fuer User, Prio)
   ↓
Phase 2 → Code-Kommentare Umlaute-Fix (alle PHP/JS/CSS-Files)
   ↓
Phase 3 → Card-Stub-Badge Re-Design
   ↓
Phase 4 → Widget-Test verschieben + Public-Stub konsolidieren
   ↓
Phase 5 → DESIGN-SYSTEM.md Reference-Implementation
   ↓
Phase 6 → Verifikation
```

Phase 1 + 2 koennen ggf. zusammengefasst werden in einem Commit pro Datei (UI + Kommentare auf einmal). Aber die Audit-Tabellen bleiben getrennt damit Tracking sauber ist.

Geschaetzter Aufwand insgesamt: 60-90 Minuten Implementierung + 15 Min Verifikation.

## Referenzen

- **Konzept:** [`2026-05-02-architektur-pivot-konzept.md`](2026-05-02-architektur-pivot-konzept.md)
- **Roadmap:** [`ROADMAP.md`](ROADMAP.md)
- **Phase-1-Plan:** [`2026-05-03-phase-1-backend-foundation.md`](2026-05-03-phase-1-backend-foundation.md)
- **Design-System:** `../docs/DESIGN-SYSTEM.md`
- **Architektur:** `../docs/ARCHITEKTUR.md`
- **Pattern-Quelle:** `C:\AI-Workspace\projects\dev\production-app\docs\DESIGN-SYSTEM.md`
- **Pre-Commit-Hook:** `_tools/check_umlauts.py`
