<?php

/**
 * Product image upload helper.
 *
 * Validates and stores an uploaded image under uploads/products/.
 * Returns the stored relative path (e.g. "uploads/products/prd_xxx.jpg")
 * or an error code string (IMG_TYPE / IMG_TOO_LARGE / IMG_UPLOAD_FAILED).
 * Returns null when no file was submitted — caller keeps the old image.
 */

const PRODUCT_IMG_MAX_BYTES = 2 * 1024 * 1024; // 2 MB
const PRODUCT_IMG_TYPES     = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

function saveProductImage(string $field = 'image'): string|null
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null; // nothing uploaded
    }

    $file = $_FILES[$field];

    if ($file['error'] !== UPLOAD_ERR_OK)          return 'IMG_UPLOAD_FAILED';
    if ($file['size'] > PRODUCT_IMG_MAX_BYTES)     return 'IMG_TOO_LARGE';

    // Verify the real mime type, never trust the client's value
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!isset(PRODUCT_IMG_TYPES[$mime]))          return 'IMG_TYPE';

    $dir = ROOT_PATH . '/uploads/products';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) return 'IMG_UPLOAD_FAILED';

    $name = uniqid('prd_', true) . '.' . PRODUCT_IMG_TYPES[$mime];
    $name = str_replace('.', '_', pathinfo($name, PATHINFO_FILENAME)) . '.' . PRODUCT_IMG_TYPES[$mime];

    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        return 'IMG_UPLOAD_FAILED';
    }

    return 'uploads/products/' . $name;
}

/** Delete a previously stored product image (ignores missing files). */
function deleteProductImage(?string $relPath): void
{
    if (!$relPath) return;
    // Only ever delete inside uploads/products — refuse anything else
    if (!str_starts_with($relPath, 'uploads/products/'))  return;
    if (str_contains($relPath, '..'))                     return;

    $abs = ROOT_PATH . '/' . $relPath;
    if (is_file($abs)) @unlink($abs);
}
