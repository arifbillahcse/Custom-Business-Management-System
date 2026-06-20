<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Stock.php';
require_once __DIR__ . '/../classes/Supplier.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Branch.php';
requireLogin();

$pageTitle   = 'Stock Management';
$_isStaff    = isStaff();
$staffBranch = getSessionBranchId();
$allStock    = Stock::getAllStock();
$history     = Stock::getStockInbound(null, $_isStaff ? $staffBranch : null);
$suppliers   = Supplier::getSuppliers();
$products    = Product::getProducts();
$branches    = Branch::getBranches();

$lowStock = array_filter($allStock, fn($r) => $r['min_stock'] > 0 && $r['current_stock'] <= $r['min_stock']);

// Group products by category for modal selects
$productsByType = [];
foreach ($products as $p) { $productsByType[$p['category_name']][] = $p; }

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content" id="mainContent">

    <!-- Page Header -->
    <div class="page-header">
        <h5><i class="bi bi-stack me-2 text-danger"></i>Stock Management</h5>
        <?php if (!$_isStaff): ?>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-primary btn-sm" id="btnAddInbound">
                <i class="bi bi-plus-lg me-1"></i>Product purchase
            </button>
            <button class="btn btn-danger btn-sm" id="btnAdjustStock">
                <i class="bi bi-sliders me-1"></i>Stock Adjustment
            </button>
            <?php if (!empty($branches)): ?>
            <button class="btn btn-info btn-sm text-white" id="btnTransferStock">
                <i class="bi bi-arrow-left-right me-1"></i>Branch Transfer
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Low stock banner -->
    <?php if ($lowStock): ?>
    <div class="alert alert-warning alert-dismissible fade show mb-3">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        <strong><?= count($lowStock) ?> products are low on stock!</strong>
        <?php foreach ($lowStock as $r): ?>
            <span class="badge bg-danger ms-1">
                <?= e($r['product_name']) ?> (<?= rtrim(rtrim($r['current_stock'],'0'),'.') ?> <?= e($r['unit']) ?>)
            </span>
        <?php endforeach; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3">
        <?php if (!$_isStaff): ?>
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#currentStockTab">
                <i class="bi bi-boxes me-1"></i>Current stock
                <span class="badge bg-secondary ms-1"><?= count($allStock) ?></span>
            </button>
        </li>
        <?php endif; ?>
        <?php if (!empty($branches)): ?>
        <li class="nav-item">
            <button class="nav-link <?= $_isStaff ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#branchStockTab" id="btnBranchStockTab">
                <i class="bi bi-shop me-1"></i>Branch Stock
            </button>
        </li>
        <?php endif; ?>
        <?php if (!$_isStaff): ?>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#inboundTab">
                <i class="bi bi-arrow-down-circle me-1"></i>Purchase History
                <span class="badge bg-secondary ms-1"><?= count($history) ?></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#adjustTab" id="btnAdjustTab">
                <i class="bi bi-sliders me-1"></i>Adjustment History
            </button>
        </li>
        <?php if (!empty($branches)): ?>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#transferTab" id="btnTransferTab">
                <i class="bi bi-arrow-left-right me-1"></i>Transfer History
            </button>
        </li>
        <?php endif; ?>
        <?php endif; ?>
    </ul>

    <div class="tab-content">

        <!-- ===== CURRENT STOCK TAB ===== -->
        <?php if (!$_isStaff): ?>
        <div class="tab-pane fade show active" id="currentStockTab">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Product name</th>
                                    <th>Type</th>
                                    <th>Size/Brand</th>
                                    <th class="text-end">Current stock</th>
                                    <th class="text-end">Minimum</th>
                                    <th class="text-end">Purchase price</th>
                                    <th class="text-end">Total value</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center"></th>
                                </tr>
                            </thead>
                            <tbody id="currentStockBody">
                            <?php if (empty($allStock)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">No products yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($allStock as $r):
                                    $low   = $r['min_stock'] > 0 && $r['current_stock'] <= $r['min_stock'];
                                    $qty   = rtrim(rtrim($r['current_stock'],'0'),'.');
                                    $total = (float)$r['current_stock'] * (float)$r['buy_price'];
                                ?>
                                <tr class="<?= $low ? 'table-danger' : '' ?>">
                                    <td class="fw-semibold"><?= e($r['product_name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= e($r['product_type']) ?></span></td>
                                    <td><?= e($r['size_brand'] ?? '—') ?></td>
                                    <td class="text-end <?= $low ? 'low-stock' : '' ?>"><?= $qty ?> <?= e($r['unit']) ?></td>
                                    <td class="text-end"><?= rtrim(rtrim($r['min_stock'],'0'),'.') ?> <?= e($r['unit']) ?></td>
                                    <td class="text-end"><?= money((float)$r['buy_price']) ?></td>
                                    <td class="text-end"><?= money($total) ?></td>
                                    <td class="text-center">
                                        <?= $low
                                            ? '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Low</span>'
                                            : '<span class="badge bg-success"><i class="bi bi-check me-1"></i>OK</span>' ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-danger"
                                                onclick="openAdjustFor(<?= $r['product_id'] ?>)"
                                                title="Stock Adjustment">
                                            <i class="bi bi-sliders"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ===== BRANCH STOCK TAB (ENHANCED) ===== -->
        <?php if (!empty($branches)): ?>
        <div class="tab-pane fade <?= $_isStaff ? 'show active' : '' ?>" id="branchStockTab">

            <?php if (!$_isStaff): ?>
            <!-- Sub-nav for admin: comparison vs individual -->
            <ul class="nav nav-pills mb-3" id="branchSubNav">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#branchComparePane" id="btnBranchCompare">
                        <i class="bi bi-grid me-1"></i>Branch comparison
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#branchSinglePane">
                        <i class="bi bi-shop me-1"></i>separate branch
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Comparison pane -->
                <div class="tab-pane fade show active" id="branchComparePane">
                    <!-- Summary cards -->
                    <div id="branchSummaryCards" class="row g-2 mb-3"></div>
                    <!-- Comparison matrix -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <div id="branchCompareLoading" class="text-center py-5 text-muted">
                                    <div class="spinner-border spinner-border-sm me-2"></div>Loading...
                                </div>
                                <table class="table table-hover align-middle mb-0 d-none" id="branchCompareTable">
                                    <thead id="branchCompareHead" class="table-dark"></thead>
                                    <tbody id="branchCompareBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Individual branch pane -->
                <div class="tab-pane fade" id="branchSinglePane">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="row g-2 align-items-center mb-3">
                                <div class="col-auto">
                                    <label class="form-label fw-semibold mb-0">Select a branch:</label>
                                </div>
                                <div class="col-sm-4">
                                    <select class="form-select" id="branchStockSelector">
                                        <option value="">— Choose a branch —</option>
                                        <?php foreach ($branches as $b): ?>
                                        <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div id="branchStockTableWrap" class="table-responsive" style="display:none">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Product name</th><th>Type</th><th>Size/Brand</th>
                                            <th class="text-end">Total collected</th>
                                            <th class="text-end">Adjustment</th>
                                            <th class="text-end">Transfer in</th>
                                            <th class="text-end">Transfer out</th>
                                            <th class="text-end">Total sales</th>
                                            <th class="text-end">Current stock</th>
                                            <th class="text-center">Status</th>
                                            <?php if (!$_isStaff): ?><th class="text-center">Action</th><?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody id="branchStockBody"></tbody>
                                </table>
                            </div>
                            <div id="branchStockEmpty" class="text-center text-muted py-5" style="display:none">
                                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>This branch has no stock.
                            </div>
                            <div id="branchStockPrompt" class="text-center text-muted py-5">
                                <i class="bi bi-shop fs-1 d-block mb-2 opacity-25"></i>Select a branch from above.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php else: /* staff view */ ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php $myBranch = array_filter($branches, fn($b) => $b['id'] == $staffBranch); $myBranch = reset($myBranch); ?>
                    <div class="mb-3">
                        <span class="badge bg-secondary fs-6"><i class="bi bi-shop me-1"></i><?= $myBranch ? e($myBranch['name']) : 'My branch' ?></span>
                    </div>
                    <div id="branchStockTableWrap" class="table-responsive" style="display:none">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Product name</th><th>Type</th><th>Size/Brand</th>
                                    <th class="text-end">Total collected</th>
                                    <th class="text-end">Total sales</th>
                                    <th class="text-end">Current stock</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="branchStockBody"></tbody>
                        </table>
                    </div>
                    <div id="branchStockEmpty" class="text-center text-muted py-5" style="display:none">
                        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>This branch has no stock.
                    </div>
                    <div id="branchStockPrompt" class="text-center text-muted py-5">
                        <i class="bi bi-shop fs-1 d-block mb-2 opacity-25"></i>Loading...
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ===== INBOUND HISTORY TAB ===== -->
        <?php if (!$_isStaff): ?>
        <div class="tab-pane fade" id="inboundTab">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th><th>Product</th><th>Branch</th><th>Supplier</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Purchase price</th>
                                    <th class="text-end">Total expense</th>
                                    <th>Note</th>
                                    <th class="text-center" style="width:100px">Action</th>
                                </tr>
                            </thead>
                            <tbody id="inboundBody">
                            <?php if (empty($history)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-4">No purchase records yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($history as $h): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($h['inbound_date'])) ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= e($h['product_name']) ?></div>
                                        <small class="text-muted"><?= e($h['product_type']) ?></small>
                                    </td>
                                    <td><?= !empty($h['branch_name']) ? '<span class="badge bg-secondary"><i class="bi bi-shop me-1"></i>'.e($h['branch_name']).'</span>' : '<span class="text-muted">—</span>' ?></td>
                                    <td><?= e($h['supplier_name'] ?? '—') ?></td>
                                    <td class="text-end"><?= rtrim(rtrim($h['quantity'],'0'),'.') ?> <?= e($h['unit']) ?></td>
                                    <td class="text-end"><?= money((float)$h['buy_price']) ?></td>
                                    <td class="text-end fw-semibold"><?= money((float)$h['total_cost']) ?></td>
                                    <td><small class="text-muted"><?= e($h['note'] ?? '') ?></small></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary btn-edit-inbound" data-id="<?= $h['id'] ?>"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-inbound" data-id="<?= $h['id'] ?>" data-name="<?= e($h['product_name']) ?>"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== ADJUSTMENT HISTORY TAB ===== -->
        <div class="tab-pane fade" id="adjustTab">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th><th>Product</th><th>Branch</th>
                                    <th class="text-end">Quantity</th>
                                    <th>Reason</th><th>Note</th><th>by</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="adjustBody">
                                <tr><td colspan="8" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== TRANSFER HISTORY TAB ===== -->
        <?php if (!empty($branches)): ?>
        <div class="tab-pane fade" id="transferTab">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th><th>Product</th>
                                    <th>Source branch</th>
                                    <th>Destination branch</th>
                                    <th class="text-end">Quantity</th>
                                    <th>Note</th><th>by</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="transferBody">
                                <tr><td colspan="8" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; /* !$_isStaff */ ?>

    </div><!-- /tab-content -->
</div><!-- /main-content -->

<?php if (!$_isStaff): ?>

<!-- ===== STOCK IN MODAL ===== -->
<div class="modal fade" id="inboundModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="inboundForm">
                <div class="modal-header">
                    <h6 class="modal-title" id="inboundModalTitle"><i class="bi bi-arrow-down-circle me-1 text-danger"></i>Product purchase (Stock In)</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="inboundId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Product <span class="text-danger">*</span></label>
                        <select name="product_id" id="inboundProduct" class="form-select" required>
                            <option value="">— Select a product —</option>
                            <?php foreach ($productsByType as $type => $list): ?>
                            <optgroup label="<?= e($type) ?>">
                                <?php foreach ($list as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= e($p['name']) ?> (<?= e($p['size_brand']) ?>)</option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="quantity" id="inboundQty" class="form-control" required placeholder="0.00">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Purchase price (৳) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="buy_price" id="inboundPrice" class="form-control" required placeholder="0.00">
                        </div>
                    </div>
                    <?php if (!empty($branches)): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" id="inboundBranch" class="form-select" required>
                            <option value="">— Select a branch —</option>
                            <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="row g-2">
                        <div class="col-7 mb-3">
                            <label class="form-label fw-semibold">Supplier</label>
                            <select name="supplier_id" id="inboundSupplier" class="form-select">
                                <option value="">— Optional —</option>
                                <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-5 mb-3">
                            <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="inbound_date" id="inboundDate" class="form-control" value="<?= today() ?>" required>
                        </div>
                    </div>
                    <div class="alert alert-info py-2 mb-3" id="totalPreview" style="display:none">
                        <i class="bi bi-calculator me-1"></i>Total expense: <strong id="totalPreviewAmt"></strong>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Note</label>
                        <input type="text" name="note" id="inboundNote" class="form-control" placeholder="Optional note">
                    </div>
                    <div class="alert alert-danger py-2 d-none" id="inboundError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelled</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveInbound"><i class="bi bi-check-lg me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== ADJUSTMENT MODAL ===== -->
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title" id="adjModalTitle"><i class="bi bi-sliders me-1"></i>Stock Adjustment</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Product <span class="text-danger">*</span></label>
                    <select id="adjProduct" class="form-select" required>
                        <option value="">— Select a product —</option>
                        <?php foreach ($productsByType as $type => $list): ?>
                        <optgroup label="<?= $type === 'rod' ? 'Rod' : 'Cement' ?>">
                            <?php foreach ($list as $p): ?>
                            <option value="<?= $p['id'] ?>" data-unit="<?= e($p['unit']) ?>"><?= e($p['name']) ?> (<?= e($p['size_brand']) ?>)</option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($branches)): ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Branch (optional)</label>
                    <select id="adjBranch" class="form-select">
                        <option value="">— Global (no branch) —</option>
                        <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                    <div class="d-flex gap-2">
                        <div class="form-check form-check-inline flex-fill">
                            <input class="form-check-input" type="radio" name="adjDir" id="adjAdd" value="add" checked>
                            <label class="form-check-label text-success fw-semibold" for="adjAdd"><i class="bi bi-plus-circle me-1"></i>Increase (+)</label>
                        </div>
                        <div class="form-check form-check-inline flex-fill">
                            <input class="form-check-input" type="radio" name="adjDir" id="adjSub" value="subtract">
                            <label class="form-check-label text-danger fw-semibold" for="adjSub"><i class="bi bi-dash-circle me-1"></i>Decrease (−)</label>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" id="adjQty" class="form-control" placeholder="0.00" required>
                    <div class="form-text" id="adjCurrentStock"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                    <select id="adjReason" class="form-select">
                        <option value="count_correction">Count adjustment</option>
                        <option value="damage">Damaged / Spoiled</option>
                        <option value="return">Return</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Note</label>
                    <input type="text" id="adjNote" class="form-control" placeholder="Optional note">
                </div>
                <div class="alert alert-danger py-2 d-none" id="adjError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelled</button>
                <button type="button" class="btn btn-danger" id="btnSaveAdj"><i class="bi bi-check-lg me-1"></i>Adjust</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== TRANSFER MODAL ===== -->
<?php if (!empty($branches)): ?>
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h6 class="modal-title" id="trfModalTitle"><i class="bi bi-arrow-left-right me-1"></i>Branch Transfer</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Product <span class="text-danger">*</span></label>
                    <select id="trfProduct" class="form-select" required>
                        <option value="">— Select a product —</option>
                        <?php foreach ($productsByType as $type => $list): ?>
                        <optgroup label="<?= $type === 'rod' ? 'Rod' : 'Cement' ?>">
                            <?php foreach ($list as $p): ?>
                            <option value="<?= $p['id'] ?>" data-unit="<?= e($p['unit']) ?>"><?= e($p['name']) ?> (<?= e($p['size_brand']) ?>)</option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Source branch <span class="text-danger">*</span></label>
                        <select id="trfFrom" class="form-select" required>
                            <option value="">— Choose —</option>
                            <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-info" id="trfFromStock"></div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Destination branch <span class="text-danger">*</span></label>
                        <select id="trfTo" class="form-select" required>
                            <option value="">— Choose —</option>
                            <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" id="trfQty" class="form-control" placeholder="0.00" required>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Note</label>
                    <input type="text" id="trfNote" class="form-control" placeholder="Optional note">
                </div>
                <div class="alert alert-danger py-2 d-none" id="trfError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelled</button>
                <button type="button" class="btn btn-info text-white" id="btnSaveTrf"><i class="bi bi-check-lg me-1"></i>Transfer</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endif; /* !$_isStaff */ ?>

<script>
const BASE_URL        = '<?= BASE_URL ?>';
const STAFF_BRANCH_ID = <?= $staffBranch ?? 'null' ?>;
const IS_STAFF_VIEW   = <?= $_isStaff ? 'true' : 'false' ?>;
const HAS_BRANCHES    = <?= !empty($branches) ? 'true' : 'false' ?>;
const CAN_WRITE       = <?= (!$_isStaff && User::isAdminOrManager()) ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/stock.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
