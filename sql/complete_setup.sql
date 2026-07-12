-- ============================================================
-- Custom Business Management System — Complete Fresh Install
-- ============================================================
-- Run this file ONCE on an EMPTY database to set up everything.
-- Do NOT run schema.sql or any migration_vN files alongside it.
--
-- Steps in phpMyAdmin:
--   1. Create the database (e.g. demoarif_cs) and select it.
--   2. Import THIS file only.
--
-- Default login: username = admin  |  password = admin123
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. BRANCHES
-- ============================================================
CREATE TABLE IF NOT EXISTS branches (
    id         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150)  NOT NULL,
    address    TEXT          DEFAULT NULL,
    phone      VARCHAR(20)   DEFAULT NULL,
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. USERS  (final schema: role includes 'manager')
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    username   VARCHAR(50)   NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('admin','manager','staff') NOT NULL DEFAULT 'staff',
    branch_id  INT UNSIGNED  DEFAULT NULL COMMENT 'staff/manager branch assignment',
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin account (password: admin123)
INSERT IGNORE INTO users (name, username, password, role)
VALUES ('Administrator', 'admin', '$2y$12$wgUtvV291cMFFRxEd3gKYuz0EjZECg1RqywX49pKfTkkEFpHV/WEe', 'admin');

-- ============================================================
-- 3. PRODUCT CATEGORIES  (replaces old ENUM type on products)
-- ============================================================
CREATE TABLE IF NOT EXISTS product_categories (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_category_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO product_categories (name) VALUES ('Rod'), ('Cement');

-- ============================================================
-- 3b. PRODUCT SUB-CATEGORIES
-- ============================================================
CREATE TABLE IF NOT EXISTS product_sub_categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name        VARCHAR(100) NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_subcat (category_id, name),
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. SUPPLIERS
-- ============================================================
CREATE TABLE IF NOT EXISTS suppliers (
    id         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150)  NOT NULL,
    phone      VARCHAR(20)   DEFAULT NULL,
    address    TEXT          DEFAULT NULL,
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. PRODUCTS  (final schema: category_id FK, no old 'type' column)
-- ============================================================
CREATE TABLE IF NOT EXISTS products (
    id              INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    category_id     INT UNSIGNED  NOT NULL,
    sub_category_id INT UNSIGNED  DEFAULT NULL,
    product_code    VARCHAR(50)   DEFAULT NULL COMMENT 'unique code, also encoded in the QR',
    image_path      VARCHAR(255)  DEFAULT NULL COMMENT 'relative path to uploaded product image',
    name            VARCHAR(150)  NOT NULL,
    size_brand      VARCHAR(100)  DEFAULT NULL COMMENT 'rod size (8mm,10mm…) or cement brand',
    unit            VARCHAR(30)   NOT NULL DEFAULT 'pcs' COMMENT 'ton, bag, pcs…',
    buy_price       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    sell_price      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    wholesale_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    min_stock       DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'alert threshold',
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_product_code (product_code),
    CONSTRAINT fk_product_category
        FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE RESTRICT,
    CONSTRAINT fk_product_subcategory
        FOREIGN KEY (sub_category_id) REFERENCES product_sub_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample products
INSERT IGNORE INTO products (category_id, name, size_brand, unit, buy_price, sell_price, min_stock)
SELECT pc.id, 'Steel Rod 8mm',     '8mm',        'ton', 65000.00, 68000.00, 2 FROM product_categories pc WHERE pc.name = 'Rod'    LIMIT 1;
INSERT IGNORE INTO products (category_id, name, size_brand, unit, buy_price, sell_price, min_stock)
SELECT pc.id, 'Steel Rod 10mm',    '10mm',       'ton', 67000.00, 70000.00, 2 FROM product_categories pc WHERE pc.name = 'Rod'    LIMIT 1;
INSERT IGNORE INTO products (category_id, name, size_brand, unit, buy_price, sell_price, min_stock)
SELECT pc.id, 'Steel Rod 12mm',    '12mm',       'ton', 68000.00, 71000.00, 2 FROM product_categories pc WHERE pc.name = 'Rod'    LIMIT 1;
INSERT IGNORE INTO products (category_id, name, size_brand, unit, buy_price, sell_price, min_stock)
SELECT pc.id, 'Steel Rod 16mm',    '16mm',       'ton', 70000.00, 73000.00, 2 FROM product_categories pc WHERE pc.name = 'Rod'    LIMIT 1;
INSERT IGNORE INTO products (category_id, name, size_brand, unit, buy_price, sell_price, min_stock)
SELECT pc.id, 'Lafarge Cement',    'LAFARGE',    'bag',   480.00,   520.00, 50 FROM product_categories pc WHERE pc.name = 'Cement' LIMIT 1;
INSERT IGNORE INTO products (category_id, name, size_brand, unit, buy_price, sell_price, min_stock)
SELECT pc.id, 'Holcim Cement',     'HOLCIM',     'bag',   475.00,   515.00, 50 FROM product_categories pc WHERE pc.name = 'Cement' LIMIT 1;
INSERT IGNORE INTO products (category_id, name, size_brand, unit, buy_price, sell_price, min_stock)
SELECT pc.id, 'Heidelberg Cement', 'HEIDELBERG', 'bag',   470.00,   510.00, 50 FROM product_categories pc WHERE pc.name = 'Cement' LIMIT 1;
INSERT IGNORE INTO products (category_id, name, size_brand, unit, buy_price, sell_price, min_stock)
SELECT pc.id, 'Shah Cement',       'SHAH',       'bag',   460.00,   500.00, 50 FROM product_categories pc WHERE pc.name = 'Cement' LIMIT 1;

-- Auto-generate product codes for the sample rows
UPDATE products SET product_code = CONCAT('P-', LPAD(id, 5, '0'))
WHERE product_code IS NULL OR product_code = '';

-- ============================================================
-- 6. STOCK INBOUND  (purchases / receiving stock)
-- ============================================================
CREATE TABLE IF NOT EXISTS stock_inbound (
    id           INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    product_id   INT UNSIGNED  NOT NULL,
    supplier_id  INT UNSIGNED  DEFAULT NULL,
    branch_id    INT UNSIGNED  DEFAULT NULL COMMENT 'branch that received stock',
    quantity     DECIMAL(12,2) NOT NULL,
    buy_price    DECIMAL(12,2) NOT NULL COMMENT 'price at time of purchase',
    total_cost   DECIMAL(14,2) GENERATED ALWAYS AS (quantity * buy_price) STORED,
    inbound_date DATE          NOT NULL,
    note         TEXT          DEFAULT NULL,
    created_by   INT UNSIGNED  DEFAULT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id)  REFERENCES products(id)   ON DELETE RESTRICT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)  ON DELETE SET NULL,
    FOREIGN KEY (branch_id)   REFERENCES branches(id)   ON DELETE SET NULL,
    FOREIGN KEY (created_by)  REFERENCES users(id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. STOCK ADJUSTMENTS  (manual +/- corrections)
-- ============================================================
CREATE TABLE IF NOT EXISTS stock_adjustments (
    id         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED  NOT NULL,
    branch_id  INT UNSIGNED  DEFAULT NULL,
    quantity   DECIMAL(12,2) NOT NULL COMMENT 'positive=add, negative=subtract',
    reason     ENUM('damage','count_correction','return','other') NOT NULL DEFAULT 'other',
    note       TEXT          DEFAULT NULL,
    created_by INT UNSIGNED  DEFAULT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)  ON DELETE RESTRICT,
    FOREIGN KEY (branch_id)  REFERENCES branches(id)  ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. STOCK TRANSFERS  (branch-to-branch moves)
-- ============================================================
CREATE TABLE IF NOT EXISTS stock_transfers (
    id             INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    product_id     INT UNSIGNED  NOT NULL,
    from_branch_id INT UNSIGNED  NOT NULL,
    to_branch_id   INT UNSIGNED  NOT NULL,
    quantity       DECIMAL(12,2) NOT NULL,
    note           TEXT          DEFAULT NULL,
    created_by     INT UNSIGNED  DEFAULT NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id)     REFERENCES products(id)  ON DELETE RESTRICT,
    FOREIGN KEY (from_branch_id) REFERENCES branches(id)  ON DELETE RESTRICT,
    FOREIGN KEY (to_branch_id)   REFERENCES branches(id)  ON DELETE RESTRICT,
    FOREIGN KEY (created_by)     REFERENCES users(id)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. CUSTOMERS
-- ============================================================
CREATE TABLE IF NOT EXISTS customers (
    id         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150)  NOT NULL,
    phone      VARCHAR(20)   DEFAULT NULL,
    address    TEXT          DEFAULT NULL,
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default walk-in customer
INSERT IGNORE INTO customers (name, phone) VALUES ('Walk-in Customer', '0000000000');

-- ============================================================
-- 10. SALES
-- ============================================================
CREATE TABLE IF NOT EXISTS sales (
    id             INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(30)   NOT NULL UNIQUE,
    customer_id    INT UNSIGNED  DEFAULT NULL,
    branch_id      INT UNSIGNED  DEFAULT NULL COMMENT 'branch fulfilling the order',
    sale_date      DATE          NOT NULL,
    subtotal       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    discount       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total_amount   DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    paid_amount    DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    due_amount     DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('cash','credit','cheque','mobile_banking') NOT NULL DEFAULT 'cash',
    status         ENUM('completed','cancelled') NOT NULL DEFAULT 'completed',
    note           TEXT          DEFAULT NULL,
    created_by     INT UNSIGNED  DEFAULT NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id)   REFERENCES branches(id)  ON DELETE SET NULL,
    FOREIGN KEY (created_by)  REFERENCES users(id)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. SALE ITEMS
-- ============================================================
CREATE TABLE IF NOT EXISTS sale_items (
    id          INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    sale_id     INT UNSIGNED  NOT NULL,
    product_id  INT UNSIGNED  NOT NULL,
    quantity    DECIMAL(12,2) NOT NULL,
    unit_price  DECIMAL(12,2) NOT NULL,
    total_price DECIMAL(14,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id)    REFERENCES sales(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. PAYMENTS  (due collections)
-- ============================================================
CREATE TABLE IF NOT EXISTS payments (
    id             INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    customer_id    INT UNSIGNED  NOT NULL,
    sale_id        INT UNSIGNED  DEFAULT NULL COMMENT 'linked sale, or NULL for general payment',
    amount         DECIMAL(14,2) NOT NULL,
    payment_method ENUM('cash','cheque','mobile_banking') NOT NULL DEFAULT 'cash',
    reference_no   VARCHAR(100)  DEFAULT NULL COMMENT 'cheque / mobile banking reference',
    payment_date   DATE          NOT NULL,
    note           TEXT          DEFAULT NULL,
    created_by     INT UNSIGNED  DEFAULT NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (sale_id)     REFERENCES sales(id)     ON DELETE SET NULL,
    FOREIGN KEY (created_by)  REFERENCES users(id)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. SALE RETURNS
-- ============================================================
CREATE TABLE IF NOT EXISTS sale_returns (
    id           INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    sale_id      INT UNSIGNED  NOT NULL,
    return_date  DATE          NOT NULL,
    reason       VARCHAR(500)  NOT NULL DEFAULT '',
    total_refund DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    note         TEXT          DEFAULT NULL,
    created_by   INT UNSIGNED  DEFAULT NULL,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. SALE RETURN ITEMS
-- ============================================================
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

-- ============================================================
-- 15. QUOTATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS quotations (
    id            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    quote_number  VARCHAR(30)   NOT NULL,
    customer_name VARCHAR(150)  NOT NULL DEFAULT '',
    customer_id   INT UNSIGNED  DEFAULT NULL,
    quote_date    DATE          NOT NULL,
    valid_days    INT           NOT NULL DEFAULT 7,
    subtotal      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status        ENUM('active','converted','cancelled') NOT NULL DEFAULT 'active',
    note          TEXT          DEFAULT NULL,
    created_by    INT UNSIGNED  DEFAULT NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 16. QUOTATION ITEMS
-- ============================================================
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

-- ============================================================
-- 17. INSTALLMENT PLANS
-- ============================================================
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
    note               TEXT          DEFAULT NULL,
    created_by         INT UNSIGNED  DEFAULT NULL,
    created_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 18. INSTALLMENTS
-- ============================================================
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

-- ============================================================
-- 19. CUSTOMER NOTES
-- ============================================================
CREATE TABLE IF NOT EXISTS customer_notes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    note        TEXT         NOT NULL,
    created_by  INT UNSIGNED DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by)  REFERENCES users(id)     ON DELETE SET NULL,
    INDEX idx_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 20. FREE NOTES  (standalone notepad)
-- ============================================================
CREATE TABLE IF NOT EXISTS free_notes (
    id            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(150)  NOT NULL,
    note          TEXT          NOT NULL,
    note_date     DATE          NOT NULL,
    author        VARCHAR(100)  NOT NULL DEFAULT '',
    is_pinned     TINYINT(1)   NOT NULL DEFAULT 0,
    status        ENUM('pending','done') NOT NULL DEFAULT 'pending',
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 21. EXPENSE CATEGORIES
-- ============================================================
CREATE TABLE IF NOT EXISTS expense_categories (
    id         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    icon       VARCHAR(50)   NOT NULL DEFAULT 'bi-receipt',
    created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO expense_categories (id, name, icon) VALUES
(1, 'Rent',             'bi-house-door'),
(2, 'Salary',           'bi-person-badge'),
(3, 'Electricity bill', 'bi-lightning-charge'),
(4, 'Internet',         'bi-wifi'),
(5, 'Transport',        'bi-truck'),
(6, 'Repair',           'bi-tools'),
(7, 'Misc',             'bi-three-dots');

-- ============================================================
-- 22. EXPENSES
-- ============================================================
CREATE TABLE IF NOT EXISTS expenses (
    id           INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    category_id  INT UNSIGNED  DEFAULT NULL,
    branch_id    INT UNSIGNED  DEFAULT NULL,
    amount       DECIMAL(12,2) NOT NULL,
    expense_date DATE          NOT NULL,
    description  TEXT          DEFAULT NULL,
    created_by   INT UNSIGNED  DEFAULT NULL,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 23. ACTIVITY LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id           INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED  DEFAULT NULL,
    action       VARCHAR(100)  NOT NULL COMMENT 'e.g. create_sale, delete_product',
    module       VARCHAR(50)   NOT NULL COMMENT 'e.g. sales, products, stock',
    reference_id INT UNSIGNED  DEFAULT NULL COMMENT 'ID of affected record',
    description  TEXT          DEFAULT NULL,
    ip_address   VARCHAR(45)   DEFAULT NULL,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 24. SETTINGS
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
    id          INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100)  NOT NULL UNIQUE,
    setting_val TEXT          DEFAULT NULL,
    updated_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (setting_key, setting_val) VALUES
('shop_name',     'My Rod & Cement Store'),
('shop_address',  'Dhaka, Bangladesh'),
('shop_phone',    '01XXXXXXXXX'),
('shop_email',    'shop@example.com'),
('currency',      'BDT'),
('invoice_prefix','INV');

-- ============================================================
-- VIEWS  (use product_categories JOIN — final v6+ structure)
-- ============================================================

-- Global stock per product
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
    COALESCE((SELECT SUM(si.quantity)  FROM stock_inbound    si  WHERE si.product_id  = p.id), 0) AS total_inbound,
    COALESCE((SELECT SUM(sai.quantity) FROM sale_items sai JOIN sales s ON s.id = sai.sale_id WHERE sai.product_id = p.id AND s.status = 'completed'), 0) AS total_sold,
    COALESCE((SELECT SUM(sa.quantity)  FROM stock_adjustments sa WHERE sa.product_id  = p.id), 0) AS total_adjustments,
    COALESCE((SELECT SUM(si.quantity)  FROM stock_inbound    si  WHERE si.product_id  = p.id), 0)
        + COALESCE((SELECT SUM(sa.quantity) FROM stock_adjustments sa WHERE sa.product_id = p.id), 0)
        - COALESCE((SELECT SUM(sai.quantity) FROM sale_items sai JOIN sales s ON s.id = sai.sale_id WHERE sai.product_id = p.id AND s.status = 'completed'), 0)
        AS current_stock
FROM   products p
JOIN   product_categories pc ON pc.id = p.category_id
WHERE  p.is_active = 1;

-- Stock per branch per product
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
    COALESCE((SELECT SUM(si.quantity)  FROM stock_inbound si  WHERE si.product_id = p.id  AND si.branch_id = b.id), 0) AS total_inbound,
    COALESCE((SELECT SUM(sai.quantity) FROM sale_items sai JOIN sales s ON s.id = sai.sale_id WHERE sai.product_id = p.id AND s.branch_id = b.id AND s.status = 'completed'), 0) AS total_sold,
    COALESCE((SELECT SUM(sa.quantity)  FROM stock_adjustments sa WHERE sa.product_id = p.id AND sa.branch_id = b.id), 0) AS total_adjustments,
    COALESCE((SELECT SUM(st.quantity)  FROM stock_transfers st WHERE st.product_id = p.id  AND st.to_branch_id   = b.id), 0) AS total_transferred_in,
    COALESCE((SELECT SUM(st.quantity)  FROM stock_transfers st WHERE st.product_id = p.id  AND st.from_branch_id = b.id), 0) AS total_transferred_out,
    COALESCE((SELECT SUM(si.quantity)  FROM stock_inbound si  WHERE si.product_id = p.id  AND si.branch_id = b.id), 0)
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

-- Customer outstanding dues
CREATE OR REPLACE VIEW vw_customer_dues AS
SELECT
    c.id   AS customer_id,
    c.name AS customer_name,
    c.phone,
    COALESCE(SUM(s.due_amount),   0) AS total_due,
    COALESCE(SUM(s.total_amount), 0) AS total_purchase,
    COALESCE(SUM(s.paid_amount),  0) AS total_paid
FROM   customers c
LEFT JOIN sales s ON s.customer_id = c.id AND s.status = 'completed'
GROUP BY c.id;

-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================
-- Setup complete. Login with: admin / admin123
-- ============================================================
