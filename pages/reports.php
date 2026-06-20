<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/User.php';
requireLogin();
requireManagerOrAdmin();

$pageTitle = 'Reports & Analytics';

$defaultFrom = date('Y-m-01');
$defaultTo   = today();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content" id="mainContent">
<div class="container-fluid py-4">

  <div class="page-header">
    <h4 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i>Reports & Analytics</h4>
    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
      <i class="bi bi-printer me-1"></i>Print
    </button>
  </div>

  <!-- Date Range Filter -->
  <div class="card shadow-sm mb-4">
    <div class="card-body py-3">
      <div class="row g-2 align-items-end">
        <div class="col-6 col-md-3">
          <label class="form-label small text-muted mb-1">From date</label>
          <input type="date" class="form-control form-control-sm" id="rFrom"
                 value="<?= $defaultFrom ?>">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small text-muted mb-1">To date</label>
          <input type="date" class="form-control form-control-sm" id="rTo"
                 value="<?= $defaultTo ?>">
        </div>
        <div class="col-12 col-md-2">
          <button class="btn btn-primary btn-sm w-100" onclick="loadReport()">
            <i class="bi bi-search me-1"></i>View report
          </button>
        </div>
        <div class="col-12 col-md-4">
          <div class="d-flex flex-wrap gap-1">
            <button class="btn btn-sm btn-outline-secondary flex-fill" onclick="setRange('today')">Today</button>
            <button class="btn btn-sm btn-outline-secondary flex-fill" onclick="setRange('week')">This week</button>
            <button class="btn btn-sm btn-outline-secondary flex-fill" onclick="setRange('month')">This month</button>
            <button class="btn btn-sm btn-outline-secondary flex-fill" onclick="setRange('year')">This year</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="reportLoading" class="text-center py-5 d-none">
    <div class="spinner-border text-primary"></div>
    <p class="text-muted mt-2">Generating report...</p>
  </div>

  <div id="reportContent">

    <!-- Summary cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card stat-card p-3 h-100">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted small mb-1">Total sales</p>
              <h5 class="fw-bold mb-0" id="sumTotalSales">—</h5>
              <small class="text-muted" id="sumSaleCount">—</small>
            </div>
            <div class="stat-icon bg-danger"><i class="bi bi-cart-check"></i></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card stat-card p-3 h-100">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted small mb-1">Collected payments</p>
              <h5 class="fw-bold mb-0 text-success" id="sumPayments">—</h5>
              <small class="text-muted">In this period</small>
            </div>
            <div class="stat-icon bg-success"><i class="bi bi-cash-stack"></i></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card stat-card p-3 h-100">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted small mb-1">New due</p>
              <h5 class="fw-bold mb-0 text-danger" id="sumDue">—</h5>
              <small class="text-muted">in sales for this period</small>
            </div>
            <div class="stat-icon bg-warning"><i class="bi bi-wallet2"></i></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card stat-card p-3 h-100">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted small mb-1">Estimated profit</p>
              <h5 class="fw-bold mb-0 text-primary" id="sumProfit">—</h5>
              <small class="text-muted">Sales − Purchase price</small>
            </div>
            <div class="stat-icon bg-primary"><i class="bi bi-graph-up-arrow"></i></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts row -->
    <div class="row g-3 mb-4">
      <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white fw-semibold">
            <i class="bi bi-graph-up me-1 text-danger"></i>Daily sales
          </div>
          <div class="card-body">
            <canvas id="dailySalesChart" height="100"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Top products + dues -->
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white fw-semibold">
            <i class="bi bi-trophy me-1 text-warning"></i>Top-selling products
          </div>
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Product</th>
                  <th class="text-end">Quantity</th>
                  <th class="text-end">Income</th>
                </tr>
              </thead>
              <tbody id="topProductsBody">
                <tr><td colspan="3" class="text-center text-muted py-3">—</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white fw-semibold d-flex justify-content-between">
            <span><i class="bi bi-exclamation-circle me-1 text-danger"></i>Customers with dues</span>
            <span class="badge bg-danger align-self-center" id="duesTotalBadge">—</span>
          </div>
          <div class="table-responsive" style="max-height:300px;overflow-y:auto">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Customer</th>
                  <th class="text-end">Due</th>
                </tr>
              </thead>
              <tbody id="duesBody">
                <tr><td colspan="2" class="text-center text-muted py-3">—</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Stock valuation -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white fw-semibold d-flex justify-content-between flex-wrap">
        <span><i class="bi bi-boxes me-1 text-info"></i>Current stock valuation</span>
        <span class="small">
          Purchase price: <span class="fw-bold" id="stockCostTotal">—</span> |
          Sell price: <span class="fw-bold text-success" id="stockValueTotal">—</span>
        </span>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Product</th>
              <th>Type</th>
              <th class="text-end">Stock</th>
              <th class="text-end">Purchase price</th>
              <th class="text-end">Stock value (purchase)</th>
              <th class="text-end">Stock value (sell)</th>
            </tr>
          </thead>
          <tbody id="stockBody">
            <tr><td colspan="6" class="text-center text-muted py-3">—</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Purchase / payment small summary -->
    <div class="row g-3">
      <div class="col-md-6">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="bi bi-cart-plus me-1 text-secondary"></i>Purchase summary</h6>
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Number of purchases</span>
              <span class="fw-semibold" id="purchaseCount">—</span>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted">Total purchase cost</span>
              <span class="fw-semibold" id="purchaseCost">—</span>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="bi bi-cash-coin me-1 text-success"></i>Profit analysis</h6>
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Total income (sales)</span>
              <span class="fw-semibold" id="profitRevenue">—</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Product purchase price</span>
              <span class="fw-semibold" id="profitCost">—</span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between">
              <span class="fw-semibold">Estimated profit</span>
              <span class="fw-bold text-primary" id="profitNet">—</span>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /reportContent -->
</div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>/assets/js/reports.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
