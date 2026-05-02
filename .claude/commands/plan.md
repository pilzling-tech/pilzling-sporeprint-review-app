# Plan — Neuen Detailplan anlegen (Stufe 3 der 3-Stufen-Methodik)

Erstellt einen neuen Feature-Plan unter `_plans/YYYY-MM-DD-<feature-name>.md` nach dem Dev-Projekt-Standard v2.0.

**Voraussetzung bei komplexen Features:** Es muss bereits ein **stabiles Konzept + abgeschlossener Pre-Check** vorliegen (`_plans/YYYY-MM-DD-<feature>-konzept.md`). Wenn nicht: zuerst `/konzept` nutzen.

**Für einfache Features** (1 Schicht, klar abgrenzbar, keine neuen Datenstrukturen): Detailplan direkt — Phase 0 deckt die Drift-Prüfung ab. Im Zweifel beim User nachfragen.

## Ablauf

1. **User nach Feature-Namen fragen** (kurz, beschreibend, Bindestriche statt Leerzeichen)
2. **Aktuelles Datum ermitteln** → Dateiname: `_plans/YYYY-MM-DD-<feature-name>.md`
3. **Prüfen ob ein Konzept-Dokument existiert** — wenn ja, Pre-Check-Drift-Liste daraus referenzieren (nicht wiederholen)
4. **Bestehende Pläne kurz anschauen** für Referenzen / Voraussetzungen
5. **Plan-Template anlegen** mit folgenden Pflicht-Abschnitten:

   - **Ziel** (1-3 Zeilen, was am Ende erreicht sein soll)
   - **Ausgangslage** (1 Absatz, aktueller Stand, warum jetzt)
   - **Verweis auf Konzept** (wenn vorhanden): `Konzept: _plans/YYYY-MM-DD-<feature>-konzept.md`
   - **Scope / NICHT in diesem Plan** (Vollständigkeits-Prinzip — bewusste Auslassungen mit Grund auflisten)
   - **Phase 0 — Docs-Review + Infrastruktur-Audit & Updates** (zweiteilig, IMMER erste Phase):
     - **Teil A — Docs-Review:** welche Doku-Dateien betroffen sind, welche Änderungen konkret (z.B. "ARCHITEKTUR.md Sektion Datenmodell wird um KV-Keys ergänzt"). Diese Änderungen werden in Phase 0 tatsächlich durchgeführt.
     - **Teil B — Infrastruktur-Audit:** welche bestehenden Helper / API-Routes / Komponenten / KV-Keys werden vom Feature berührt? Pro Berührungsstelle: ist es generalisierbar oder hartcodiert auf einen anderen Use-Case? Anti-Pattern erkennen — keine Parallel-Implementierungen. Bei vorhandenem Konzept: Pre-Check-Drift-Liste referenzieren.
   - **Phase 1, 2, 3, … — Implementierungs-Phasen** (je mit klarem Scope, Akzeptanzkriterien, Commit-Ende)
   - **Phase N — Docs-Verifikation** (letzte Phase, immer, auch wenn nur 5 Minuten: Code gegen Docs abgleichen)
   - **Akzeptanzkriterien** (Plan gilt als abgeschlossen wenn …)
   - **Referenzen** (welche Dateien betroffen, welche externen Docs)

6. **`_plans/ROADMAP.md` erweitern** um neuen Plan (Checkbox-Liste + Status + Verweis)
7. **User-Bestätigung einholen** oder Anpassungen einarbeiten

## Vollständigkeits-Prinzip (Anti-Reduzierung)

- **Kein Filtern nach Aufwand.** Alles aus Spec/Briefing/Konzept rein, auch wenn aufwändig.
- **"Folge-Plan / minimal-additiv / später"** sind beim Planen verboten.
- **Bewusste Auslassungen** explizit als "NICHT in diesem Plan"-Liste mit Grund — stilles Weglassen = Bug.
- **Scope-Reduktion** (wenn überhaupt) erst **nach** dem vollständigen Plan mit User diskutieren.

## Nicht vergessen

- **Dateiname chronologisch sortierbar** → `YYYY-MM-DD-` als Präfix
- **Phase 0 zweiteilig (A + B)** ist nicht optional — ohne Docs-Review davor und ohne Infra-Audit driftet alles
- **Verifikations-Phase** ist nicht optional — Versicherung gegen Drift
- **Scope-Abgrenzung** (NICHT-Liste) klar festhalten

## Basis

Volles Template inkl. Beispiele: `C:\AI-Workspace\references\dev-projekt-standard.md` Abschnitt 4.3.
