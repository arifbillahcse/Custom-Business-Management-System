<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Customer.php';
requireLogin();
requireManagerOrAdmin();

$pageTitle  = 'Quotations';
$products   = Product::getProducts();
$customers  = Customer::getCustomers();
$canWrite   = User::isAdminOrManager();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content" id="mainContent">
<div class="container-fluid py-4">

  <div class="page-header mb-4">
    <h5><i class="bi bi-file-earmark-text me-2 text-danger"></i>Quotation / Estimate</h5>
    <?php if ($canWrite): ?>
    <button class="btn btn-primary btn-sm" id="btnNewQuote">
      <i class="bi bi-plus-lg me-1"></i>New quotation
    </button>
    <?php endif; ?>
  </div>

  <!-- Filter -->
  <div class="card shadow-sm mb-3">
    <div class="card-body py-2">
      <div class="row g-2 align-items-center">
        <div class="col-12 col-sm-auto">
          <div class="d-flex flex-wrap gap-1" id="statusFilter">
            <button class="btn btn-sm btn-danger active" data-status="">All</button>
            <button class="btn btn-sm btn-outline-primary" data-status="active">Active</button>
            <button class="btn btn-sm btn-outline-success" data-status="converted">Converted</button>
            <button class="btn btn-sm btn-outline-secondary" data-status="cancelled">Cancelled</button>
          </div>
        </div>
        <div class="col">
          <input type="text" class="form-control form-control-sm" id="searchInput"
                 placeholder="Customer name or quotation number...">
        </div>
      </div>
    </div>
  </div>

  <div id="quoteList">
    <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
  </div>
</div>
</div>

<!-- New Quotation Modal -->
<div class="modal fade" id="quoteModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-file-earmark-plus me-2"></i>New quotation</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="quoteForm" onsubmit="submitQuote(event)">
          <div class="row g-3 mb-3">
            <div class="col-md-5">
              <label class="form-label fw-semibold">Customer name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="qCustomer" maxlength="150"
                     placeholder="Enter name" required list="customerSuggestions">
              <datalist id="customerSuggestions">
                <?php foreach ($customers as $c): ?>
                <option value="<?= e($c['name']) ?>">
                <?php endforeach; ?>
              </datalist>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Date</label>
              <input type="date" class="form-control" id="qDate" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label fw-semibold">Term (days)</label>
              <input type="number" class="form-control" id="qValidDays" value="7" min="1" max="365">
            </div>
            <div class="col-md-2">
              <label class="form-label fw-semibold">Discount (৳)</label>
              <input type="number" class="form-control" id="qDiscount" value="0" min="0" step="0.01" oninput="calcTotal()">
            </div>
          </div>

          <!-- Items -->
          <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm" id="quoteItemsTable">
              <thead class="table-dark">
                <tr>
                  <th style="min-width:200px">Product</th>
                  <th style="width:100px">Quantity</th>
                  <th style="width:130px">Unit price (৳)</th>
                  <th style="width:130px">Total (৳)</th>
                  <th style="width:50px"></th>
                </tr>
              </thead>
              <tbody id="quoteItemsBody"></tbody>
              <tfoot>
                <tr>
                  <td colspan="5">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addQuoteRow()">
                      <i class="bi bi-plus-lg me-1"></i>Add product
                    </button>
                  </td>
                </tr>
                <tr class="table-light fw-bold">
                  <td colspan="3" class="text-end">Subtotal:</td>
                  <td id="qSubtotal">0.00 ৳</td><td></td>
                </tr>
                <tr class="table-light fw-bold">
                  <td colspan="3" class="text-end">Total:</td>
                  <td id="qTotal" class="text-success">0.00 ৳</td><td></td>
                </tr>
              </tfoot>
            </table>
          </div>

          <div>
            <label class="form-label fw-semibold">Note</label>
            <textarea class="form-control" id="qNote" rows="2" maxlength="500"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelled</button>
        <button type="submit" form="quoteForm" class="btn btn-primary" id="quoteSaveBtn">
          <i class="bi bi-check-circle me-1"></i>Create quotation
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Quotation Modal -->
<div class="modal fade" id="editQuoteModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit quotation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="editQuoteForm" onsubmit="submitEditQuote(event)">
          <input type="hidden" id="eqId">
          <div class="row g-3 mb-3">
            <div class="col-md-5">
              <label class="form-label fw-semibold">Customer name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="eqCustomer" maxlength="150"
                     placeholder="Enter name" required list="customerSuggestions">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Date</label>
              <input type="date" class="form-control" id="eqDate">
            </div>
            <div class="col-md-2">
              <label class="form-label fw-semibold">Term (days)</label>
              <input type="number" class="form-control" id="eqValidDays" min="1" max="365">
            </div>
            <div class="col-md-2">
              <label class="form-label fw-semibold">Discount (৳)</label>
              <input type="number" class="form-control" id="eqDiscount" min="0" step="0.01" oninput="calcEditTotal()">
            </div>
          </div>
          <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm">
              <thead class="table-dark">
                <tr>
                  <th style="min-width:200px">Product</th>
                  <th style="width:100px">Quantity</th>
                  <th style="width:130px">Unit price (৳)</th>
                  <th style="width:130px">Total (৳)</th>
                  <th style="width:50px"></th>
                </tr>
              </thead>
              <tbody id="editQuoteItemsBody"></tbody>
              <tfoot>
                <tr>
                  <td colspan="5">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addEditQuoteRow()">
                      <i class="bi bi-plus-lg me-1"></i>Add product
                    </button>
                  </td>
                </tr>
                <tr class="table-light fw-bold">
                  <td colspan="3" class="text-end">Subtotal:</td>
                  <td id="eqSubtotal">0.00 ৳</td><td></td>
                </tr>
                <tr class="table-light fw-bold">
                  <td colspan="3" class="text-end">Total:</td>
                  <td id="eqTotal" class="text-success">0.00 ৳</td><td></td>
                </tr>
              </tfoot>
            </table>
          </div>
          <div>
            <label class="form-label fw-semibold">Note</label>
            <textarea class="form-control" id="eqNote" rows="2" maxlength="500"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelled</button>
        <button type="submit" form="editQuoteForm" class="btn btn-warning" id="editQuoteSaveBtn">
          <i class="bi bi-check-circle me-1"></i>Update
        </button>
      </div>
    </div>
  </div>
</div>

<!-- View Quotation Modal -->
<div class="modal fade" id="viewQuoteModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-file-earmark-text me-2"></i>Quotation details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="viewQuoteBody">
        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
      </div>
      <div class="modal-footer" id="viewQuoteFooter"></div>
    </div>
  </div>
</div>

<script>
const BASE_URL  = '<?= BASE_URL ?>';
const PRODUCTS  = <?= json_encode(array_map(fn($p) => [
    'id' => $p['id'], 'name' => $p['name'],
    'sell_price' => $p['sell_price'], 'unit' => $p['unit']
], $products)) ?>;
const CAN_WRITE = <?= $canWrite ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/quotations.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
