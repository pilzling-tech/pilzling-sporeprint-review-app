-- =====================================================================
-- Sporeprint — Schema v2 (Pilzling-Go-Live-Vorbereitung)
-- DB: pilzling_reviews_app
-- Erstellt: 2026-05-04
-- =====================================================================
-- Erweitert das Schema für Phase-3-Admin-Funktionen:
--   1. widget_configs.theme_overrides JSON — Per-Shop-Theme-Anpassung
--      (siehe ARCHITEKTUR.md "Widget-Theming-Strategie")
--   2. shops.feedback_url_slug + landing_title + landing_text — für
--      die Bewertungs-Landing-Page (Sub-Pfad-Variante:
--      sporeprint.pilzling.eu/feedback?shop=<slug>)
--   3. shops.contact_email — Default-Empfänger für Notifications
--      (kann durch notification_emails-Tabelle erweitert werden)
--   4. notification_emails — mehrere Empfänger pro Shop, Toggle pro
--      Notification-Typ
--
-- Ausführen via phpMyAdmin: DB pilzling_reviews_app wählen, SQL-Tab,
-- Inhalt einfügen, ausführen.
-- =====================================================================

USE pilzling_reviews_app;

-- ---------------------------------------------------------------------
-- 1. widget_configs erweitern: theme_overrides
-- ---------------------------------------------------------------------
ALTER TABLE widget_configs
    ADD COLUMN IF NOT EXISTS theme_overrides JSON NULL COMMENT 'Per-Shop CSS-Variable-Overrides (siehe widget.js THEME_OVERRIDE_MAP)';

-- ---------------------------------------------------------------------
-- 2. shops erweitern: Bewertungs-Landing-Page-Konfig + Kontakt-Email
-- ---------------------------------------------------------------------
ALTER TABLE shops
    ADD COLUMN IF NOT EXISTS feedback_url_slug VARCHAR(64) NULL COMMENT 'URL-Slug für Bewertungs-Landing-Page (default: shop_id)',
    ADD COLUMN IF NOT EXISTS feedback_landing_title VARCHAR(255) NULL COMMENT 'Title-Tag der Bewertungs-Landing-Page',
    ADD COLUMN IF NOT EXISTS feedback_landing_text TEXT NULL COMMENT 'Begrüßungstext auf der Bewertungs-Landing-Page',
    ADD COLUMN IF NOT EXISTS contact_email VARCHAR(128) NULL COMMENT 'Default-Notification-Empfänger pro Shop';

-- ---------------------------------------------------------------------
-- Initial-Defaults für Bewertungs-Landing-Page (slug = shop_id)
-- ---------------------------------------------------------------------
UPDATE shops SET feedback_url_slug = shop_id WHERE feedback_url_slug IS NULL;

UPDATE shops SET
    feedback_landing_title = CONCAT(name, ' bewerten'),
    feedback_landing_text = CONCAT(
        'Hey! Wenn dir gefällt was wir bei ', name, ' machen — lass uns gerne eine Bewertung da. ',
        'Das hilft uns wirklich weiter! Wähl einfach aus, wo du uns bewerten möchtest.'
    )
WHERE feedback_landing_title IS NULL;

-- ---------------------------------------------------------------------
-- 3. notification_emails — mehrere Empfänger pro Shop möglich
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notification_emails (
    id                       INT          NOT NULL AUTO_INCREMENT,
    shop_id                  VARCHAR(32)  NOT NULL,
    email                    VARCHAR(128) NOT NULL,
    notify_new_review        TINYINT(1)   NOT NULL DEFAULT 1,
    notify_reply_received    TINYINT(1)   NOT NULL DEFAULT 1,
    notify_negative_only     TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Wenn 1: nur bei <=3 Sternen benachrichtigen',
    is_active                TINYINT(1)   NOT NULL DEFAULT 1,
    created_at               TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_shop_email (shop_id, email),
    KEY idx_shop_active (shop_id, is_active),
    CONSTRAINT fk_notif_shop FOREIGN KEY (shop_id) REFERENCES shops(shop_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Verifikation nach Ausführung:
--   SHOW COLUMNS FROM widget_configs LIKE 'theme_overrides';
--   SHOW COLUMNS FROM shops LIKE 'feedback_%';
--   SELECT shop_id, feedback_url_slug, feedback_landing_title FROM shops;
--   SHOW TABLES LIKE 'notification_emails';
-- =====================================================================
