-- Migración 001: Tareas programadas de compra.
-- Aplica sobre instalaciones existentes (v1.x → v2.0).
-- Ejecutar: bash scripts/migrate.sh

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS scheduled_purchases (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        INT UNSIGNED NOT NULL,
    asset_id       INT UNSIGNED NOT NULL,
    frequency      ENUM('weekly','monthly_start','monthly_mid','bimonthly','quarterly_start','quarterly_mid') NOT NULL,
    amount_eur     DECIMAL(15,2) NOT NULL,
    commission_eur DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    start_date     DATE NOT NULL,
    end_date       DATE NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    notes          TEXT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sp_user (user_id),
    KEY idx_sp_asset (asset_id),
    CONSTRAINT fk_sp_user  FOREIGN KEY (user_id)  REFERENCES users  (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sp_asset FOREIGN KEY (asset_id) REFERENCES assets (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_sp_amount     CHECK (amount_eur > 0),
    CONSTRAINT chk_sp_commission CHECK (commission_eur >= 0),
    CONSTRAINT chk_sp_dates      CHECK (end_date IS NULL OR end_date >= start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS scheduled_purchase_occurrences (
    id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    scheduled_purchase_id INT UNSIGNED NOT NULL,
    user_id               INT UNSIGNED NOT NULL,
    expected_date         DATE NOT NULL,
    status                ENUM('pending','confirmed','skipped') NOT NULL DEFAULT 'pending',
    confirmed_date        DATE NULL,
    investment_id         INT UNSIGNED NULL,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_spo_schedule_date (scheduled_purchase_id, expected_date),
    KEY idx_spo_user_status  (user_id, status),
    KEY idx_spo_investment   (investment_id),
    CONSTRAINT fk_spo_schedule   FOREIGN KEY (scheduled_purchase_id) REFERENCES scheduled_purchases (id) ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_spo_user       FOREIGN KEY (user_id)               REFERENCES users               (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_spo_investment FOREIGN KEY (investment_id)         REFERENCES investments          (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
