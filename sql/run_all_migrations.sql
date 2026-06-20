-- ============================================================
-- COMBINED MIGRATION FILE
-- Run this in phpMyAdmin on your existing database.
-- Safe to run even if some tables already exist.
-- ============================================================

-- ── v2: Branch System ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS branches (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)  NOT NULL,
    address     TEXT          DEFAULT NULL,
    phone       VARCHAR(20)   DEFAULT NULL,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS branch_id INT UNSIGNED NULL DEFAULT NULL AFTER role;

ALTER TABLE stock_inbound
    ADD COLUMN IF NOT EXISTS branch_id INT UNSIGNED NULL DEFAULT NULL AFTER supplier_id;

ALTER TABLE sales
    ADD COLUMN IF NOT EXISTS branch_id INT UNSIGNED NULL DEFAULT NULL AFTER customer_id;

-- ── v4: Stock Adjustments & Transfers ───────────────────────
CREATE TABLE IF NOT EXISTS stock_adjustments (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    branch_id  INT UNSIGNED DEFAULT NULL,
    quantity   DECIMAL(12,2) NOT NULL,
    reason     ENUM('damage','count_correction','return','other') NOT NULL DEFAULT 'other',
    note       TEXT DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (branch_id)  REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stock_transfers (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id     INT UNSIGNED NOT NULL,
    from_branch_id INT UNSIGNED NOT NULL,
    to_branch_id   INT UNSIGNED NOT NULL,
    quantity       DECIMAL(12,2) NOT NULL,
    note           TEXT DEFAULT NULL,
    created_by     INT UNSIGNED DEFAULT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id)     REFERENCES products(id),
    FOREIGN KEY (from_branch_id) REFERENCES branches(id),
    FOREIGN KEY (to_branch_id)   REFERENCES branches(id),
    FOREIGN KEY (created_by)     REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── v5: Manager Role ─────────────────────────────────────────
ALTER TABLE users
    MODIFY COLUMN role ENUM('admin','manager','staff') NOT NULL DEFAULT 'staff';

-- ── v6: Dynamic Product Categories ───────────────────────────
CREATE TABLE IF NOT EXISTS product_categories (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_category_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO product_categories (name) VALUES ('Rod'), ('Cement');

ALTER TABLE products ADD COLUMN IF NOT EXISTS category_id INT UNSIGNED NULL AFTER id;

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE name = 'Rod'    LIMIT 1) WHERE type = 'rod'    AND category_id IS NULL;
UPDATE products SET category_id = (SELECT id FROM product_categories WHERE name = 'Cement' LIMIT 1) WHERE type = 'cement' AND category_id IS NULL;

-- Set a default category for any products still missing one
UPDATE products SET category_id = (SELECT id FROM product_categories LIMIT 1) WHERE category_id IS NULL;

ALTER TABLE products MODIFY COLUMN category_id INT UNSIGNED NOT NULL;

-- Add FK only if it doesn't exist yet
SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'products'
      AND CONSTRAINT_NAME = 'fk_product_category'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE products ADD CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE RESTRICT',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Drop old type column if it still exists
ALTER TABLE products DROP COLUMN IF EXISTS type;

-- ── v7: Customer Notes ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS customer_notes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    note        TEXT         NOT NULL,
    created_by  INT UNSIGNED DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by)  REFERENCES users(id)     ON DELETE SET NULL,
    INDEX idx_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── v8: Free Notes ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS free_notes (
    id            INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(150)   NOT NULL,
    note          TEXT           NOT NULL,
    note_date     DATE           NOT NULL,
    author        VARCHAR(100)   NOT NULL DEFAULT '',
    is_pinned     TINYINT(1)     NOT NULL DEFAULT 0,
    status        ENUM('pending','done') NOT NULL DEFAULT 'pending',
    created_at    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE free_notes ADD COLUMN IF NOT EXISTS is_pinned TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE free_notes ADD COLUMN IF NOT EXISTS status ENUM('pending','done') NOT NULL DEFAULT 'pending';

-- ── v9: Quotations, Sale Returns, Installments ───────────────
CREATE TABLE IF NOT EXISTS quotations (
    id            INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    quote_number  VARCHAR(30)    NOT NULL,
    customer_name VARCHAR(150)   NOT NULL DEFAULT '',
    customer_id   INT UNSIGNED   DEFAULT NULL,
    quote_date    DATE           NOT NULL,
    valid_days    INT            NOT NULL DEFAULT 7,
    subtotal      DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
    discount      DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
    total_amount  DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
    status        ENUM('active','converted','cancelled') NOT NULL DEFAULT 'active',
    note          TEXT,
    created_by    INT UNSIGNED   DEFAULT NULL,
    created_at    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quotation_items (
    id           INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    quotation_id INT UNSIGNED  NOT NULL,
    product_id   INT UNSIGNED  NOT NULL,
    product_name VARCHAR(150)  NOT NULL DEFAULT '',
    quantity     DECIMAL(12,2) NOT NULL,
    unit_price   DECIMAL(12,2) NOT NULL,
    total_price  DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sale_returns (
    id           INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    sale_id      INT UNSIGNED  NOT NULL,
    return_date  DATE          NOT NULL,
    reason       VARCHAR(500)  NOT NULL DEFAULT '',
    total_refund DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    note         TEXT,
    created_by   INT UNSIGNED  DEFAULT NULL,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sale_return_items (
    id            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    return_id     INT UNSIGNED  NOT NULL,
    product_id    INT UNSIGNED  NOT NULL,
    product_name  VARCHAR(150)  NOT NULL DEFAULT '',
    quantity      DECIMAL(12,2) NOT NULL,
    unit_price    DECIMAL(12,2) NOT NULL,
    refund_amount DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (return_id) REFERENCES sale_returns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS installment_plans (
    id                 INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    customer_name      VARCHAR(150)  NOT NULL,
    customer_id        INT UNSIGNED  DEFAULT NULL,
    sale_id            INT UNSIGNED  DEFAULT NULL,
    total_amount       DECIMAL(12,2) NOT NULL,
    down_payment       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    installment_count  INT           NOT NULL,
    installment_amount DECIMAL(12,2) NOT NULL,
    start_date         DATE          NOT NULL,
    status             ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
    note               TEXT,
    created_by         INT UNSIGNED  DEFAULT NULL,
    created_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS installments (
    id             INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    plan_id        INT UNSIGNED  NOT NULL,
    installment_no INT           NOT NULL,
    due_date       DATE          NOT NULL,
    amount         DECIMAL(12,2) NOT NULL,
    paid_amount    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    paid_date      DATE          DEFAULT NULL,
    status         ENUM('pending','paid','overdue') NOT NULL DEFAULT 'pending',
    note           VARCHAR(500)  DEFAULT NULL,
    FOREIGN KEY (plan_id) REFERENCES installment_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── v10: Expense Tracking ────────────────────────────────────
CREATE TABLE IF NOT EXISTS expense_categories (
    id         INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)   NOT NULL,
    icon       VARCHAR(50)    NOT NULL DEFAULT 'bi-receipt',
    created_at DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO expense_categories (id, name, icon) VALUES
(1, 'Rent',             'bi-house-door'),
(2, 'Salary',           'bi-person-badge'),
(3, 'Electricity bill', 'bi-lightning-charge'),
(4, 'Internet',         'bi-wifi'),
(5, 'Transport',        'bi-truck'),
(6, 'Repair',           'bi-tools'),
(7, 'Misc',             'bi-three-dots');

CREATE TABLE IF NOT EXISTS expenses (
    id           INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    category_id  INT UNSIGNED   DEFAULT NULL,
    branch_id    INT UNSIGNED   DEFAULT NULL,
    amount       DECIMAL(12,2)  NOT NULL,
    expense_date DATE           NOT NULL,
    description  TEXT,
    created_by   INT UNSIGNED   DEFAULT NULL,
    created_at   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Rebuild views (final version using product_categories) ───
CREATE OR REPLACE VIEW vw_current_stock AS
SELECT
    p.id          AS product_id,
    p.name        AS product_name,
    pc.name       AS product_type,
    p.category_id,
    p.size_brand,
    p.unit,
    p.buy_price,
    p.sell_price,
    p.min_stock,
    COALESCE((SELECT SUM(si.quantity) FROM stock_inbound si WHERE si.product_id = p.id), 0) AS total_inbound,
    COALESCE((SELECT SUM(sai.quantity) FROM sale_items sai JOIN sales s ON s.id = sai.sale_id WHERE sai.product_id = p.id AND s.status = 'completed'), 0) AS total_sold,
    COALESCE((SELECT SUM(sa.quantity)  FROM stock_adjustments sa WHERE sa.product_id = p.id), 0) AS total_adjustments,
    COALESCE((SELECT SUM(si.quantity) FROM stock_inbound si WHERE si.product_id = p.id), 0)
        + COALESCE((SELECT SUM(sa.quantity) FROM stock_adjustments sa WHERE sa.product_id = p.id), 0)
        - COALESCE((SELECT SUM(sai.quantity) FROM sale_items sai JOIN sales s ON s.id = sai.sale_id WHERE sai.product_id = p.id AND s.status = 'completed'), 0)
        AS current_stock
FROM   products p
JOIN   product_categories pc ON pc.id = p.category_id
WHERE  p.is_active = 1;

CREATE OR REPLACE VIEW vw_branch_stock AS
SELECT
    b.id    AS branch_id,
    b.name  AS branch_name,
    p.id    AS product_id,
    p.name  AS product_name,
    pc.name AS product_type,
    p.category_id,
    p.size_brand,
    p.unit,
    p.buy_price,
    p.sell_price,
    p.min_stock,
    COALESCE((SELECT SUM(si.quantity) FROM stock_inbound si WHERE si.product_id = p.id AND si.branch_id = b.id), 0) AS total_inbound,
    COALESCE((SELECT SUM(sai.quantity) FROM sale_items sai JOIN sales s ON s.id = sai.sale_id WHERE sai.product_id = p.id AND s.branch_id = b.id AND s.status = 'completed'), 0) AS total_sold,
    COALESCE((SELECT SUM(sa.quantity)  FROM stock_adjustments sa WHERE sa.product_id = p.id AND sa.branch_id = b.id), 0) AS total_adjustments,
    COALESCE((SELECT SUM(st.quantity)  FROM stock_transfers st WHERE st.product_id = p.id AND st.to_branch_id   = b.id), 0) AS total_transferred_in,
    COALESCE((SELECT SUM(st.quantity)  FROM stock_transfers st WHERE st.product_id = p.id AND st.from_branch_id = b.id), 0) AS total_transferred_out,
    COALESCE((SELECT SUM(si.quantity) FROM stock_inbound si WHERE si.product_id = p.id AND si.branch_id = b.id), 0)
        + COALESCE((SELECT SUM(sa.quantity) FROM stock_adjustments sa WHERE sa.product_id = p.id AND sa.branch_id = b.id), 0)
        + COALESCE((SELECT SUM(st.quantity) FROM stock_transfers st WHERE st.product_id = p.id AND st.to_branch_id   = b.id), 0)
        - COALESCE((SELECT SUM(st.quantity) FROM stock_transfers st WHERE st.product_id = p.id AND st.from_branch_id = b.id), 0)
        - COALESCE((SELECT SUM(sai.quantity) FROM sale_items sai JOIN sales s ON s.id = sai.sale_id WHERE sai.product_id = p.id AND s.branch_id = b.id AND s.status = 'completed'), 0)
        AS current_stock
FROM   branches b
CROSS  JOIN products p
JOIN   product_categories pc ON pc.id = p.category_id
WHERE  p.is_active = 1
  AND  b.is_active = 1;

CREATE OR REPLACE VIEW vw_customer_dues AS
SELECT
    c.id            AS customer_id,
    c.name          AS customer_name,
    c.phone,
    COALESCE(SUM(s.due_amount), 0)   AS total_due,
    COALESCE(SUM(s.total_amount), 0) AS total_purchase,
    COALESCE(SUM(s.paid_amount), 0)  AS total_paid
FROM   customers c
LEFT JOIN sales s ON s.customer_id = c.id AND s.status = 'completed'
GROUP BY c.id;
