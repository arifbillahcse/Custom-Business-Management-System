<?php

require_once __DIR__ . '/BaseModel.php';

class Product extends BaseModel
{
    protected static string $table = 'products';

    public static function addProduct(
        int     $categoryId,
        string  $name,
        string  $sizeBrand,
        string  $unit,
        float   $buyPrice,
        float   $sellPrice,
        float   $minStock,
        ?int    $subCategoryId  = null,
        float   $wholesalePrice = 0.0,
        string  $productCode    = '',
        ?string $imagePath      = null
    ): int|string {
        $name        = trim($name);
        $productCode = trim($productCode);

        if ($categoryId <= 0)     return 'INVALID_CATEGORY';
        if ($name === '')         return 'NAME_REQUIRED';
        if ($buyPrice  <= 0)      return 'INVALID_BUY_PRICE';
        if ($sellPrice <= 0)      return 'INVALID_SELL_PRICE';
        if ($wholesalePrice < 0)  return 'INVALID_WHOLESALE';
        if ($minStock  <  0)      return 'INVALID_MIN_STOCK';

        if (!Category::exists($categoryId)) return 'INVALID_CATEGORY';

        // Sub-category is optional, but when given it must belong to the category
        if ($subCategoryId !== null && $subCategoryId > 0) {
            if (!Category::subCategoryBelongsTo($subCategoryId, $categoryId)) {
                return 'INVALID_SUBCATEGORY';
            }
        } else {
            $subCategoryId = null;
        }

        $exists = Database::fetchOne(
            'SELECT id FROM products
             WHERE category_id = ? AND name = ? AND IFNULL(size_brand,"") = ?
               AND is_active = 1 LIMIT 1',
            [$categoryId, $name, trim($sizeBrand)]
        );
        if ($exists) return 'DUPLICATE';

        if ($productCode !== '' && self::codeTaken($productCode)) {
            return 'DUPLICATE_CODE';
        }

        $id = (int) Database::insert(
            'INSERT INTO products
             (category_id, sub_category_id, product_code, image_path, name, size_brand,
              unit, buy_price, sell_price, wholesale_price, min_stock)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, $subCategoryId, $productCode !== '' ? $productCode : null, $imagePath,
             $name, trim($sizeBrand), trim($unit), $buyPrice, $sellPrice, $wholesalePrice, $minStock]
        );

        // Auto-generate the code when none was provided
        if ($productCode === '') {
            Database::execute(
                "UPDATE products SET product_code = CONCAT('P-', LPAD(id, 5, '0')) WHERE id = ?",
                [$id]
            );
        }

