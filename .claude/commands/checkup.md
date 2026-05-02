# Checkup — Drift-Analyse Sporeprint

Sucht Abweichungen zwischen Dokumentation, Plänen und Realität im Sporeprint-Projekt. Findet Drift bevor er zum Problem wird.

## Checks

### 1. `docs/` vs. SSOT-Nachschlagewerk
- Alle Dateien in `docs/` listen
- Gegen die SSOT-Tabelle in `CLAUDE.md` abgleichen
- Verwaiste Dateien? Fehlende Einträge? Veraltete Verweise?
- Auch die "Aktive Docs-Anzahl" in CLAUDE.md prüfen

### 2. ROADMAP.md vs. `_plans/`
- Ist jeder Plan in `_plans/` auch in `ROADMAP.md` referenziert?
- Gibt es Konzept-Dokumente (`-konzept.md`) ohne Roadmap-Eintrag?
- Gibt es archivierte Pläne in `_archive/_plans/` die noch auf "in Arbeit" stehen?
- Zeigt "Aktueller Fokus" den realen Zustand?

### 3. ROADMAP.md vs. CLAUDE.md
- Zeigt der "Aktueller Fokus"-Abschnitt in CLAUDE.md die gleiche Phase wie in ROADMAP.md?
- Ist die "Aktuelle Phase" im Status-Block aktuell?

### 4. Konzept-/Plan-Konsistenz (3-Stufen-Methodik)
- Gibt es Detailpläne (`YYYY-MM-DD-*.md` ohne `-konzept`-Suffix) für komplexe Features ohne vorher abgeschlossenes Konzept?
- Sind Konzept-Dokumente noch in Stufe 1 (Konzept) oder steht der Pre-Check-Abschnitt noch leer obwohl sie als stabil markiert sind?
- Gibt es archivierte Pläne ohne den zugehörigen Konzept-Eintrag im Archiv?

### 5. MEMORY.md auf Stale-Einträge prüfen
- Gibt es Stolperstellen die längst gelöst sind?
- Gibt es überholte Entscheidungen (ersetzt durch neuere)?
- Externer Kontext noch aktuell (3 Shops, Brevo-Account, onlinereviews.tech-Status)?

### 6. Code vs. Docs
- Gibt es Code in `src/` der Patterns/APIs/KV-Keys nutzt, die nicht in `docs/ARCHITEKTUR.md` stehen?
- Gibt es Aussagen in `docs/ARCHITEKTUR.md` die nicht mehr stimmen (z.B. Komponenten die nicht so existieren)?

### 7. Referenzen auf Standard
- Verweist CLAUDE.md noch auf den korrekten Dev-Projekt-Standard (aktuell v2.0)?
- Bei Standard-Update auf Workspace-Ebene: ist die lokale CLAUDE.md mitgezogen?

## Ausgabe-Format

Je Check:
- **✅ ok** — keine Abweichung gefunden
- **⚠ Abweichung** — was genau + Empfehlung was zu tun wäre

Am Ende:
- Gesamtbewertung (ok / minor drift / major drift)
- Priorisierte Handlungsempfehlung

**Keine automatischen Änderungen.** Dieser Checkup zeigt nur den Zustand — Aktionen entscheidet der User.
