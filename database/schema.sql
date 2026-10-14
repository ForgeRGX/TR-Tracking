SET NAMES utf8mb4;

-- Usuarios de la aplicación.
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    language      CHAR(2) NOT NULL DEFAULT 'es',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_users_username UNIQUE (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Intentos de login fallidos (limitación de fuerza bruta).
CREATE TABLE IF NOT EXISTS login_attempts (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip         VARCHAR(45) NOT NULL,
    username   VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_attempts_ip (ip, created_at),
    KEY idx_login_attempts_user (username, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tipos de activo (propios de cada usuario).
CREATE TABLE IF NOT EXISTS asset_types (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    name        VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT uq_asset_types_user_name UNIQUE (user_id, name),
    CONSTRAINT fk_asset_types_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Activos concretos. ticker e isin son opcionales (p. ej. Private Markets).
CREATE TABLE IF NOT EXISTS assets (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id          INT UNSIGNED NOT NULL,
    asset_type_id    INT UNSIGNED NOT NULL,
    name             VARCHAR(150) NOT NULL,
    ticker           VARCHAR(20) NULL,
    isin             CHAR(12) NULL,
    currency         CHAR(3) NOT NULL DEFAULT 'EUR',
    description      TEXT NULL,
    -- Reservado para futuras versiones (valoración). No se usa en la v1.
    current_price    DECIMAL(18,6) NULL,
    price_updated_at DATETIME NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT uq_assets_type_name UNIQUE (asset_type_id, name),
    KEY idx_assets_user (user_id),
    KEY idx_assets_name (name),
    KEY idx_assets_ticker (ticker),
    KEY idx_assets_isin (isin),
    CONSTRAINT fk_assets_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_assets_type FOREIGN KEY (asset_type_id)
        REFERENCES asset_types (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Cada fila es una operación de compra.
CREATE TABLE IF NOT EXISTS investments (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        INT UNSIGNED NOT NULL,
    asset_id       INT UNSIGNED NOT NULL,
    -- buy = compra, sell = venta (el importe siempre es positivo; el tipo da el signo).
    operation_type ENUM('buy','sell') NOT NULL DEFAULT 'buy',
    amount_eur     DECIMAL(15,2) NOT NULL,
    purchase_date  DATE NOT NULL,
    commission_eur DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    -- Reservado para futuras versiones (unidades y precio de compra). No se usa en la v1.
    units          DECIMAL(24,10) NULL,
    unit_price     DECIMAL(18,6) NULL,
    notes          TEXT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_investments_user_date (user_id, purchase_date),
    KEY idx_investments_user_op_date (user_id, operation_type, purchase_date),
    KEY idx_investments_date (purchase_date),
    KEY idx_investments_asset_date (asset_id, purchase_date),
    CONSTRAINT fk_investments_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_investments_asset FOREIGN KEY (asset_id)
        REFERENCES assets (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_investments_amount CHECK (amount_eur > 0),
    CONSTRAINT chk_investments_commission CHECK (commission_eur >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tareas programadas de compra (DCA automático configurable por el usuario).
CREATE TABLE IF NOT EXISTS scheduled_purchases (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        INT UNSIGNED NOT NULL,
    asset_id       INT UNSIGNED NOT NULL,
    -- weekly=cada lunes · monthly_start=1er día lab. de mes · monthly_mid=1er día lab. desde el 15
    -- bimonthly=ambos · quarterly_start/mid=cada 3 meses
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

-- Ocurrencias generadas de cada tarea programada (una fila por fecha esperada).
CREATE TABLE IF NOT EXISTS scheduled_purchase_occurrences (
    id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    scheduled_purchase_id INT UNSIGNED NOT NULL,
    user_id               INT UNSIGNED NOT NULL,
    expected_date         DATE NOT NULL,
    -- pending=esperando confirmación · confirmed=registrada como inversión · skipped=omitida
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
