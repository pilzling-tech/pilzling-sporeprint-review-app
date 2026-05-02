# Sporeprint — Review-Aggregations-Tool

Eigenes Review-Management-System für drei JTL-Shops der I.M.A. Pilzling GmbH (Pilzling, Pilzwald, Shroom Boom). Aggregiert Google-, Trustpilot- und (perspektivisch) JTL-Produktbewertungen in ein Widget + Admin-Dashboard.

**Branding extern:** Sporeprint (Sporenabdruck — der eindeutige Pilz-Fingerabdruck).
**Tech-Layer intern:** "reviews" (DB `pilzling_reviews_app`, Tabellen `reviews`/`review_replies`).

## Stack

- PHP 8.2 + MariaDB auf Server Profis cPanel
- Vanilla JS Widget
- Brevo für Mail-Automation (Phase 4)
- Google Business Profile API + Trustpilot API

## Subdomains

- `sporeprint.pilzling.eu` → Public-API + Widget (DocRoot `public/`)
- `admin-sporeprint.pilzling.eu` → Admin-Dashboard (DocRoot `admin/`, Verzeichnisschutz aktiv)

## Folder-Struktur

```
src/
├── public/   ← DocRoot fuer sporeprint.pilzling.eu
├── admin/    ← DocRoot fuer admin-sporeprint.pilzling.eu
├── lib/      ← geteilte Helper, NICHT public
├── config/   ← database.php + .env (nicht committed)
├── _db/      ← Schema-Migrations (nummeriert)
└── _tools/   ← Cron-Skripte (PHP-CLI)
```

## Lokales Setup

1. Repo klonen
2. `config/.env` aus `config/.env.example` kopieren und mit lokalen DB-/API-Credentials befuellen
3. Schema einspielen via phpMyAdmin: `_db/schema_v1.sql` ausfuehren auf `pilzling_reviews_app`
4. Auto-Deploy via WinSCP nach `/home/pilzling/app.reviews/`

## Doku

- Volle Architektur, Sicherheits-Layer, Datenmodell: `../docs/ARCHITEKTUR.md` im Workspace-Folder
- Aktiver Plan: `../_plans/2026-05-03-phase-0-foundation.md`
- Konzept-Hintergrund: `../_plans/2026-05-02-architektur-pivot-konzept.md`

## Repo-Origin

`pilzling-tech/pilzling-sporeprint-review-app` (GitHub, privat).
