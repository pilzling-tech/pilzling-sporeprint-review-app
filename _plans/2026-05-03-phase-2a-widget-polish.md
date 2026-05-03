# Phase 2a — Widget-Polish (API-frei)

**Erstellt:** 2026-05-03
**Stufe:** 3 (Detailplan, Plan-Light)
**Roadmap-Bezug:** [ROADMAP.md](ROADMAP.md) → Phase 2

## Ziel

Widget-Polish-Schritte die ohne externe APIs laufen können — visuelles Differenzierungs-Element (Sporen-Rating) + Multi-Tenant-Branding (CI pro Shop) + Produkt-Karten-Variante + Responsive-Polish. Mock-Daten bleiben drin, echter API-Call kommt in Phase 2b sobald Google/Trustpilot freigegeben sind.

## Scope

**Drin:**
1. Sporen-Rating-Visual (SVG inline, statt 5-Sterne) — das markante Sporeprint-Element
2. CI-Farben pro Shop (Widget liest `ci_primary`/`ci_secondary` aus Shop-Daten via fetch oder data-attributes)
3. Produktbewertungen als eigene Karten-Variante (JTL-Reviews bekommen Produktname prominent)
4. Sporeprint-Branding-Polish (Footer-Tooltip)
5. Responsive-Test + Polish (Mobile/Tablet/Desktop)

**Bewusst nicht (Phase 2b):**
- API-Anbindung Mock → echtes `fetch('/api/reviews?shop=...')`
- JTL-Template-Integration mit SRI-Hash
- Live-Test im echten Shop-Frontend

## Vorgehen

| # | Schritt | Commit-Ende |
|---|---------|-------------|
| 1 | Sporen-Rating-Visual als SVG (replaceable, klassische Sterne als Fallback wenn JS aus) | `feat(widget): Sporen-Rating-Visual` |
| 2 | CI-Farben pro Shop dynamisch (Widget bekommt Shop-Farben über Config-Endpoint oder Mock) | `feat(widget): CI-Farben pro Shop` |
| 3 | Produktbewertungs-Karten-Variante (Produkt-Header bei `source='jtl'`) | `feat(widget): Produkt-Karten-Variante` |
| 4 | Sporeprint-Footer-Polish (Tooltip-Verbesserung, Sporen-Icon) | `style(widget): Footer-Polish` |
| 5 | Responsive-Test + CSS-Tuning | `style(widget): Responsive-Polish` |

Jeder Schritt ein Commit. Live-Test über `https://admin-sporeprint.pilzling.eu/widget-test.php` mit Shop-Switcher.

## Akzeptanzkriterien

- [ ] Widget zeigt Sporen-Rating statt 5-Sterne
- [ ] Widget rendert mit CI-Farben pro Shop unterschiedlich (Mock kann das simulieren — sobald Phase 2b API liefert, ziehen die echten `ci_primary`/`ci_secondary` aus DB)
- [ ] JTL-Mock-Reviews haben prominenten Produktnamen, Google/Trustpilot nicht
- [ ] Sporeprint-Footer hat Sporen-Icon + Tooltip mit Erklärung
- [ ] Widget responsive auf Mobile (< 480px), Tablet (768px), Desktop (1024px+)
- [ ] Plan in `_archive/_plans/` nach Abschluss

## Referenzen

- Widget-Code: `src/public/widget.js`
- Test-Page: `src/admin/widget-test.php`
- Sporeprint-Erklärung: "Sporenabdruck — der eindeutige Pilz-Fingerabdruck"
- Mock-Reviews: 5 Stück, davon 2 JTL-Source mit `product_name`
