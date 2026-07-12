<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Category.php';
requireManagerOrAdmin();

$pageTitle  = 'Product Management';
$categories = Category::getAll();

// Central product list writes are owner/admin only (managers can view)
$canWrite = User::isAdmin();

// Sub-categories grouped by category (for the dependent dropdown)
$subCategories = Category::getSubCategories();
$subcatsByCat  = [];
foreach ($subCategories as $sc) {
    $subcatsByCat[(int)$sc['category_id']][] = ['id' => (int)$sc['id'], 'name' => $sc['name']];
}

// Build products grouped by category
$productsByCategory = [];
foreach ($categories as $cat) {
    $productsByCategory[$cat['id']] = Product::getProducts((int)$cat['id']);
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content" id="mainContent">

    <!-- Page Header -->
    <div class="page-header">
        <h5><i class="bi bi-box-seam me-2 text-danger"></i>Product Management</h5>
        <?php if ($canWrite): ?>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" id="btnManageCategories"
                    data-bs-toggle="modal" data-bs-target="#categoryModal">
                <i class="bi bi-tags me-1"></i> Category
            </button>
            <button class="btn btn-primary btn-sm" id="btnAddProduct">
                <i class="bi bi-plus-lg me-1"></i> New product
            </button>
        </div>
        <?php endif; ?>
    </div>

    <?php if (empty($categories)): ?>
    <div class="alert alert-info">
        No categories. Use the above <strong>Category</strong> First add a category from the button.
    </div>
    <?php else: ?>

    <!-- Category Tabs -->
    <ul class="nav nav-tabs mb-3" id="productTabs">
        <?php foreach ($categories as $i => $cat): ?>
        <li class="nav-item">
            <button class="nav-link <?= $i === 0 ? 'active' : '' ?>"
                    data-bs-toggle="tab"
                    data-bs-target="#catTab<?= $cat['id'] ?>">
                <i class="bi bi-tag me-1"></i>
                <?= e($cat['name']) ?>
                <span class="badge bg-secondary ms-1"><?= count($productsByCategory[$cat['id']]) ?></span>
            </button>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="tab-content">
        <?php foreach ($categories as $i => $cat): ?>
        <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="catTab<?= $cat['id'] ?>">
            <?php renderProductTable($productsByCategory[$cat['id']], $canWrite); ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>

<?php
function renderProductTable(array $items, bool $canWrite): void { ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:56px">Image</th>
                            <th>Product name</th>
                            <th>Code</th>
                            <th>Size / Brand</th>
                            <th>Unit</th>
                            <th class="text-end">Purchase price</th>
                            <th class="text-end">Sell price</th>
                            <th class="text-end">Wholesale price</th>
                            <th class="text-end">Minimum stock</th>
                            <th class="text-center" style="width:150px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="10" class="text-center text-muted py-4">
                            No products. "New product" click the button.
                        </td></tr>
                    <?php else: foreach ($items as $p): ?>
                        <tr>
                            <td>
                                <?php if (!empty($p['image_path'])): ?>
                                <img src="<?= BASE_URL . '/' . e($p['image_path']) ?>"
                                     alt="" class="rounded border"
                                     style="width:40px;height:40px;object-fit:cover">
                                <?php else: ?>
                                <span class="d-inline-flex align-items-center justify-content-center rounded border bg-light text-muted"
                                      style="width:40px;height:40px"><i class="bi bi-image"></i></span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold">
                                <?= e($p['name']) ?>
                                <?php if (!empty($p['sub_category_name'])): ?>
                                <br><small class="text-muted"><i class="bi bi-tag"></i> <?= e($p['sub_category_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><code class="small"><?= e($p['product_code'] ?? '—') ?></code></td>
                            <td><span class="badge bg-light text-dark border"><?= e($p['size_brand'] ?: '—') ?></span></td>
                            <td><?= e($p['unit']) ?></td>
                            <td class="text-end"><?= money((float)$p['buy_price']) ?></td>
                            <td class="text-end"><?= money((float)$p['sell_price']) ?></td>
                            <td class="text-end"><?= (float)($p['wholesale_price'] ?? 0) > 0 ? money((float)$p['wholesale_price']) : '—' ?></td>
                            <td class="text-end"><?= rtrim(rtrim($p['min_stock'], '0'), '.') ?> <?= e($p['unit']) ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-secondary btn-qr"
                                        data-code="<?= e($p['product_code'] ?? '') ?>"
                                        data-name="<?= e($p['name']) ?>" title="QR code">
                                    <i class="bi bi-qr-code"></i>
                                </button>
                                <?php if ($canWrite): ?>
                                <button class="btn btn-sm btn-outline-primary btn-edit"
                                        data-id="<?= $p['id'] ?>" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-delete"
                                        data-id="<?= $p['id'] ?>" data-name="<?= e($p['name']) ?>" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php } ?>

<!-- ============ PRODUCT MODAL ============ -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="productForm">
                <div class="modal-header">
                    <h6 class="modal-title" id="modalTitle">
                        <i class="bi bi-box-seam me-1 text-danger"></i> New product
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="productId">

                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="productCategory" class="form-select" required>
                                <option value="">— Select a category —</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Sub-category</label>
                            <select name="sub_category_id" id="productSubCategory" class="form-select">
                                <option value="">— None —</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Product name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="productName" class="form-control"
                               placeholder="e.g. Steel Rod 12mm / Lafarge Cement" required>
                    </div>

                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Product code</label>
                            <input type="text" name="product_code" id="productCode" class="form-control"
                                   placeholder="Auto if empty" maxlength="50">
                            <small class="text-muted">Used in the QR code. Left empty = auto.</small>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Product image</label>
                            <input type="file" name="image" id="productImage" class="form-control"
                                   accept="image/jpeg,image/png,image/webp">
                            <small class="text-muted" id="currentImageNote"></small>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-7 mb-3">
                            <label class="form-label fw-semibold">Size / Brand</label>
                            <input type="text" name="size_brand" id="sizeBrand" class="form-control"
                                   placeholder="e.g. 12mm / LAFARGE / 60×60cm">
                        </div>
                        <div class="col-5 mb-3">
                            <label class="form-label fw-semibold">Unit <span class="text-danger">*</span></label>
                            <select name="unit" id="productUnit" class="form-select" required>
                                <option value="ton">Ton (ton)</option>
                                <option value="bag">Bag (bag)</option>
                                <option value="pcs">Pcs (pcs)</option>
                                <option value="sqft">Sq ft (sqft)</option>
                                <option value="cft">Cu ft (cft)</option>
                                <option value="kg">Kg (kg)</option>
                                <option value="liter">Litre (liter)</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-4 mb-3">
                            <label class="form-label fw-semibold">Purchase price (৳) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="buy_price"
                                   id="buyPrice" class="form-control" required>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label fw-semibold">Sell price (৳) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="sell_price"
                                   id="sellPrice" class="form-control" required>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label fw-semibold">Wholesale (৳)</label>
                            <input type="number" step="0.01" min="0" name="wholesale_price"
                                   id="wholesalePrice" class="form-control" value="0">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Minimum stock (Alert level)</label>
                        <input type="number" step="0.01" min="0" name="min_stock"
                               id="minStock" class="form-control" value="0">
                        <small class="text-muted">A warning will show when stock falls below this amount.</small>
                    </div>

                    <div class="alert alert-danger py-2 d-none" id="formError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelled</button>
                    <button type="submit" class="btn btn-primary" id="btnSave">
                        <i class="bi bi-check-lg me-1"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============ QR CODE MODAL ============ -->
<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-qr-code me-1 text-danger"></i> Product QR code</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qrContainer" class="d-inline-block p-2 bg-white border rounded"></div>
                <div class="fw-semibold mt-2" id="qrProductName"></div>
                <code id="qrProductCode"></code>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnPrintQr">
                    <i class="bi bi-printer me-1"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<?php if ($canWrite): ?>
<!-- ============ CATEGORY MANAGEMENT MODAL ============ -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">
                    <i class="bi bi-tags me-1 text-danger"></i> Category Management
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Existing categories list -->
                <ul class="list-group mb-3" id="categoryList">
                    <?php foreach ($categories as $cat): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span><?= e($cat['name']) ?></span>
                        <button class="btn btn-sm btn-outline-danger btn-del-cat"
                                data-id="<?= $cat['id'] ?>" data-name="<?= e($cat['name']) ?>">
                            <i class="bi bi-trash"></i>
                        </button>
                    </li>
                    <?php endforeach; ?>
                    <?php if (empty($categories)): ?>
                    <li class="list-group-item text-muted text-center" id="noCatMsg">No categories</li>
                    <?php endif; ?>
                </ul>

                <!-- Add new category -->
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="newCategoryName"
                           placeholder="New category name" maxlength="100">
                    <button class="btn btn-primary" id="btnAddCategory">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                <div class="alert alert-danger py-2 d-none" id="catError"></div>

                <hr>

                <!-- Sub-category management -->
                <h6 class="fw-semibold mb-2"><i class="bi bi-diagram-3 me-1"></i>Sub-categories</h6>
                <select class="form-select form-select-sm mb-2" id="subcatParent" data-no-search="1">
                    <option value="">— Select category —</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <ul class="list-group mb-2" id="subcatList">
                    <li class="list-group-item text-muted text-center small">Select a category above</li>
                </ul>
                <div class="input-group">
                    <input type="text" class="form-control" id="newSubcatName"
                           placeholder="New sub-category name" maxlength="100" disabled>
                    <button class="btn btn-primary" id="btnAddSubcat" disabled>
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                <div class="alert alert-danger py-2 mt-2 d-none" id="subcatError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============ DELETE CATEGORY CONFIRM MODAL ============ -->
<div class="modal fade" id="delCatModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-1"></i> Delete category
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2 small">
                    You <strong class="text-danger" id="delCatName"></strong> the category
                    you want to delete. It will be permanently removed.
                </p>
                <p class="mb-2 small text-muted">
                    the category name below to confirm <strong>exactly</strong> Write:
                </p>
                <input type="text" class="form-control" id="delCatConfirmInput"
                       placeholder="Enter the category name" autocomplete="off">
                <input type="hidden" id="delCatId">
                <div class="alert alert-danger py-2 mt-2 d-none" id="delCatError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelled</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnConfirmDelCat" disabled>
                    <i class="bi bi-trash me-1"></i> Confirm delete
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script>
const BASE       = '<?= BASE_URL ?>';
const CATEGORIES = <?= json_encode(array_values($categories)) ?>;
const SUBCATS    = <?= json_encode($subcatsByCat ?: new stdClass()) ?>;
const CAN_WRITE  = <?= $canWrite ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/products.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
