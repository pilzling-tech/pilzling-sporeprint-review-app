# Sporeprint-Datenbank — Schemas & Migrations

**⚠ Die DB `pilzling_reviews_app` ist die SSOT für alle Reviews. Jede Änderung mit Vorsicht.**

Konvention übernommen aus production-app `_db/README.md` (Pre-Check A8), an Sporeprint-DB-Namen angepasst.

---

## Schema-Versionen (chronologisch, NICHT überschneidend)

| Version | Datei | Zweck | Betrifft |
|---------|-------|-------|----------|
| v1 | `schema_v1.sql` | Initial: 6 Tabellen + 3 Shops + 3 Widget-Configs | shops, reviews, review_replies, sync_runs, widget_configs, rate_limits |
| v2 | `schema_v2.sql` | Pilzling-Go-Live-Vorbereitung (Theme-Overrides, Bewertungs-Landing-Page-Felder, Notification-Emails) | widget_configs (+1 Spalte), shops (+4 Spalten), notification_emails (neue Tabelle) |

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

**Letzte angewandte Migration:** v1 (am 2026-05-02 eingespielt — bestätigt durch User).

**Pending zur Ausführung:** v2 (`schema_v2.sql`) — wartet auf User-Einspielung via phpMyAdmin. Beinhaltet ALTER TABLE auf `widget_configs` und `shops` plus CREATE TABLE für `notification_emails`. Wird gebraucht damit Phase-3-Admin-Pages (Widget-Konfigurator, Bewertungslink, Settings) funktionieren.
