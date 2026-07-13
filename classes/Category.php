<?php

require_once __DIR__ . '/BaseModel.php';

class Category extends BaseModel
{
    protected static string $table = 'product_categories';

    public static function getAll(): array
    {
        return Database::fetchAll('SELECT * FROM product_categories ORDER BY name');
    }

    public static function exists(int $id): bool
    {
        return (bool) Database::fetchOne(
            'SELECT id FROM product_categories WHERE id = ? LIMIT 1', [$id]
        );
    }

    public static function add(string $name): int|string
    {
        $name = trim($name);
        if ($name === '') return 'NAME_REQUIRED';

        $dup = Database::fetchOne(
            'SELECT id FROM product_categories WHERE name = ? LIMIT 1', [$name]
        );
        if ($dup) return 'DUPLICATE';

        return (int) Database::insert(
            'INSERT INTO product_categories (name) VALUES (?)', [$name]
        );
    }

    public static function delete(int $id): bool|string
    {
        $used = Database::fetchOne(
            'SELECT id FROM products WHERE category_id = ? LIMIT 1', [$id]
        );
        if ($used) return 'HAS_PRODUCTS';

        try {
            Database::execute('DELETE FROM product_categories WHERE id = ?', [$id]);
        } catch (\PDOException $e) {
            return 'HAS_PRODUCTS';
        }
        return true;
    }

    // ── Sub-categories ──────────────────────────────────────────────────────

    private static ?bool $subCatTable = null;

    /** True once migration_v11 has created the sub-category table. */
    public static function subCategoriesInstalled(): bool
    {
        if (self::$subCatTable === null) {
            $row = Database::fetchOne(
                "SELECT COUNT(*) AS c FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = 'product_sub_categories'"
            );
            self::$subCatTable = (int)($row['c'] ?? 0) > 0;
        }
        return self::$subCatTable;
    }

    public static function getSubCategories(?int $categoryId = null): array
    {
        if (!self::subCategoriesInstalled()) return [];

        if ($categoryId !== null && $categoryId > 0) {
            return Database::fetchAll(
                'SELECT * FROM product_sub_categories WHERE category_id = ? ORDER BY name',
                [$categoryId]
            );
        }
        return Database::fetchAll(
            'SELECT * FROM product_sub_categories ORDER BY category_id, name'
        );
    }

    public static function subCategoryBelongsTo(int $subCategoryId, int $categoryId): bool
    {
        if (!self::subCategoriesInstalled()) return false;
        return (bool) Database::fetchOne(
            'SELECT id FROM product_sub_categories WHERE id = ? AND category_id = ? LIMIT 1',
            [$subCategoryId, $categoryId]
        );
    }

    public static function addSubCategory(int $categoryId, string $name): int|string
    {
        $name = trim($name);
        if ($name === '')                return 'NAME_REQUIRED';
        if (!self::exists($categoryId))  return 'NOT_FOUND';

        $dup = Database::fetchOne(
            'SELECT id FROM product_sub_categories WHERE category_id = ? AND name = ? LIMIT 1',
            [$categoryId, $name]
        );
        if ($dup) return 'DUPLICATE';

        return (int) Database::insert(
            'INSERT INTO product_sub_categories (category_id, name) VALUES (?, ?)',
            [$categoryId, $name]
        );
    }

    public static function deleteSubCategory(int $id): bool
    {
        // FK on products is ON DELETE SET NULL, so this never blocks —
        // products in this sub-category are simply detached.
        Database::execute('DELETE FROM product_sub_categories WHERE id = ?', [$id]);
        return true;
    }

    public static function errorMessage(string $code): string
    {
        return [
            'NAME_REQUIRED' => 'Enter the category name.',
            'DUPLICATE'     => 'This category already exists.',
            'HAS_PRODUCTS'  => 'This category has products and cannot be deleted.',
            'NOT_FOUND'     => 'Category not found.',
        ][$code] ?? 'Something went wrong.';
    }
}
