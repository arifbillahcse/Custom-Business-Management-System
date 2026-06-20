<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Customer.php';
requireLogin();
requireManagerOrAdmin();

$pageTitle = 'Installment Tracking';
$customers = Customer::getCustomers();
$canWrite  = User::isAdminOrManager();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content" id="mainContent">
<div class="container-fluid py-4">

  <div class="page-header mb-4">
    <h5><i class="bi bi-calendar-check me-2 text-danger"></i>Installment Tracking</h5>
    <?php if ($canWrite): ?>
    <button class="btn btn-primary btn-sm" id="btnNewPlan">
      <i class="bi bi-plus-lg me-1"></i>New installment plan
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
            <button class="btn btn-sm btn-outline-success" data-status="completed">Completed</button>
            <button class="btn btn-sm btn-outline-secondary" data-status="cancelled">Cancelled</button>
          </div>
        </div>
        <div class="col">
          <input type="text" class="form-control form-control-sm" id="searchInput"
                 placeholder="Customer name...">
        </div>
      </div>
    </div>
  </div>

  <div id="planList">
    <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
  </div>
  <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-white rounded-bottom" id="instPaginationBar" style="display:none!important">
    <small class="text-muted" id="instPageInfo"></small>
    <nav><ul class="pagination pagination-sm mb-0" id="instPagination"></ul></nav>
  </div>

</div>
</div>

<!-- New Plan Modal -->
<div class="modal fade" id="planModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-calendar-plus me-2"></i>New installment plan</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="planForm" onsubmit="submitPlan(event)">
          <div class="mb-3">
            <label class="form-label fw-semibold">Customer name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="pName" maxlength="150"
                   list="cSuggestions" placeholder="Enter name" required>
            <datalist id="cSuggestions">
              <?php foreach ($customers as $c): ?>
              <option value="<?= e($c['name']) ?>">
              <?php endforeach; ?>
            </datalist>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Total amount (৳) <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="pTotal" min="1" step="0.01" required oninput="calcInstall()">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Advance / Down payment (৳)</label>
              <input type="number" class="form-control" id="pDown" value="0" min="0" step="0.01" oninput="calcInstall()">
            </div>
          </div>
          <div class="row g-3 mt-0">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Number of installments <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="pCount" min="1" max="120" required oninput="calcInstall()">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Start date</label>
              <input type="date" class="form-control" id="pStart" value="<?= date('Y-m-d') ?>">
            </div>
          </div>

          <!-- Preview -->
          <div class="alert alert-info mt-3 py-2 d-none" id="installPreview"></div>

          <div class="mt-3">
            <label class="form-label fw-semibold">Note</label>
            <textarea class="form-control" id="pNote" rows="2" maxlength="500"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelled</button>
        <button type="submit" form="planForm" class="btn btn-primary" id="planSaveBtn">
          <i class="bi bi-check-circle me-1"></i>Create
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Plan Detail Modal -->
<div class="modal fade" id="planDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-calendar-check me-2"></i>Installment details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="planDetailBody">
        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
      </div>
    </div>
  </div>
</div>

<!-- Pay Installment Modal -->
<div class="modal fade" id="payModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-cash-coin me-2"></i>Installment payment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="payForm" onsubmit="submitPay(event)">
          <input type="hidden" id="payInstId">
          <p class="mb-3">Installment No. <strong id="payInstNo"></strong> — Principal amount: <strong id="payInstAmt"></strong></p>
          <div class="mb-3">
            <label class="form-label fw-semibold">Payment amount (৳)</label>
            <input type="number" class="form-control" id="payAmount" min="0.01" step="0.01" required>
          </div>
          <div>
            <label class="form-label fw-semibold">Note</label>
            <input type="text" class="form-control" id="payNote" maxlength="500">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelled</button>
        <button type="submit" form="payForm" class="btn btn-success" id="paySaveBtn">
          <i class="bi bi-check-circle me-1"></i>Confirm payment
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const BASE_URL  = '<?= BASE_URL ?>';
const CAN_WRITE = <?= $canWrite ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/installments.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
