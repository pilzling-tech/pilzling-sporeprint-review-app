/* Sporeprint Admin — Format-Helper (SSOT)
 *
 * SYNC-PAIR: PHP-Pendants in src/lib/helpers.php (formatDate, humanTimeDiff).
 * Beide müssen synchron bleiben (gleiche Schwellwerte, gleiche Labels).
 *
 * Konvention (in CLAUDE.md "Harte Regeln" verankert):
 *   - Niemals toLocaleDateString() / toLocaleString() direkt in Page-JS
 *   - Niemals new Intl.DateTimeFormat(...) direkt
 *   - Immer über AppFormat.* gehen
 *
 * Lade-Reihenfolge: VOR allen anderen Page-JS-Modulen (in <head> oder
 * früh im Body). Aktuell wird das Skript in Phase 1 noch nicht von
 * Pages geladen — wird erstmals genutzt sobald die ersten Admin-JS-
 * Komponenten in Phase 3 dazukommen.
 */
(function (global) {
    'use strict';

    /**
     * Parst ISO-String oder Date-Objekt in ein Date.
     * Akzeptiert "2026-04-12", "2026-04-12 14:30:00", "2026-04-12T14:30:00".
     * Bei ungültigem Input: null.
     */
    function parseIso(input) {
        if (!input) return null;
        if (input instanceof Date) return isNaN(input) ? null : input;
        // MariaDB-Format mit Leerzeichen → ISO-T-Variante
        const normalized = String(input).replace(' ', 'T');
        const d = new Date(normalized);
        return isNaN(d) ? null : d;
    }

    function pad2(n) { return n < 10 ? '0' + n : '' + n; }

    const AppFormat = {
        /**
         * Datum: TT.MM.JJJJ.
         * Bei ungültigem Input: "–".
         */
        date(iso) {
            const d = parseIso(iso);
            if (!d) return '–';
            return pad2(d.getDate()) + '.' + pad2(d.getMonth() + 1) + '.' + d.getFullYear();
        },

        /**
         * Datum + Uhrzeit: TT.MM.JJJJ, HH:MM.
         */
        dateTime(iso) {
            const d = parseIso(iso);
            if (!d) return '–';
            return pad2(d.getDate()) + '.' + pad2(d.getMonth() + 1) + '.' + d.getFullYear()
                + ', ' + pad2(d.getHours()) + ':' + pad2(d.getMinutes());
        },

        /**
         * Uhrzeit: HH:MM.
         */
        time(iso) {
            const d = parseIso(iso);
            if (!d) return '–';
            return pad2(d.getHours()) + ':' + pad2(d.getMinutes());
        },

        /**
         * Relative Zeit: "gerade eben", "vor 5 Min", "vor 3h", "vor 2 Tagen".
         * SYNC-PAIR mit PHP::humanTimeDiff() — gleiche Schwellwerte/Labels.
         */
        relative(iso) {
            const d = parseIso(iso);
            if (!d) return '–';
            const diffSec = Math.floor((Date.now() - d.getTime()) / 1000);
            if (diffSec < 60) return 'gerade eben';
            if (diffSec < 3600) return 'vor ' + Math.floor(diffSec / 60) + ' Min';
            if (diffSec < 86400) return 'vor ' + Math.floor(diffSec / 3600) + 'h';
            const days = Math.floor(diffSec / 86400);
            return 'vor ' + days + (days === 1 ? ' Tag' : ' Tagen');
        },
    };

    global.AppFormat = AppFormat;
})(typeof window !== 'undefined' ? window : globalThis);
