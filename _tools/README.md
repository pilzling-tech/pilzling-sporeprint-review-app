# _tools/ — Lokale Helper + Cron-Skripte

Skripte hier laufen **per CLI**, nicht ueber HTTP. cPanel-Cronjobs rufen sie mit `php /home/pilzling/app.reviews/_tools/<name>.php` auf.

## Cron-Skripte (kommen in Phase 1)

- `cron-fetch-google.php` — Google Business Profile Reviews fuer alle 3 Shops
- `cron-fetch-trustpilot.php` — Trustpilot Reviews fuer alle 3 Shops
- `cron-cleanup-rate-limits.php` — Alte Eintraege aus rate_limits-Tabelle purgen (haeufig)

## Lokale Tools

- `check_umlauts.py` — Pre-Commit-Hook der staged .md-Dateien + Commit-Message gegen `umlauts-patterns.txt` prueft. Allowlist in `umlauts-allowlist.txt`. Uebernommen aus production-app (Pre-Check A9).

## Cron-Konfiguration in cPanel

```
0 */6 * * *   php /home/pilzling/app.reviews/_tools/cron-fetch-google.php
30 */6 * * *  php /home/pilzling/app.reviews/_tools/cron-fetch-trustpilot.php
*/15 * * * *  php /home/pilzling/app.reviews/_tools/cron-cleanup-rate-limits.php
```

Wird in Phase 1 nach Implementation der Skripte eingerichtet.
