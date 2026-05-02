# Sporeprint-Datenbank — Schemas & Migrations

**⚠ Die DB `pilzling_reviews_app` ist die SSOT fuer alle Reviews. Jede Aenderung mit Vorsicht.**

Konvention uebernommen aus production-app `_db/README.md` (Pre-Check A8), an Sporeprint-DB-Namen angepasst.

---

## Schema-Versionen (chronologisch, NICHT ueberschneidend)

| Version | Datei | Zweck | Betrifft |
|---------|-------|-------|----------|
| v1 | `schema_v1.sql` | Initial: 6 Tabellen + 3 Shops + 3 Widget-Configs | shops, reviews, review_replies, sync_runs, widget_configs, rate_limits |

---

## Namenskonvention

**IMMER:** `schema_vN.sql` mit fortlaufender Nummer N. **Keine Suffixe.**

Falsche Beispiele (gehen nicht):
- ❌ `schema_v3_admin_filter.sql` — Versionsnummer ist belegt
- ❌ `schema_new.sql` — keine Version
- ❌ `schema_v5b.sql` — keine Sub-Versionen

---

## Pflicht-Checkliste vor JEDER Schema-Aenderung

1. **Aktuellen Stand pruefen:**
   - Dieses README lesen → welche Versionen gibt es?
   - Betroffene Tabelle in den existierenden Schemas suchen (was ist aktuell definiert?)
   - Current DB-Struktur via phpMyAdmin dumpen falls unsicher

2. **Neue Migration erstellen:**
   - Naechste freie Versionsnummer nehmen
   - Datei: `schema_vN.sql`
   - **Immer `USE pilzling_reviews_app;`** am Anfang (Schutz gegen "Keine Datenbank ausgewaehlt")
   - **NUR `ALTER TABLE` + `CREATE TABLE IF NOT EXISTS`** nutzen, niemals `DROP TABLE` ohne expliziten Grund
   - Bei Spalten: `ADD COLUMN IF NOT EXISTS` nutzen wo moeglich

3. **README aktualisieren:**
   - Neue Zeile in der Tabelle oben eintragen
   - Zweck beschreiben + welche Tabellen/Spalten betroffen

4. **Migration vom User ausfuehren lassen:**
   - User oeffnet phpMyAdmin → **Datenbank `pilzling_reviews_app` auswaehlen** → SQL-Tab → Script einfuegen + ausfuehren

---

## Typische Fehler vermeiden

### "Keine Datenbank ausgewaehlt" (#1046)
→ `USE pilzling_reviews_app;` am Anfang des Scripts fehlt, oder User hat im phpMyAdmin die DB nicht geoeffnet.
**Fix:** Immer `USE pilzling_reviews_app;` als erste Zeile.

### "Column already exists"
→ Migration wurde schon angewendet.
**Fix:** `IF NOT EXISTS` verwenden wenn moeglich.

---

## Backup-Strategie

**Vor jeder Migration:**
1. phpMyAdmin → `pilzling_reviews_app` → Exportieren → "Schnell" → SQL
2. Datei mit Datum speichern: `backup_YYYY-MM-DD.sql` (lokal, nicht committen)

Wird nicht hier im Git versioniert — liegt im lokalen Workspace oder auf NAS.

---

## Aktueller Stand

**Letzte angewandte Migration:** noch keine — `schema_v1.sql` ist Initial-Migration und wird im Rahmen von Phase 0 Foundation eingespielt.
