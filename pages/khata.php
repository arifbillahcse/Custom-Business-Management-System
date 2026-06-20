<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Customer.php';
requireLogin();
requireManagerOrAdmin();

$pageTitle    = 'Ledger';
$allCustomers = Customer::getCustomers();
$preSelected  = (int)($_GET['customer_id'] ?? 0);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content" id="mainContent">
<div class="container-fluid py-4">

  <div class="page-header mb-4">
    <h5><i class="bi bi-journal-text me-2 text-danger"></i>Ledger</h5>
  </div>

  <!-- Customer selector -->
  <div class="card shadow-sm mb-4">
    <div class="card-body py-3">
      <div class="row g-2 align-items-end">
        <div class="col-md-6 col-lg-4">
          <label class="form-label fw-semibold small text-muted mb-1">Select a customer</label>
          <select class="form-select" id="ledgerCustomer">
            <option value="">-- Select customer --</option>
            <?php foreach ($allCustomers as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id'] === $preSelected ? 'selected' : '' ?>>
              <?= e($c['name']) ?>
              <?php if ((float)$c['total_due'] > 0): ?>
                (Due: <?= money((float)$c['total_due']) ?>)
              <?php endif; ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary w-100" onclick="loadLedger()">
            <i class="bi bi-search me-1"></i>View
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Ledger content area -->
  <div id="ledgerContent">
    <div class="text-center py-5 text-muted">
      <i class="bi bi-journal-text fs-1 d-block mb-2 opacity-25"></i>
      Select a customer and view the ledger
    </div>
  </div>

</div>
</div>

<script>
const BASE_URL  = '<?= BASE_URL ?>';
const IS_ADMIN  = <?= User::isAdminOrManager() ? 'true' : 'false' ?>;
const PRE_SEL   = <?= $preSelected ?>;
const SHOP_NAME = '<?= e(Setting::get('shop_name', 'Shop ledger')) ?>';
</script>
<script src="<?= BASE_URL ?>/assets/js/khata.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
