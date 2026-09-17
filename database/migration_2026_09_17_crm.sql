-- ============================================================
--  ITX CRM — employees + customers + activity log
--  Migration: 2026-09-17
--  Run once against `itx_db`.
-- ============================================================

-- Staff accounts that log into the /crm portal.
-- Created / activated / deactivated by the site admin.
CREATE TABLE IF NOT EXISTS `employees` (
  `id`         int          NOT NULL AUTO_INCREMENT,
  `name`       varchar(100) NOT NULL,
  `username`   varchar(50)  NOT NULL,
  `email`      varchar(150) DEFAULT NULL,
  `phone`      varchar(40)  DEFAULT NULL,
  `password`   varchar(255) NOT NULL,
  `status`     enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` int          DEFAULT NULL,     -- admins.id
  `last_login` datetime     DEFAULT NULL,
  `created_at` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customers / leads. Not tied to a specific employee — every
-- logged-in employee sees and manages all customers.
CREATE TABLE IF NOT EXISTS `customers` (
  `id`            int          NOT NULL AUTO_INCREMENT,
  `name`          varchar(150) NOT NULL,
  `company`       varchar(150) DEFAULT NULL,
  `phone`         varchar(40)  DEFAULT NULL,
  `whatsapp`      varchar(40)  DEFAULT NULL,
  `email`         varchar(150) DEFAULT NULL,
  `city`          varchar(80)  DEFAULT NULL,
  `country`       varchar(80)  DEFAULT NULL,
  `source`        varchar(30)  NOT NULL DEFAULT 'other',
  `status`        varchar(20)  NOT NULL DEFAULT 'new',
  `deal_value`    decimal(14,2) NOT NULL DEFAULT 0.00,
  `currency`      varchar(8)   NOT NULL DEFAULT 'SAR',
  `notes`         text         DEFAULT NULL,
  `next_followup` date         DEFAULT NULL,
  `last_contact`  datetime     DEFAULT NULL,
  `created_by`    int          DEFAULT NULL,   -- employees.id (audit only)
  `created_at`    timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status`   (`status`),
  KEY `idx_source`   (`source`),
  KEY `idx_followup` (`next_followup`),
  KEY `idx_created`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Timeline of interactions / notes / status changes per customer.
CREATE TABLE IF NOT EXISTS `customer_activities` (
  `id`          int         NOT NULL AUTO_INCREMENT,
  `customer_id` int         NOT NULL,
  `employee_id` int         DEFAULT NULL,
  `type`        varchar(20) NOT NULL DEFAULT 'note',
  `content`     text        DEFAULT NULL,
  `old_status`  varchar(20) DEFAULT NULL,
  `new_status`  varchar(20) DEFAULT NULL,
  `created_at`  timestamp   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_emp`      (`employee_id`),
  CONSTRAINT `fk_act_customer` FOREIGN KEY (`customer_id`)
    REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
