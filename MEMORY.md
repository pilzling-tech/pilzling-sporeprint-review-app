# MEMORY — Sporeprint Review-Tool

Gestartet: März 2026 (initial unter dem Arbeitstitel "Sternfänger" — am 2026-05-02 umbenannt)
Letzte Aktualisierung: 2026-05-02

## Naming-Konvention (wichtig)

Saubere Trennung: alles **server-intern** = "reviews", alles **extern sichtbar** = "Sporeprint".

| Layer | Name | Begründung |
|-------|------|------------|
| DB | `pilzling_reviews_app` | intern technisch |
| Tabellen / Code-Identifier | `reviews`, `review_replies`, `getReviews()` | intern technisch |
| Server-Profis-Folder | `app.reviews` | intern technisch + Server-Profis-Konvention `app.<name>` |
| GitHub-Repo | `pilzling-sporeprint-review-app` | folgt Konvention `<owner>-<branding>-<technical>-app` analog DB-Name `pilzling_reviews_app` |
| Public Subdomain | `sporeprint.pilzling.eu` | extern, im JTL-Shop-HTML sichtbar |
| Admin Subdomain | `admin-sporeprint.pilzling.eu` | extern, in URL-Bar sichtbar |
| Workspace-Projekt-Ordner | `sporeprint` (geplant, Rename aus aktiver Session blockiert) | intern aber Branding-Identifier — Workspace-Ebene ist eigene Domäne |
| Widget-Branding-Strings | "Sporeprint" | extern |

**cPanel-Folder-Struktur:**
```
/home/pilzling/app.reviews/
├── public/         ← DocRoot für sporeprint.pilzling.eu
├── admin/          ← DocRoot für admin-sporeprint.pilzling.eu (mit .htaccess Verzeichnisschutz)
├── lib/            ← geteilte Helper, NICHT öffentlich
├── config/         ← env.php mit DB/API-Credentials
├── _db/            ← Migrations
└── _tools/         ← Cron-Skripte (per CLI getriggert)
```

## Feedback & Korrekturen

*Dinge die Claude falsch gemacht hat und nicht wiederholen soll. Format: `- [Datum] Was war falsch, wie richtig.`*

- [2026-05-02] Beim Aufsetzen des Projekts darauf achten, dass der **Dev-Projekt-Standard v2.0** gilt (3-Stufen-Methodik, zweiteilige Phase 0). Der ältere v1.0-Standard reicht nicht — wurde explizit hochgezogen.

## Bestätigte Entscheidungen

*Architektur-Entscheidungen die gefallen sind und beibehalten werden. Format: `- [Datum] Entscheidung, kurze Begründung.`*

