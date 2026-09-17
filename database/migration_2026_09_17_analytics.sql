-- ============================================================
--  ITX Analytics — visitor & pageview tracking
--  Migration: 2026-09-17
--  Run once against `itx_db`.
-- ============================================================

-- One row per pageview on the public site.
CREATE TABLE IF NOT EXISTS `page_visits` (
  `id`             bigint       NOT NULL AUTO_INCREMENT,
  `visited_at`     datetime     NOT NULL,
  `visit_date`     date         NOT NULL,
  `visit_hour`     tinyint      NOT NULL DEFAULT 0,
  `weekday`        tinyint      NOT NULL DEFAULT 0,   -- 0=Sunday .. 6=Saturday
  `page_type`      varchar(20)  NOT NULL DEFAULT 'other',
  `page_path`      varchar(255) NOT NULL DEFAULT '',
  `page_title`     varchar(255) DEFAULT NULL,
  `visitor_id`     char(32)     DEFAULT NULL,          -- long-lived cookie (unique visitors)
  `session_id`     char(32)     DEFAULT NULL,          -- session cookie (visits/sessions)
  `is_new_visitor` tinyint(1)   NOT NULL DEFAULT 0,
  `ip_hash`        char(64)     DEFAULT NULL,          -- sha256(ip) for privacy-safe counting
  `ip_addr`        varchar(45)  DEFAULT NULL,          -- used only to resolve geo, then can be purged
  `country`        varchar(80)  DEFAULT NULL,
  `country_code`   char(2)      DEFAULT NULL,
  `city`           varchar(100) DEFAULT NULL,
  `referrer`       varchar(255) DEFAULT NULL,
  `referrer_host`  varchar(120) DEFAULT NULL,
  `device_type`    varchar(12)  DEFAULT NULL,          -- desktop | mobile | tablet
  `browser`        varchar(40)  DEFAULT NULL,
  `os`             varchar(40)  DEFAULT NULL,
  `is_bot`         tinyint(1)   NOT NULL DEFAULT 0,
  `lang`           varchar(10)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_visited_at`  (`visited_at`),
  KEY `idx_visit_date`  (`visit_date`),
  KEY `idx_page_type`   (`page_type`),
  KEY `idx_country`     (`country_code`),
  KEY `idx_visitor`     (`visitor_id`),
  KEY `idx_session`     (`session_id`),
  KEY `idx_bot`         (`is_bot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache of resolved IP → location so we hit the geo API at most once per IP.
CREATE TABLE IF NOT EXISTS `ip_geo` (
  `ip_addr`      varchar(45)  NOT NULL,
  `country`      varchar(80)  DEFAULT NULL,
  `country_code` char(2)      DEFAULT NULL,
  `city`         varchar(100) DEFAULT NULL,
  `resolved_at`  datetime     NOT NULL,
  PRIMARY KEY (`ip_addr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
