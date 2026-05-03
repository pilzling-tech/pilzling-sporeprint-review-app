/**
 * Sporeprint Widget — Vanilla JS, embedbar in JTL-Shop-Templates.
 *
 * Aufruf:
 *   <script src="https://sporeprint.pilzling.eu/widget.js" data-shop="pilzling"></script>
 *
 * Phase 1: Mock-Data hardcoded. Phase 2: fetch('/api/reviews?shop=...') statt Mock.
 *
 * Anti-Konflikt-Strategie:
 *   - IIFE-Scope (kein globaler Namespace)
 *   - Eigener CSS-Prefix .sporeprint-* (kollidiert nicht mit Shop-Theme)
 *   - Keine externen Dependencies (kein jQuery, kein Framework)
 *   - Keine Cookies, kein LocalStorage (Cookie-Banner-frei)
 */
(function () {
    'use strict';

    // === Aktueller Script-Tag identifizieren ===
    const scriptTag = document.currentScript || (function () {
        const scripts = document.getElementsByTagName('script');
        return scripts[scripts.length - 1];
    })();

    const shopId = scriptTag.getAttribute('data-shop') || '';
    if (!shopId) {
        console.warn('[Sporeprint] data-shop attribute fehlt');
        return;
    }

    // === Mock-Reviews für Phase 1. Später via fetch ersetzt. ===
    const MOCK_REVIEWS = [
        {
            stars: 5,
            content: 'Frische Bio-Pilze in Top-Qualität. Lieferung war schnell und alles war perfekt verpackt.',
            author: 'Marie K.',
            language: 'de',
            product_name: null,
            source: 'google',
            posted_on: '2026-04-28',
        },
        {
            stars: 5,
            content: 'Lions Mane Steak ist ein echter Geheimtipp. Schmeckt fantastisch, super Service.',
            author: 'Tom R.',
            language: 'de',
            product_name: 'Lions Mane Steak',
            source: 'jtl',
            posted_on: '2026-04-26',
        },
        {
            stars: 4,
            content: 'Sehr guter Shop, schnelle Antwort auf Rückfragen. Pilze waren frisch.',
            author: 'Anna B.',
            language: 'de',
            product_name: null,
            source: 'trustpilot',
            posted_on: '2026-04-24',
        },
        {
            stars: 5,
            content: 'Kräuterseitlinge sind erste Sahne. Bestelle definitiv wieder.',
            author: 'Jens H.',
            language: 'de',
            product_name: 'Kräuterseitlinge 250g',
            source: 'jtl',
            posted_on: '2026-04-21',
        },
        {
            stars: 5,
            content: 'Beste Pilze aus Köln. Habe schon mehrere Kollegen darauf aufmerksam gemacht.',
            author: 'Petra S.',
            language: 'de',
            product_name: null,
            source: 'google',
            posted_on: '2026-04-19',
        },
    ];

    // === CSS injizieren ===
    const css = `
.sporeprint-widget {
    --sp-bg: #1a1f2e;
    --sp-card: #ffffff;
    --sp-text: #1a1a1a;
    --sp-muted: rgba(0,0,0,0.45);
    --sp-light: #ffffff;
    --sp-light-60: rgba(255,255,255,0.6);
    --sp-star: #f4c430;
    --sp-radius: 12px;
    --sp-gap: 16px;
    --sp-font: system-ui, -apple-system, "Segoe UI", sans-serif;

    background: var(--sp-bg);
    color: var(--sp-light);
    font-family: var(--sp-font);
    padding: 32px 16px 24px;
    border-radius: var(--sp-radius);
    box-sizing: border-box;
}
.sporeprint-widget * { box-sizing: border-box; }
.sporeprint-widget__header {
    text-align: center;
    margin-bottom: 24px;
    font-size: 14px;
    color: var(--sp-light-60);
    letter-spacing: 0.05em;
    text-transform: uppercase;
}
.sporeprint-widget__track {
    display: flex;
    gap: var(--sp-gap);
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 12px;
    scrollbar-width: thin;
}
.sporeprint-widget__track::-webkit-scrollbar { height: 6px; }
.sporeprint-widget__track::-webkit-scrollbar-track { background: transparent; }
.sporeprint-widget__track::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.18); border-radius: 3px; }
.sporeprint-card {
    flex: 0 0 280px;
    background: var(--sp-card);
    color: var(--sp-text);
    padding: 18px;
    border-radius: var(--sp-radius);
    scroll-snap-align: start;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-height: 180px;
}
.sporeprint-card__stars {
    color: var(--sp-star);
    font-size: 16px;
    letter-spacing: 1px;
}
.sporeprint-card__rating {
    display: inline-flex;
    gap: 3px;
    align-items: center;
}
.sporeprint-card__spore {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}
.sporeprint-card__spore--filled {
    color: var(--sp-star);
}
.sporeprint-card__spore--empty {
    color: rgba(0, 0, 0, 0.12);
}
.sporeprint-card__product {
    font-size: 12px;
    color: var(--sp-muted);
    font-weight: 500;
}
.sporeprint-card__content {
    font-size: 14px;
    line-height: 1.45;
    flex: 1;
}
.sporeprint-card__meta {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: var(--sp-muted);
    margin-top: auto;
}
.sporeprint-card__source {
    text-transform: uppercase;
    font-weight: 500;
    letter-spacing: 0.05em;
}
.sporeprint-widget__footer {
    text-align: center;
    margin-top: 18px;
    font-size: 11px;
    color: var(--sp-light-60);
    letter-spacing: 0.05em;
}
.sporeprint-widget__footer a {
    color: inherit;
    text-decoration: none;
    border-bottom: 1px dotted rgba(255,255,255,0.4);
}
.sporeprint-widget__footer a:hover { color: var(--sp-light); }
@media (max-width: 480px) {
    .sporeprint-card { flex-basis: 240px; }
}
`;

    function injectStyles() {
        if (document.getElementById('sporeprint-widget-styles')) return;
        const style = document.createElement('style');
        style.id = 'sporeprint-widget-styles';
        style.textContent = css;
        document.head.appendChild(style);
    }

    // === Render-Helper ===
    //
    // Sporen-Rating-Visual: stilisierter Sporenabdruck als SVG.
    // Statt 5 Sterne zeigen wir 5 kleine "Sporen-Cluster" — ein Pilz-eigenes
    // Rating, das mit dem Brand-Namen "Sporeprint" semantisch zusammenhaengt.
    // Farb-Code: gefuellt = volle Bewertung, leer = nicht erreichte Bewertung.
    //
    // Geometrie: ein zentraler Punkt + 6 ringsherum verteilte kleinere Punkte.
    // Wirkt wie ein klassischer Sporenabdruck unter dem Mikroskop.
    const SPORE_SVG = `<svg class="sporeprint-card__spore" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor" aria-hidden="true">
        <circle cx="12" cy="12" r="3"/>
        <circle cx="12" cy="4" r="1.6"/>
        <circle cx="12" cy="20" r="1.6"/>
        <circle cx="4" cy="12" r="1.6"/>
        <circle cx="20" cy="12" r="1.6"/>
        <circle cx="6.5" cy="6.5" r="1.4"/>
        <circle cx="17.5" cy="6.5" r="1.4"/>
        <circle cx="6.5" cy="17.5" r="1.4"/>
        <circle cx="17.5" cy="17.5" r="1.4"/>
    </svg>`;

    function renderRating(count) {
        const wrapper = document.createElement('div');
        wrapper.className = 'sporeprint-card__rating';
        wrapper.setAttribute('role', 'img');
        wrapper.setAttribute('aria-label', count + ' von 5 Sporen');
        for (let i = 1; i <= 5; i++) {
            wrapper.insertAdjacentHTML(
                'beforeend',
                SPORE_SVG.replace(
                    'class="sporeprint-card__spore"',
                    'class="sporeprint-card__spore sporeprint-card__spore--' + (i <= count ? 'filled' : 'empty') + '"'
                )
            );
        }
        return wrapper;
    }

    function renderCard(review) {
        const card = document.createElement('article');
        card.className = 'sporeprint-card';

        card.appendChild(renderRating(review.stars));

        if (review.product_name) {
            const product = document.createElement('div');
            product.className = 'sporeprint-card__product';
            product.textContent = review.product_name;
            card.appendChild(product);
        }

        const content = document.createElement('div');
        content.className = 'sporeprint-card__content';
        content.textContent = review.content;
        card.appendChild(content);

        const meta = document.createElement('div');
        meta.className = 'sporeprint-card__meta';
        const author = document.createElement('span');
        author.textContent = review.author || 'Anonym';
        const source = document.createElement('span');
        source.className = 'sporeprint-card__source';
        source.textContent = review.source;
        meta.appendChild(author);
        meta.appendChild(source);
        card.appendChild(meta);

        return card;
    }

    function renderWidget(container, reviews) {
        container.classList.add('sporeprint-widget');
        container.innerHTML = '';

        const header = document.createElement('div');
        header.className = 'sporeprint-widget__header';
        header.textContent = 'Was unsere Kund:innen sagen';
        container.appendChild(header);

        const track = document.createElement('div');
        track.className = 'sporeprint-widget__track';
        reviews.forEach(r => track.appendChild(renderCard(r)));
        container.appendChild(track);

        const footer = document.createElement('div');
        footer.className = 'sporeprint-widget__footer';
        const link = document.createElement('a');
        link.href = 'https://sporeprint.pilzling.eu/';
        link.target = '_blank';
        link.rel = 'noopener';
        link.title = 'Sporeprint — der Sporenabdruck einer Marke. Reviews als unverfälschter Abdruck der Kund:innen-Erfahrung.';
        link.textContent = 'powered by Sporeprint';
        footer.appendChild(link);
        container.appendChild(footer);
    }

    // === Container finden / anlegen ===
    function findOrCreateContainer() {
        let container = document.getElementById('sporeprint-widget');
        if (!container) {
            container = document.createElement('div');
            container.id = 'sporeprint-widget';
            scriptTag.parentNode.insertBefore(container, scriptTag.nextSibling);
        }
        return container;
    }

    // === Init ===
    injectStyles();
    const container = findOrCreateContainer();

    // Phase 1: Mock-Data direkt rendern.
    // Phase 2: durch fetch() ersetzen, siehe Kommentar unten.
    renderWidget(container, MOCK_REVIEWS);

    /*
     * Phase-2-Implementierung (sobald APIs liefern):
     *
     * fetch('https://sporeprint.pilzling.eu/api/reviews?shop=' + encodeURIComponent(shopId))
     *     .then(r => r.json())
     *     .then(reviews => renderWidget(container, reviews))
     *     .catch(err => {
     *         console.warn('[Sporeprint] Konnte Reviews nicht laden:', err);
     *         // Fallback: Container verstecken oder Skeleton-State
     *     });
     */
})();
