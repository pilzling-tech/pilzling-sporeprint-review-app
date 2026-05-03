/**
 * Sporeprint Widget — Vanilla JS, embedbar in JTL-Shop-Templates.
 *
 * Aufruf:
 *   <script src="https://sporeprint.pilzling.eu/widget.js" data-shop="pilzling"></script>
 *
 * Phase 2a (jetzt): Mock-Data hardcoded.
 * Phase 2b (nach API-Freigabe): fetch('/api/reviews?shop=...') statt Mock,
 *                               plus shop-config + aggregate-Endpoint.
 *
 * Widget-Architektur:
 *   - IIFE-Scope (kein globaler Namespace)
 *   - Eigener CSS-Prefix .sporeprint-* (kollidiert nicht mit Shop-Theme)
 *   - Keine externen Dependencies (kein jQuery, kein Framework)
 *   - Keine Cookies, kein LocalStorage (Cookie-Banner-frei)
 *
 * Features:
 *   - Plattform-Aggregat oben (Google + Trustpilot + JTL + Total-Count)
 *   - Sporen-Rating-Visual (Sporeprint-eigenes Markenelement)
 *   - Avatar + Name + Datum pro Card
 *   - Plattform-Icon pro Card oben rechts
 *   - Pfeil-Navigation links/rechts (klickbar)
 *   - CTA "Bewertung schreiben" → Bewertungs-Landing-Page
 *   - Sporeprint-Footer mit Sporen-Icon + Tooltip
 *   - Per-Shop CI-Farben + Theme-Overrides
 *   - Produkt-Chip unten links bei JTL-Reviews (statt prominent oben)
 *   - Responsive (Desktop/Tablet/Mobile)
 */
