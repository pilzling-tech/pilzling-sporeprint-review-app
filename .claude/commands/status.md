# Status — Sporeprint

Kompakter Überblick über den aktuellen Stand des Projekts.

## Ablauf

1. **`MEMORY.md` kurz zusammenfassen** — relevante offene Punkte, kürzliche Entscheidungen, aktive Stolperstellen
2. **`_plans/ROADMAP.md` lesen** — welche Phase ist aktiv, welche Punkte sind abgehakt, was ist als nächstes dran
3. **Aktive Pläne UND Konzepte in `_plans/` lesen** (alles außer ROADMAP.md). Konzepte am Suffix `-konzept.md` erkennbar — wenn vorhanden, zeigt das die aktuelle Stufe (Konzept / Pre-Check / Detailplan)
4. **Drift-Quickcheck** — `docs/` auflisten und gegen SSOT-Nachschlagewerk in `CLAUDE.md` abgleichen. Bei Unstimmigkeit kurz nennen (nicht automatisch fixen)
5. **Kompaktes Status-Statement** in 3-5 Zeilen ausgeben

## Ausgabe-Format

```
Projekt: Sporeprint Review-Tool
Aktive Phase: <Phase-Name aus ROADMAP>
Aktiver Plan/Konzept: <Datei + Stufe> (z.B. "datenmodell-konzept.md, Stufe 1 Konzept")
Nächster Schritt: <konkreter Schritt>
Offene Punkte: <aus MEMORY / ROADMAP>
Drift: <ok / Abweichung bei ...>
```

Keine Code-Änderungen. Nur lesen und zusammenfassen.
