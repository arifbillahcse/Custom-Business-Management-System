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