(function () {
    'use strict';

    // === Aktueller Script-Tag identifizieren ===
    const scriptTag = document.currentScript || (function () {
        const scripts = document.getElementsByTagName('script');
        return scripts[scripts.length - 1];
    })();

    const shopId = scriptTag.getAttribute('data-shop') || '';
    const widgetType = scriptTag.getAttribute('data-type') || 'carousel';
    const apiBase = scriptTag.getAttribute('data-api-base') || 'https://sporeprint.pilzling.eu';

    if (!shopId) {
        console.warn('[Sporeprint] data-shop attribute fehlt');
        return;
    }

    // ===================================================================
    // MOCK-DATA (Phase 2a — Phase 2b ersetzt durch fetch)
    // ===================================================================

    const MOCK_SHOP_CONFIGS = {
        'pilzling': {
            ci_primary: '#F85B05',
            ci_secondary: '#7a4f1a',
            name: 'Pilzling',
            domain: 'pilzling.shop',
            feedback_url: 'https://sporeprint.pilzling.eu/feedback?shop=pilzling',
            theme_overrides: null,
        },
        'pilzwald': {
            ci_primary: '#89B455',
            ci_secondary: '#507227',
            name: 'Pilzwald',
            domain: 'pilzwald.de',
            feedback_url: 'https://sporeprint.pilzling.eu/feedback?shop=pilzwald',
            theme_overrides: null,
        },
        'shroom-boom': {
            ci_primary: '#C87449',
            ci_secondary: '#FFD8C2',
            name: 'Shroom Boom',
            domain: 'shroom-boom.de',
            feedback_url: 'https://sporeprint.pilzling.eu/feedback?shop=shroom-boom',
            theme_overrides: null,
        },
    };

    const MOCK_AGGREGATES = {
        'pilzling':    { google: { avg: 4.8, count: 50 }, trustpilot: { avg: 4.5, count: 13 }, total: 63 },
        'pilzwald':    { google: { avg: 4.7, count: 22 }, trustpilot: { avg: 4.6, count: 8 },  total: 30 },
        'shroom-boom': { google: { avg: 4.9, count: 18 }, trustpilot: { avg: 4.8, count: 5 },  total: 23 },
    };

    const MOCK_REVIEWS = [
        {
            stars: 5,
            content: 'Frische Bio-Pilze in Top-Qualität. Lieferung war schnell und alles war perfekt verpackt.',
            author: 'Marie K.',
            avatar: null,
            language: 'de',
            product_name: null,
            source: 'google',
            posted_on: '2026-04-28',
        },
        {
            stars: 5,
            content: 'Lions Mane Steak ist ein echter Geheimtipp. Schmeckt fantastisch, super Service.',
            author: 'Tom R.',
            avatar: null,
            language: 'de',
            product_name: 'Lions Mane Steak',
            source: 'jtl',
            posted_on: '2026-04-26',
        },
        {
            stars: 4,
            content: 'Sehr guter Shop, schnelle Antwort auf Rückfragen. Pilze waren frisch und der Versand sauber organisiert.',
            author: 'Anna B.',
            avatar: null,
            language: 'de',
            product_name: null,
            source: 'trustpilot',
            posted_on: '2026-04-24',
        },
        {
            stars: 5,
            content: 'Kräuterseitlinge sind erste Sahne. Bestelle definitiv wieder. Auch das Begleitmaterial mit Rezepten ist toll.',
            author: 'Jens H.',
            avatar: null,
            language: 'de',
            product_name: 'Kräuterseitlinge 250g',
            source: 'jtl',
            posted_on: '2026-04-21',
        },
        {
            stars: 5,
            content: 'Beste Pilze aus Köln. Habe schon mehrere Kollegen darauf aufmerksam gemacht.',
            author: 'Petra S.',
            avatar: null,
            language: 'de',
            product_name: null,
            source: 'google',
            posted_on: '2026-04-19',
        },
        {
            stars: 5,
            content: 'Tolles Produkt, hübsch verpackt und bei Fragen oder Problemen gibt es schnell eine Rückmeldung.',
            author: 'Steffi',
            avatar: null,
            language: 'de',
            product_name: null,
            source: 'trustpilot',
            posted_on: '2026-04-15',
        },
    ];

    // ===================================================================
    // THEME-OVERRIDE-MAP — erweiterbar ohne DB-Migration
    // ===================================================================
    const THEME_OVERRIDE_MAP = {
        'accent':           '--sp-accent',
        'accent_soft':      '--sp-accent-soft',
        'background':       '--sp-bg',
        'card_background':  '--sp-card',
        'rating_filled':    '--sp-accent',
    };

    // ===================================================================
    // CSS — wird einmalig in den <head> injected
    // ===================================================================
    const css = `
.sporeprint-widget {
    --sp-bg: #1a1f2e;
    --sp-card: #ffffff;
    --sp-text: #1a1a1a;
    --sp-muted: rgba(0, 0, 0, 0.55);
    --sp-muted-light: rgba(0, 0, 0, 0.4);
    --sp-light: #ffffff;
    --sp-light-60: rgba(255, 255, 255, 0.6);
    --sp-light-20: rgba(255, 255, 255, 0.18);
    --sp-light-10: rgba(255, 255, 255, 0.08);
    --sp-accent: #F85B05;
    --sp-accent-soft: rgba(248, 91, 5, 0.15);
    --sp-radius: 12px;
    --sp-radius-sm: 8px;
    --sp-gap: 16px;
    --sp-font: "Rubik", system-ui, -apple-system, "Segoe UI", "Helvetica Neue", sans-serif;

    background: var(--sp-bg);
    color: var(--sp-light);
    font-family: var(--sp-font);
    padding: 40px 24px 28px;
    border-radius: var(--sp-radius);
    box-sizing: border-box;
    line-height: 1.5;
}
.sporeprint-widget * { box-sizing: border-box; }

/* ===== Header: Aggregate ===== */
.sporeprint-widget__aggregates {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 32px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}
.sporeprint-platform-stat {
    display: flex;
    align-items: center;
    gap: 10px;
}
.sporeprint-platform-stat__logo {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
}
.sporeprint-platform-stat__score {
    font-size: 20px;
    font-weight: 600;
    color: var(--sp-light);
}
.sporeprint-platform-stat__star {
    width: 16px;
    height: 16px;
    color: var(--sp-accent);
    flex-shrink: 0;
}

.sporeprint-widget__total {
    text-align: center;
    font-size: 14px;
    color: var(--sp-light-60);
    margin-bottom: 32px;
}
.sporeprint-widget__total strong {
    color: var(--sp-light);
    font-weight: 600;
}

/* ===== Karussell-Container mit Pfeil-Nav ===== */
.sporeprint-carousel {
    display: flex;
    align-items: center;
    gap: 12px;
    max-width: 1100px;
    margin: 0 auto;
}
.sporeprint-carousel__nav {
    background: var(--sp-light-10);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    min-width: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--sp-light);
    flex-shrink: 0;
    transition: background 0.2s, opacity 0.2s;
    padding: 0;
}
.sporeprint-carousel__nav:hover { background: var(--sp-light-20); }
.sporeprint-carousel__nav:disabled,
.sporeprint-carousel__nav.is-disabled {
    opacity: 0.3;
    cursor: not-allowed;
}
.sporeprint-carousel__nav svg { width: 18px; height: 18px; }

.sporeprint-carousel__viewport {
    overflow: hidden;
    flex: 1;
}
.sporeprint-carousel__track {
    display: flex;
    gap: var(--sp-gap);
    transition: transform 0.35s cubic-bezier(.4, 0, .2, 1);
}

/* ===== Review-Card ===== */
.sporeprint-card {
    flex: 0 0 calc(33.333% - calc(var(--sp-gap) * 2 / 3));
    background: var(--sp-card);
    color: var(--sp-text);
    border-radius: var(--sp-radius);
    padding: 18px 18px 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-height: 220px;
    position: relative;
}
.sporeprint-card__header {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding-right: 28px; /* Platz für Plattform-Icon oben rechts */
}
.sporeprint-card__avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--sp-accent-soft);
    color: var(--sp-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 14px;
    font-weight: 600;
    overflow: hidden;
}
.sporeprint-card__avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.sporeprint-card__author {
    flex: 1;
    min-width: 0;
}
.sporeprint-card__name {
    font-weight: 600;
    font-size: 14px;
    color: var(--sp-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sporeprint-card__date {
    font-size: 12px;
    color: var(--sp-muted-light);
    margin-top: 2px;
}
.sporeprint-card__platform {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 20px;
    height: 20px;
}

/* ===== Sporen-Rating ===== */
.sporeprint-card__rating {
    display: inline-flex;
    gap: 3px;
    align-items: center;
    margin-top: 2px;
}
.sporeprint-card__spore {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}
.sporeprint-card__spore--filled { color: var(--sp-accent); }
.sporeprint-card__spore--empty  { color: rgba(0, 0, 0, 0.12); }

/* ===== Bewertungstext ===== */
.sporeprint-card__content {
    font-size: 14px;
    line-height: 1.55;
    color: var(--sp-text);
    flex: 1;
    /* Truncate nach 5 Zeilen */
    display: -webkit-box;
    -webkit-line-clamp: 5;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* ===== Produkt-Chip (nur bei JTL-Reviews, unten links) ===== */
.sporeprint-card__product-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    background: var(--sp-accent-soft);
    color: var(--sp-accent);
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    align-self: flex-start;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.sporeprint-card__product-chip svg {
    width: 11px;
    height: 11px;
    flex-shrink: 0;
}

/* ===== CTA-Button ===== */
.sporeprint-widget__cta {
    text-align: center;
    margin-top: 28px;
}
.sporeprint-cta-btn {
    display: inline-block;
    background: transparent;
    border: 1.5px solid var(--sp-light-20);
    border-radius: 999px;
    padding: 12px 32px;
    color: var(--sp-light);
    font-family: var(--sp-font);
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.2s, border-color 0.2s, color 0.2s;
}
.sporeprint-cta-btn:hover {
    background: var(--sp-accent);
    border-color: var(--sp-accent);
    color: var(--sp-light);
}

/* ===== Footer ===== */
.sporeprint-widget__footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid var(--sp-light-10);
    font-size: 11px;
    color: var(--sp-light-60);
    letter-spacing: 0.04em;
}
.sporeprint-widget__footer-icon {
    width: 12px;
    height: 12px;
    color: var(--sp-accent);
    flex-shrink: 0;
}
.sporeprint-widget__footer a {
    color: inherit;
    text-decoration: none;
    border-bottom: 1px dotted rgba(255, 255, 255, 0.4);
    transition: color 0.15s, border-color 0.15s;
}
.sporeprint-widget__footer a:hover {
    color: var(--sp-accent);
    border-bottom-color: var(--sp-accent);
}

/* ===== Responsive ===== */
@media (max-width: 900px) {
    .sporeprint-card { flex-basis: calc(50% - calc(var(--sp-gap) / 2)); }
}
@media (max-width: 640px) {
    .sporeprint-widget { padding: 28px 16px 20px; }
    .sporeprint-widget__aggregates { gap: 20px; }
    .sporeprint-platform-stat__logo { width: 26px; height: 26px; }
    .sporeprint-platform-stat__score { font-size: 17px; }
    .sporeprint-widget__total { margin-bottom: 22px; font-size: 13px; }
    .sporeprint-card { flex-basis: 100%; min-height: 180px; }
    .sporeprint-carousel__nav { width: 36px; height: 36px; min-width: 36px; }
}
@media (max-width: 480px) {
    .sporeprint-widget { padding: 20px 12px 16px; border-radius: var(--sp-radius-sm); }
    .sporeprint-card { padding: 14px; }
    .sporeprint-card__content { font-size: 13px; -webkit-line-clamp: 4; }
}
`;

    function injectStyles() {
        if (document.getElementById('sporeprint-widget-styles')) return;
        const style = document.createElement('style');
        style.id = 'sporeprint-widget-styles';
        style.textContent = css;
        document.head.appendChild(style);
    }

    // ===================================================================
    // SVG-Icons als Strings (inline für minimalen Footprint)
    // ===================================================================

    // Sporenabdruck — zentraler Punkt + 8 Sub-Punkte ringsherum
    const SPORE_SVG = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor" aria-hidden="true">'
        + '<circle cx="12" cy="12" r="3"/>'
        + '<circle cx="12" cy="4" r="1.6"/>'
        + '<circle cx="12" cy="20" r="1.6"/>'
        + '<circle cx="4" cy="12" r="1.6"/>'
        + '<circle cx="20" cy="12" r="1.6"/>'
        + '<circle cx="6.5" cy="6.5" r="1.4"/>'
        + '<circle cx="17.5" cy="6.5" r="1.4"/>'
        + '<circle cx="6.5" cy="17.5" r="1.4"/>'
        + '<circle cx="17.5" cy="17.5" r="1.4"/>'
        + '</svg>';

    // Google-G (offizielles Multi-Color-Logo)
    const GOOGLE_G_SVG = '<svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-label="Google">'
        + '<path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>'
        + '<path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>'
        + '<path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>'
        + '<path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>'
        + '</svg>';

    // Trustpilot-Stern (grün)
    const TRUSTPILOT_STAR_SVG = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-label="Trustpilot">'
        + '<path fill="#00b67a" d="M12 0L14.59 8.41H23.51L16.46 13.59L19.05 22L12 16.82L4.95 22L7.54 13.59L0.49 8.41H9.41L12 0Z"/>'
        + '</svg>';

    // JTL-Produkt — Tag-Icon
    const JTL_TAG_SVG = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor" aria-label="Produkt">'
        + '<path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/>'
        + '</svg>';

    // Stern (für Plattform-Aggregat-Anzeige)
    const SOLID_STAR_SVG = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor" aria-hidden="true">'
        + '<path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>'
        + '</svg>';

    // Chevron Left/Right für Pfeil-Nav
    const CHEVRON_LEFT_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>';
    const CHEVRON_RIGHT_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>';

    // ===================================================================
    // Helper-Funktionen
    // ===================================================================

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    function hexToRgba(hex, alpha) {
        const m = hex.replace('#', '').match(/.{1,2}/g);
        if (!m || m.length < 3) return 'rgba(248, 91, 5, ' + alpha + ')';
        const [r, g, b] = m.slice(0, 3).map(h => parseInt(h, 16));
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
    }

    /**
     * Formatiert ISO-Datum auf "17. Dezember 2025"-Schreibweise.
     */
    const MONTH_NAMES = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
                        'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
    function formatDateLong(iso) {
        if (!iso) return '';
        const d = new Date(String(iso).replace(' ', 'T'));
        if (isNaN(d)) return '';
        return d.getDate() + '. ' + MONTH_NAMES[d.getMonth()] + ' ' + d.getFullYear();
    }

    /**
     * Erzeugt Initialen aus Author-Name für Avatar-Fallback.
     */
    function getInitials(name) {
        if (!name) return '?';
        const parts = String(name).trim().split(/\s+/);
        if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    function applyShopBranding(container, shopConfig) {
        if (!shopConfig) return;
        if (shopConfig.ci_primary) {
            container.style.setProperty('--sp-accent', shopConfig.ci_primary);
            container.style.setProperty('--sp-accent-soft', hexToRgba(shopConfig.ci_primary, 0.15));
        }
        const overrides = shopConfig.theme_overrides || {};
        for (const [key, value] of Object.entries(overrides)) {
            const cssVar = THEME_OVERRIDE_MAP[key];
            if (cssVar && value) container.style.setProperty(cssVar, value);
        }
    }

    // ===================================================================
    // Render-Funktionen
    // ===================================================================

    function renderRating(count) {
        const wrapper = document.createElement('div');
        wrapper.className = 'sporeprint-card__rating';
        wrapper.setAttribute('role', 'img');
        wrapper.setAttribute('aria-label', count + ' von 5 Sporen');
        for (let i = 1; i <= 5; i++) {
            const span = document.createElement('span');
            span.className = 'sporeprint-card__spore sporeprint-card__spore--' + (i <= count ? 'filled' : 'empty');
            span.innerHTML = SPORE_SVG;
            wrapper.appendChild(span);
        }
        return wrapper;
    }

    function getPlatformIcon(source) {
        if (source === 'google') return GOOGLE_G_SVG;
        if (source === 'trustpilot') return TRUSTPILOT_STAR_SVG;
        if (source === 'jtl') return JTL_TAG_SVG;
        return '';
    }

    function renderCard(review) {
        const card = document.createElement('article');
        card.className = 'sporeprint-card';

        // === Plattform-Icon oben rechts ===
        const platformIcon = document.createElement('span');
        platformIcon.className = 'sporeprint-card__platform';
        platformIcon.innerHTML = getPlatformIcon(review.source);
        card.appendChild(platformIcon);

        // === Header: Avatar + Name + Datum ===
        const header = document.createElement('div');
        header.className = 'sporeprint-card__header';

        const avatar = document.createElement('div');
        avatar.className = 'sporeprint-card__avatar';
        if (review.avatar) {
            avatar.innerHTML = '<img src="' + escapeHtml(review.avatar) + '" alt="">';
        } else {
            avatar.textContent = getInitials(review.author);
        }
        header.appendChild(avatar);

        const authorBlock = document.createElement('div');
        authorBlock.className = 'sporeprint-card__author';
        const name = document.createElement('div');
        name.className = 'sporeprint-card__name';
        name.textContent = review.author || 'Anonym';
        const date = document.createElement('div');
        date.className = 'sporeprint-card__date';
        date.textContent = formatDateLong(review.posted_on);
        authorBlock.appendChild(name);
        authorBlock.appendChild(date);
        header.appendChild(authorBlock);

        card.appendChild(header);

        // === Sporen-Rating ===
        card.appendChild(renderRating(review.stars));

        // === Bewertungstext ===
        const content = document.createElement('div');
        content.className = 'sporeprint-card__content';
        content.textContent = review.content;
        card.appendChild(content);

        // === Produkt-Chip unten links bei JTL-Reviews ===
        if (review.product_name) {
            const chip = document.createElement('span');
            chip.className = 'sporeprint-card__product-chip';
            chip.title = review.product_name;
            chip.innerHTML = JTL_TAG_SVG + '<span>' + escapeHtml(review.product_name) + '</span>';
            card.appendChild(chip);
        }

        return card;
    }

    function renderAggregates(aggregates) {
        const wrapper = document.createElement('div');
        wrapper.className = 'sporeprint-widget__aggregates';

        const platforms = [
            { key: 'google', label: 'Google', logo: GOOGLE_G_SVG },
            { key: 'trustpilot', label: 'Trustpilot', logo: TRUSTPILOT_STAR_SVG },
            { key: 'jtl', label: 'Produktbewertungen', logo: JTL_TAG_SVG },
        ];

        let anyShown = false;
        for (const p of platforms) {
            const data = aggregates && aggregates[p.key];
            if (!data || !data.count) continue;
            anyShown = true;

            const stat = document.createElement('div');
            stat.className = 'sporeprint-platform-stat';
            stat.setAttribute('aria-label', p.label + ': ' + data.avg + ' von 5');

            const logo = document.createElement('span');
            logo.className = 'sporeprint-platform-stat__logo';
            logo.innerHTML = p.logo;
            stat.appendChild(logo);

            const score = document.createElement('span');
            score.className = 'sporeprint-platform-stat__score';
            score.textContent = String(data.avg.toFixed(1)).replace('.', ',');
            stat.appendChild(score);

            const star = document.createElement('span');
            star.className = 'sporeprint-platform-stat__star';
            star.innerHTML = SOLID_STAR_SVG;
            stat.appendChild(star);

            wrapper.appendChild(stat);
        }

        return anyShown ? wrapper : null;
    }

    function renderTotal(aggregates) {
        const total = aggregates && aggregates.total;
        if (!total) return null;
        const wrapper = document.createElement('div');
        wrapper.className = 'sporeprint-widget__total';
        wrapper.innerHTML = '<strong>' + total + '</strong> Bewertungen';
        return wrapper;
    }

    /**
     * Rendert das komplette Widget.
     */
    function renderWidget(container, reviews, shopConfig, aggregates) {
        container.classList.add('sporeprint-widget');
        container.innerHTML = '';
        applyShopBranding(container, shopConfig);

        // === Aggregat-Block oben ===
        const agg = renderAggregates(aggregates);
        if (agg) container.appendChild(agg);

        const total = renderTotal(aggregates);
        if (total) container.appendChild(total);

        // === Karussell mit Pfeil-Nav ===
        const carousel = document.createElement('div');
        carousel.className = 'sporeprint-carousel';

        const btnPrev = document.createElement('button');
        btnPrev.className = 'sporeprint-carousel__nav sporeprint-carousel__nav--prev';
        btnPrev.setAttribute('aria-label', 'Zurück');
        btnPrev.innerHTML = CHEVRON_LEFT_SVG;
        carousel.appendChild(btnPrev);

        const viewport = document.createElement('div');
        viewport.className = 'sporeprint-carousel__viewport';
        const track = document.createElement('div');
        track.className = 'sporeprint-carousel__track';
        reviews.forEach(r => track.appendChild(renderCard(r)));
        viewport.appendChild(track);
        carousel.appendChild(viewport);

        const btnNext = document.createElement('button');
        btnNext.className = 'sporeprint-carousel__nav sporeprint-carousel__nav--next';
        btnNext.setAttribute('aria-label', 'Weiter');
        btnNext.innerHTML = CHEVRON_RIGHT_SVG;
        carousel.appendChild(btnNext);

        container.appendChild(carousel);

        // === Karussell-Nav-Logik ===
        let current = 0;
        function visibleCards() {
            const w = window.innerWidth;
            if (w <= 640) return 1;
            if (w <= 900) return 2;
            return 3;
        }
        function update() {
            const cards = track.children;
            if (!cards.length) return;
            const cardWidth = cards[0].getBoundingClientRect().width;
            const gap = 16;
            const maxIdx = Math.max(0, cards.length - visibleCards());
            current = Math.max(0, Math.min(current, maxIdx));
            track.style.transform = 'translateX(-' + (current * (cardWidth + gap)) + 'px)';
            btnPrev.classList.toggle('is-disabled', current === 0);
            btnNext.classList.toggle('is-disabled', current === maxIdx);
        }
        btnPrev.addEventListener('click', () => { current--; update(); });
        btnNext.addEventListener('click', () => { current++; update(); });
        window.addEventListener('resize', update);
        // Initial-Update mit Delay damit Layout berechnet ist
        setTimeout(update, 50);

        // === CTA-Button ===
        if (shopConfig && shopConfig.feedback_url) {
            const cta = document.createElement('div');
            cta.className = 'sporeprint-widget__cta';
            const link = document.createElement('a');
            link.href = shopConfig.feedback_url;
            link.target = '_blank';
            link.rel = 'noopener';
            link.className = 'sporeprint-cta-btn';
            link.textContent = 'Bewertung schreiben';
            cta.appendChild(link);
            container.appendChild(cta);
        }

        // === Footer ===
        const footer = document.createElement('div');
        footer.className = 'sporeprint-widget__footer';
        const footerIcon = document.createElement('span');
        footerIcon.className = 'sporeprint-widget__footer-icon';
        footerIcon.innerHTML = SPORE_SVG;
        footer.appendChild(footerIcon);
        const footerLink = document.createElement('a');
        footerLink.href = 'https://sporeprint.pilzling.eu/';
        footerLink.target = '_blank';
        footerLink.rel = 'noopener';
        footerLink.title = 'Sporeprint — wie der Sporenabdruck einen Pilz eindeutig identifiziert, machen Bewertungen die wahre Identität einer Marke sichtbar.';
        footerLink.textContent = 'powered by Sporeprint';
        footer.appendChild(footerLink);
        container.appendChild(footer);
    }

    function findOrCreateContainer() {
        let container = document.getElementById('sporeprint-widget');
        if (!container) {
            container = document.createElement('div');
            container.id = 'sporeprint-widget';
            scriptTag.parentNode.insertBefore(container, scriptTag.nextSibling);
        }
        return container;
    }

    // ===================================================================
    // Init
    // ===================================================================
    injectStyles();
    const container = findOrCreateContainer();

    // Phase 2a: Mock-Daten direkt rendern.
    // Phase 2b: durch fetch() ersetzen — siehe Kommentar unten.
    const shopConfig = MOCK_SHOP_CONFIGS[shopId] || null;
    const aggregates = MOCK_AGGREGATES[shopId] || null;
    renderWidget(container, MOCK_REVIEWS, shopConfig, aggregates);

    /*
     * === Phase 2b — sobald APIs liefern ===
     *
     * Promise.all([
     *     fetch(apiBase + '/api/shop-config?shop=' + encodeURIComponent(shopId)).then(r => r.json()),
     *     fetch(apiBase + '/api/reviews?shop=' + encodeURIComponent(shopId)).then(r => r.json()),
     *     fetch(apiBase + '/api/aggregates?shop=' + encodeURIComponent(shopId)).then(r => r.json()),
     * ])
     * .then(([shopConfig, reviews, aggregates]) => {
     *     renderWidget(container, reviews, shopConfig, aggregates);
     * })
     * .catch(err => {
     *     console.warn('[Sporeprint] Konnte Daten nicht laden:', err);
     *     // Fallback: Container verstecken oder Skeleton-State
     *     container.style.display = 'none';
     * });
     */
})();
