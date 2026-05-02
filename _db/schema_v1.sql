-- =====================================================================
-- Sporeprint — Schema v1 (Initial-Migration)
-- DB: pilzling_reviews_app
-- Erstellt: 2026-05-03
-- =====================================================================
-- Fuehrt 6 Tabellen ein + Initial-Daten fuer 3 Shops + Default-Widget-Configs.
-- Naming: durchgaengig English (created_at, posted_at, is_active etc.).
-- Schema-Korrekturen aus Pre-Check (Konzept Sektion C) bereits eingearbeitet:
--   C2: review_replies aufgesplittet (created_at vs. external_posted_at)
--   C3: rate_limits als Sliding-Window mit bucket_minute
--   C7: visibility ENUM mit DEFAULT 'visible'
--   C8: zwei Admin-Filter-Indexe auf reviews
--
-- Ausfuehren via phpMyAdmin: DB pilzling_reviews_app waehlen, SQL-Tab,
-- Inhalt einfuegen, ausfuehren.
-- =====================================================================

USE pilzling_reviews_app;

-- ---------------------------------------------------------------------
-- 1. shops — Stammdaten der 3 Shops
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shops (
  shop_id            VARCHAR(32)  NOT NULL,
  name               VARCHAR(128) NOT NULL,
  domain             VARCHAR(128) NOT NULL,
  google_place_id    VARCHAR(64)  NULL,
  trustpilot_unit_id VARCHAR(64)  NULL,
  jtl_api_url        VARCHAR(255) NULL,
  ci_primary         VARCHAR(7)   NULL,
  ci_secondary       VARCHAR(7)   NULL,
  created_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (shop_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. reviews — alle Bewertungen polymorph ueber source-ENUM
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
  review_id      INT          NOT NULL AUTO_INCREMENT,
  shop_id        VARCHAR(32)  NOT NULL,
  source         ENUM('google','trustpilot','jtl') NOT NULL,
  external_id    VARCHAR(128) NOT NULL,
  stars          TINYINT      NOT NULL,
  author         VARCHAR(255) NULL,
  content        TEXT         NULL,
  language       VARCHAR(8)   NULL,
  product_name   VARCHAR(255) NULL,
  product_sku    VARCHAR(64)  NULL,
  posted_at      DATETIME     NOT NULL,
  fetched_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  visibility     ENUM('visible','hidden','flagged') NOT NULL DEFAULT 'visible',
  PRIMARY KEY (review_id),
  UNIQUE KEY uniq_source (shop_id, source, external_id),
  KEY idx_shop_source_date (shop_id, source, posted_at DESC),
  KEY idx_shop_stars (shop_id, stars),
  KEY idx_shop_visibility (shop_id, visibility),
  CONSTRAINT fk_reviews_shop FOREIGN KEY (shop_id) REFERENCES shops(shop_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. review_replies — Antworten (1:1, getrennte created_at vs. external_posted_at)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS review_replies (
  reply_id            INT          NOT NULL AUTO_INCREMENT,
  review_id           INT          NOT NULL,
  content             TEXT         NOT NULL,
  created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  external_posted_at  DATETIME     NULL,
  posted_by           VARCHAR(64)  NULL,
  external_status     ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  external_error      TEXT         NULL,
  PRIMARY KEY (reply_id),
  KEY idx_review (review_id),
  CONSTRAINT fk_replies_review FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. sync_runs — Cron-Lauf-Protokoll
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sync_runs (
  run_id          INT          NOT NULL AUTO_INCREMENT,
  shop_id         VARCHAR(32)  NOT NULL,
  source          ENUM('google','trustpilot','jtl') NOT NULL,
  started_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at     TIMESTAMP    NULL,
  status          ENUM('running','ok','error') NOT NULL DEFAULT 'running',
  reviews_new     INT          NOT NULL DEFAULT 0,
  reviews_updated INT          NOT NULL DEFAULT 0,
  error_message   TEXT         NULL,
  PRIMARY KEY (run_id),
  KEY idx_shop_started (shop_id, started_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. widget_configs — Pro-Shop-Widget-Settings
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS widget_configs (
  shop_id              VARCHAR(32) NOT NULL,
  layout               ENUM('carousel','feed') NOT NULL DEFAULT 'carousel',
  min_stars            TINYINT     NOT NULL DEFAULT 4,
  max_items            SMALLINT    NOT NULL DEFAULT 20,
  show_product_reviews TINYINT(1)  NOT NULL DEFAULT 1,
  custom_css           TEXT        NULL,
  PRIMARY KEY (shop_id),
  CONSTRAINT fk_widget_configs_shop FOREIGN KEY (shop_id) REFERENCES shops(shop_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. rate_limits — Sliding-Window-Counter (Korrektur C3)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rate_limits (
  ip_address     VARBINARY(16) NOT NULL,
  bucket_minute  INT UNSIGNED  NOT NULL,
  request_count  INT           NOT NULL DEFAULT 1,
  PRIMARY KEY (ip_address, bucket_minute),
  KEY idx_bucket (bucket_minute)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Initial-Daten: 3 Shops vorbefuellen
-- ---------------------------------------------------------------------
-- Domains sind Platzhalter — finale Werte beim ersten OAuth-Setup eintragen
INSERT INTO shops (shop_id, name, domain) VALUES
  ('pilzling',    'Pilzling',    'pilzling.shop'),
  ('pilzwald',    'Pilzwald',    'pilzwald.de'),
  ('shroom-boom', 'Shroom Boom', 'shroom-boom.de')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ---------------------------------------------------------------------
-- Initial-Daten: Default-Widget-Configs fuer die 3 Shops
-- ---------------------------------------------------------------------
INSERT INTO widget_configs (shop_id, layout, min_stars, max_items, show_product_reviews) VALUES
  ('pilzling',    'carousel', 4, 20, 1),
  ('pilzwald',    'carousel', 4, 20, 1),
  ('shroom-boom', 'carousel', 4, 20, 1)
ON DUPLICATE KEY UPDATE layout = VALUES(layout);

-- =====================================================================
-- Verifikation nach Ausfuehrung:
--   SHOW TABLES;                            -- erwartet 6 Tabellen
--   SELECT shop_id, name FROM shops;        -- erwartet 3 Zeilen
--   SELECT shop_id, layout FROM widget_configs;  -- erwartet 3 Zeilen
-- =====================================================================