- [2026-03] Vercel Free Tier als Hosting (0 €/Monat statt 80 €/Monat aktuell)
- [2026-03] Vercel KV (Redis) statt klassischer DB — reicht für Review-Volumen, kein Schema-Management nötig
- [2026-03] Cron alle 6h für Review-Fetching — guter Trade-off zwischen Aktualität und API-Quota
- [2026-03] Brevo für Review-Request-Mails (bereits im Einsatz, keine eigene Mail-Schicht nötig)
- [2026-05-02] Datenmodell für Vercel KV wird per 3-Stufen-Methodik konzipiert (Konzept → Pre-Check → Detailplan), nicht direkt in Phase 1 hineingebaut
- [2026-05-03] **JTL REST API zurückgestellt.** JTL-eigene REST-API ist in Beta und plant kostenpflichtige Lizenzierung (~100 €/Monat). Wird vermieden. Workaround für Phase 1.5 oder 2.5: JTL-Produktbewertungen direkt aus JTL-MSSQL-DB ziehen (production-app hat das Connection-Pattern bereits, Tabelle `tBewertung` o.ä. — zu verifizieren). Phase 1 läuft daher erstmal nur mit Google + Trustpilot.
- [2026-05-03] **GitHub-Repo angelegt:** `pilzling-tech/pilzling-sporeprint-review-app` (Org `pilzling-tech`, privates Repo). `cmvetter92` als Collaborator.
- [2026-05-03] **Repo-Struktur:** Git-Repo liegt auf **Workspace-Folder-Level** (analog production-app), NICHT in `src/`. `src/` enthält nur deploybaren Code (public/, admin/, lib/, config/). `_db/`, `_tools/`, `docs/`, `_plans/`, `_archive/`, `references/`, `CLAUDE.md`, `MEMORY.md`, `.claude/` liegen parallel zu `src/`, sind im Repo aber NICHT im WinSCP-Auto-Deploy. WinSCP-Source ist nur `src/`. Initialer Bug (`_db`/`_tools` in `src/`) am 2026-05-03 korrigiert.
- [2026-05-03] **SSOT-Prinzipien als harte Regeln in CLAUDE.md verankert** (Code-SSOT + DB-SSOT). Bei jeder Code-Änderung wird vor neuen Helpern gegen `lib/`-Inhaltsverzeichnis in `docs/ARCHITEKTUR.md` gegrepped. Außer ID-/PK-Spalten existiert jeder DB-Wert nur einmal — bei Mehrfach-Vorkommen FK-Verweis statt Duplikat.
- [2026-05-03] **Google Cloud Projekt angelegt:** "Pilzling Sporeprint Reviews" (in Organisation "Keine Organisation" — könnte später in eine Pilzling-Workspace-Org wandern, aktuell unkritisch).
- [2026-05-03] **Google Reviews-API-Zugang erfordert separaten Antrag.** Die in der API-Library auflistbaren GMB-APIs (My Business Account Management, Business Information, Performance) reichen nicht — Reviews-Endpoints liegen unter "Business Profile API" und sind nur über das Antragsformular https://developers.google.com/my-business/content/prereqs zugänglich. Bearbeitungszeit typisch 3-14 Tage. Workaround für Phase 1: Trustpilot zuerst implementieren, Google nachziehen sobald Zugang freigegeben — Architektur ist multi-source-fähig, Code-Umbau dafür nicht nötig.
- [2026-05-03] **Google Reviews-API beantragt** (christian@pilzling.com). Wartet auf Freigabe.
- [2026-05-03] **Google OAuth-Client-Credentials** (Client-ID + Secret) für "Pilzling Sporeprint Reviews" angelegt und in Bitwarden gespeichert. Hinweis: Google rollte Ende 2024 eine UI-Änderung aus — Client-Secret wird nicht mehr automatisch erzeugt, muss manuell via "Client-Schlüssel hinzufügen" generiert werden.
- [2026-05-03] **Trustpilot Public API beantragt.** Wartet auf Freigabe. Public-API-Zugang ist normalerweise kostenlos, dient nur der Business-Validierung. Falls Gebühren-Forderung auftaucht: nochmal entscheiden. Business-API (alle Reviews, Reply-API) kostet — kann später gezielt aufgestockt werden falls Public-API zu eingeschränkt ist.

## Bekannte Stolperstellen

*Technische Fallen, Edge-Cases, Workarounds die im Hinterkopf bleiben müssen.*

- pilzling.shop blockt WebFetch (WAF) — für Frontend-Checks Claude in Chrome nutzen, nicht WebFetch
- onlinereviews.tech ist die Altsystem-Referenz — Material liegt unter `references/Orginal Structure/`. Beim Designen des eigenen Systems nicht 1:1 kopieren, sondern bewusst Verbesserungen einbauen
- **cPanel-Verzeichnisschutz + WinSCP-Auto-Deploy = Konflikt.** cPanel verwaltet Auth-Direktiven in der `.htaccess` des geschützten Ordners (mit `#----cp:ppd`-Markern). Wenn die lokale `src/admin/.htaccess` diese Direktiven nicht enthält, überschreibt WinSCP die Server-Version und der Schutz geht verloren — sehr wahrscheinlich auch der historische Grund warum production-app's Schutz immer wieder "weg" war. **Lösung:** den kompletten cp:ppd-Block (inkl. Marker) in `src/admin/.htaccess` mitführen. Bei User-/Passwort-Änderung in cPanel muss der Block manuell nachgezogen werden (Frequenz niedrig, pragmatisch ok). `.htpasswd` selbst liegt unter `/home/pilzling/.htpasswds/<domain>/passwd` — außerhalb DocRoot, vom Sync nicht betroffen.

## Externer Kontext

*Infos aus der Welt außerhalb des Projekts (Server-Config, Team-Absprachen, Termine, Integrations-Details von Drittsystemen).*

- 3 Shops mit eigener JTL-Instanz: Pilzling, Pilzwald, Shroom Boom — jeder Shop braucht eigene API-Keys (Google Place ID, Trustpilot Business Unit ID, JTL REST Credentials)
- Aktuelles System: onlinereviews.tech — 80 €/Monat, Ziel: kündigen nach Sporeprint-Go-Live aller drei Shops
- Brevo-Account existiert bereits und wird für andere Marketing-Flows genutzt — Review-Anfragen sind ein zusätzlicher Automation-Strang, kein neues System