        self::log('create_product', 'products', $id, "Added product: $name");
        return $id;
    }

    private static function codeTaken(string $code, int $exceptId = 0): bool
    {
        return (bool) Database::fetchOne(
            'SELECT id FROM products WHERE product_code = ? AND id <> ? LIMIT 1',
            [$code, $exceptId]
        );
    }

    public static function getProducts(?int $categoryId = null): array
    {
        if ($categoryId !== null && $categoryId > 0) {
            return Database::fetchAll(
                'SELECT p.*, pc.name AS category_name, sc.name AS sub_category_name
                 FROM products p
                 JOIN product_categories pc ON pc.id = p.category_id
                 LEFT JOIN product_sub_categories sc ON sc.id = p.sub_category_id
                 WHERE p.category_id = ? AND p.is_active = 1
                 ORDER BY p.name',
                [$categoryId]
            );
        }
        return Database::fetchAll(
            'SELECT p.*, pc.name AS category_name, sc.name AS sub_category_name
             FROM products p
             JOIN product_categories pc ON pc.id = p.category_id
             LEFT JOIN product_sub_categories sc ON sc.id = p.sub_category_id
             WHERE p.is_active = 1
             ORDER BY pc.name, p.name'
        );
    }

    public static function getProductById(int $id): array|false
    {
        return Database::fetchOne(
            'SELECT p.*, pc.name AS category_name, sc.name AS sub_category_name
             FROM products p
             JOIN product_categories pc ON pc.id = p.category_id
             LEFT JOIN product_sub_categories sc ON sc.id = p.sub_category_id
             WHERE p.id = ? AND p.is_active = 1 LIMIT 1',
            [$id]
        );
    }

    public static function updateProduct(int $id, array $data): bool|string
    {
        $product = self::getProductById($id);
        if (!$product) return 'NOT_FOUND';

        $categoryId = isset($data['category_id']) ? (int)$data['category_id'] : (int)$product['category_id'];
        $name       = trim($data['name']       ?? $product['name']);
        $sizeBrand  = trim($data['size_brand'] ?? $product['size_brand']);
        $unit       = trim($data['unit']       ?? $product['unit']);
        $buyPrice   = (float)($data['buy_price']  ?? $product['buy_price']);
        $sellPrice  = (float)($data['sell_price'] ?? $product['sell_price']);
        $minStock   = (float)($data['min_stock']  ?? $product['min_stock']);
        $wholesale  = (float)($data['wholesale_price'] ?? $product['wholesale_price'] ?? 0);

        // Blank code keeps the existing one (codes are never removed)
        $productCode = trim($data['product_code'] ?? '');
        if ($productCode === '') $productCode = (string)($product['product_code'] ?? '');

        $subCategoryId = array_key_exists('sub_category_id', $data)
            ? (int)$data['sub_category_id']
            : (int)($product['sub_category_id'] ?? 0);

        // New image replaces the old one; otherwise keep what's there
        $imagePath = array_key_exists('image_path', $data) && $data['image_path'] !== null
            ? $data['image_path']
            : ($product['image_path'] ?? null);

        if ($categoryId <= 0)  return 'INVALID_CATEGORY';
        if ($name === '')      return 'NAME_REQUIRED';
        if ($buyPrice  <= 0)   return 'INVALID_BUY_PRICE';
        if ($sellPrice <= 0)   return 'INVALID_SELL_PRICE';
        if ($wholesale <  0)   return 'INVALID_WHOLESALE';
        if ($minStock  <  0)   return 'INVALID_MIN_STOCK';

        if (!Category::exists($categoryId)) return 'INVALID_CATEGORY';

        if ($subCategoryId > 0) {
            if (!Category::subCategoryBelongsTo($subCategoryId, $categoryId)) {
                return 'INVALID_SUBCATEGORY';
            }
        } else {
            $subCategoryId = null;
        }

        $dup = Database::fetchOne(
            'SELECT id FROM products
             WHERE category_id = ? AND name = ? AND IFNULL(size_brand,"") = ?
               AND is_active = 1 AND id <> ? LIMIT 1',
            [$categoryId, $name, $sizeBrand, $id]
        );
        if ($dup) return 'DUPLICATE';

        if ($productCode !== '' && self::codeTaken($productCode, $id)) {
            return 'DUPLICATE_CODE';
        }

        Database::execute(
            'UPDATE products SET
                category_id = ?, sub_category_id = ?, product_code = ?, image_path = ?,
                name = ?, size_brand = ?, unit = ?,
                buy_price = ?, sell_price = ?, wholesale_price = ?, min_stock = ?
             WHERE id = ?',
            [$categoryId, $subCategoryId, $productCode !== '' ? $productCode : null, $imagePath,
             $name, $sizeBrand, $unit, $buyPrice, $sellPrice, $wholesale, $minStock, $id]
        );

        self::log('update_product', 'products', $id, "Updated product: $name");
        return true;
    }

    public static function deleteProduct(int $id): bool|string
    {
        $product = self::getProductById($id);
        if (!$product) return 'NOT_FOUND';

        $inStock = Database::fetchOne(
            'SELECT id FROM stock_inbound WHERE product_id = ? LIMIT 1', [$id]
        );
        $inSales = Database::fetchOne(
            'SELECT id FROM sale_items WHERE product_id = ? LIMIT 1', [$id]
        );
        if ($inStock || $inSales) return 'HAS_HISTORY';

        Database::execute('UPDATE products SET is_active = 0 WHERE id = ?', [$id]);
        self::log('delete_product', 'products', $id, "Deleted product: {$product['name']}");
        return true;
    }

    public static function checkMinStock(): array
    {
        return Database::fetchAll(
            'SELECT * FROM vw_current_stock
             WHERE current_stock <= min_stock AND min_stock > 0
             ORDER BY current_stock ASC'
        );
    }

    public static function errorMessage(string $code): string
    {
        return [
            'INVALID_CATEGORY'   => 'Select a valid category.',
            'NAME_REQUIRED'      => 'Enter the product name.',
            'INVALID_BUY_PRICE'  => 'Purchase price must be greater than 0.',
            'INVALID_SELL_PRICE' => 'Sell price must be greater than 0.',
            'INVALID_MIN_STOCK'  => 'Minimum stock cannot be negative.',
            'INVALID_WHOLESALE'  => 'Wholesale price cannot be negative.',
            'INVALID_SUBCATEGORY'=> 'The sub-category does not belong to the selected category.',
            'DUPLICATE'          => 'This product already exists.',
            'DUPLICATE_CODE'     => 'This product code is already in use.',
            'NOT_FOUND'          => 'Product not found.',
            'HAS_HISTORY'        => 'This product has stock/sales records and cannot be deleted.',
            'IMG_TYPE'           => 'Only JPG, PNG or WEBP images are allowed.',
            'IMG_TOO_LARGE'      => 'Image must be smaller than 2 MB.',
            'IMG_UPLOAD_FAILED'  => 'Image upload failed. Try again.',
        ][$code] ?? 'Something went wrong.';
    }
}
