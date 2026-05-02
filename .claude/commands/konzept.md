# Konzept — Neues Konzept-Dokument anlegen (Stufe 1 der 3-Stufen-Methodik)

Erstellt ein neues Konzept-Dokument unter `_plans/YYYY-MM-DD-<feature-name>-konzept.md` für komplexe Features. Dies ist die **Brainstorming-Stufe** — kein Umsetzungsplan.

**Wann anwenden** (Pflicht-Triggern):
- Feature führt **neue Datenstrukturen** ein (Vercel-KV-Schema, neues Daten-Modell)
- Feature berührt **mehrere Schichten** (z.B. Backend + Widget + Admin)
- Feature bringt **neues Datenmodell-Konzept** (Multi-Tenant-Mechanik, Lifecycle-Logik)
- Feature erwartet **mehrere Brainstorming-Runden** mit dem User

**Im Zweifel:** lieber Konzept zuviel als zuwenig — nicht selber entscheiden, beim User nachfragen.

## Ablauf

1. **User nach Feature-Namen fragen** (kurz, beschreibend, Bindestriche statt Leerzeichen)
2. **Aktuelles Datum ermitteln** → Dateiname: `_plans/YYYY-MM-DD-<feature-name>-konzept.md`
3. **Bestehende Pläne und Konzepte kurz anschauen** um Referenzen / Voraussetzungen zu identifizieren
4. **Konzept-Template anlegen** mit folgenden Pflicht-Abschnitten:
   - **Status-Header oben:** `Status: Konzept (kein Umsetzungsplan)`
   - **Ziel** (Was/Warum, 2-5 Sätze)
   - **Datenmodell / Geschäfts-Regeln** (Soll-Zustand, kein Migrations-Pfad in dieser Stufe)
   - **UI-Skizzen / Beispiele** (wo relevant)
   - **Bewusst nicht im Konzept** (Pflicht-Sektion mit Grund pro Auslassung)
   - **Iterations-Log** (initial leer, jede Iteration mit Datum eintragen)
   - **Pre-Check (Stufe 2)** — Platzhalter-Sektion mit drei Unter-Abschnitten:
     - Wiederverwendung (welche bestehenden Bausteine werden abgedeckt)
     - Drift-Punkte (was muss generalisiert werden)
     - Schema-Korrekturen (Redundanzen, Naming-Inkonsistenzen)

     Diese Sektion bleibt initial leer — wird gefüllt **bevor** der Detailplan geschrieben wird.

5. **`_plans/ROADMAP.md` erweitern** um Eintrag für das neue Feature (Status: "in Konzept-Stufe")
6. **User-Bestätigung einholen** — los iterieren

## Wichtig

- **Reihenfolge im Konzept ist nicht relevant** — wir denken offen, ergänzen, ändern, verwerfen
- **Kein Fokus auf bestehenden Code** in Stufe 1 — wir definieren das Soll, nicht den Migrations-Pfad
- **Nicht mit Detailplan vermischen** — Konzept hat keine Phasen, keine Akzeptanzkriterien, keinen Phase-0-Audit. Das kommt erst in der Detailplan-Stufe.
- **Pre-Check (Stufe 2) startet** sobald User signalisiert "Konzept passt für mich" — wird in dieselbe Datei eingearbeitet, kein eigenes Dokument

## Basis

Volles Pattern: `C:\AI-Workspace\references\dev-projekt-standard.md` Abschnitt 4.2.
