-- v11: Master Product List features
--  - Sub-categories (per category)
--  - Product code (unique, auto-generated) — used for the QR code
--  - Product image
--  - Wholesale price
--
-- Run this on an EXISTING database. Fresh installs already get all of
-- this from complete_setup.sql.

-- ── Sub-categories ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS product_sub_categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name        VARCHAR(100) NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_subcat (category_id, name),
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── New columns on products ───────────────────────────────────────────────────
ALTER TABLE products ADD COLUMN IF NOT EXISTS sub_category_id INT UNSIGNED NULL AFTER category_id;
ALTER TABLE products ADD COLUMN IF NOT EXISTS product_code    VARCHAR(50)  NULL AFTER sub_category_id;
ALTER TABLE products ADD COLUMN IF NOT EXISTS image_path      VARCHAR(255) NULL AFTER product_code;
ALTER TABLE products ADD COLUMN IF NOT EXISTS wholesale_price DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER sell_price;

-- FK: deleting a sub-category detaches products (sets NULL), never blocks
ALTER TABLE products
    ADD CONSTRAINT fk_product_subcategory
    FOREIGN KEY (sub_category_id) REFERENCES product_sub_categories(id) ON DELETE SET NULL;

-- Backfill auto product codes for existing rows, then enforce uniqueness
UPDATE products SET product_code = CONCAT('P-', LPAD(id, 5, '0'))
WHERE product_code IS NULL OR product_code = '';

ALTER TABLE products ADD UNIQUE KEY IF NOT EXISTS uq_product_code (product_code);
