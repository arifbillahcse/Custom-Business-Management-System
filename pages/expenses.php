<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Expense.php';
require_once __DIR__ . '/../classes/Branch.php';
requireLogin();
requireManagerOrAdmin();

$pageTitle  = 'Expense Tracking';
$categories = Expense::getCategories();
$branches   = Branch::getBranches();

$thisMonth  = Expense::getTotalExpenses(date('Y-m-01'), date('Y-m-d'));
$thisYear   = Expense::getTotalExpenses(date('Y-01-01'), date('Y-m-d'));
$today      = Expense::getTotalExpenses(date('Y-m-d'), date('Y-m-d'));

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content" id="mainContent">
<div class="container-fluid py-4">

  <div class="page-header">
    <h4 class="mb-0"><i class="bi bi-cash-stack me-2 text-danger"></i>Expense Tracking</h4>
    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
      <i class="bi bi-plus-lg me-1"></i>New expense
    </button>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="stat-icon bg-danger text-white"><i class="bi bi-calendar-day fs-4"></i></div>
          <div>
            <div class="stat-value text-danger"><?= money($today) ?></div>
            <div class="stat-label">Today's expenses</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="stat-icon bg-warning text-dark"><i class="bi bi-calendar-month fs-4"></i></div>
          <div>
            <div class="stat-value"><?= money($thisMonth) ?></div>
            <div class="stat-label">This month's expenses</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="stat-icon bg-secondary text-white"><i class="bi bi-calendar fs-4"></i></div>
          <div>
            <div class="stat-value"><?= money($thisYear) ?></div>
            <div class="stat-label">This year's expenses</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <ul class="nav nav-pills mb-4" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#expListTab" type="button">
        <i class="bi bi-list-ul me-1"></i>Expense list
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="pill" data-bs-target="#profitTab" type="button"
              id="profitTabBtn">
        <i class="bi bi-graph-up-arrow me-1"></i>Profit-loss
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="pill" data-bs-target="#catTab" type="button">
        <i class="bi bi-tags me-1"></i>Category
      </button>
    </li>
  </ul>

  <div class="tab-content">

    <!-- ===== EXPENSE LIST TAB ===== -->
    <div class="tab-pane fade show active" id="expListTab">
      <!-- Filter -->
      <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
          <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
              <label class="form-label small text-muted mb-1">From date</label>
              <input type="date" class="form-control form-control-sm" id="eFrom"
                     value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label small text-muted mb-1">To date</label>
              <input type="date" class="form-control form-control-sm" id="eTo"
                     value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label small text-muted mb-1">Category</label>
              <select class="form-select form-select-sm" id="eCat">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php if (!empty($branches)): ?>
            <div class="col-12 col-md-3">
              <label class="form-label small text-muted mb-1">Branch</label>
              <select class="form-select form-select-sm" id="eBranch">
                <option value="">All branches</option>
                <?php foreach ($branches as $b): ?>
                <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>
            <div class="col-12 col-md-2">
              <button class="btn btn-primary btn-sm w-100" onclick="loadExpenses()">
                <i class="bi bi-search me-1"></i>Search
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="table-responsive">
          <table class="table table-hover mb-0 align-middle">
            <thead class="table-dark">
              <tr>
                <th>Date</th>
                <th>Category</th>
                <?php if (!empty($branches)): ?><th>Branch</th><?php endif; ?>
                <th>Details</th>
                <th class="text-end">Quantity</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody id="expenseBody">
              <tr><td colspan="6" class="text-center py-5 text-muted">
                <span class="spinner-border spinner-border-sm me-2"></span>Loading...
              </td></tr>
            </tbody>
          </table>
        </div>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top" id="expPaginationBar" style="display:none!important">
          <small class="text-muted" id="expPageInfo"></small>
          <nav><ul class="pagination pagination-sm mb-0" id="expPagination"></ul></nav>
        </div>
      </div>
    </div><!-- /expListTab -->

    <!-- ===== PROFIT / LOSS TAB ===== -->
    <div class="tab-pane fade" id="profitTab">
      <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
          <div class="row g-2 align-items-end">
            <div class="col-md-3">
              <label class="form-label small text-muted mb-1">From date</label>
              <input type="date" class="form-control form-control-sm" id="pFrom"
                     value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label small text-muted mb-1">To date</label>
              <input type="date" class="form-control form-control-sm" id="pTo"
                     value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-2">
              <button class="btn btn-primary btn-sm w-100" onclick="loadProfitLoss()">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div id="plContent">
        <div class="text-center text-muted py-5">
          <i class="bi bi-graph-up-arrow fs-1 d-block mb-2 opacity-25"></i>
          Select a date above and search
        </div>
      </div>
    </div><!-- /profitTab -->

    <!-- ===== CATEGORY TAB ===== -->
    <div class="tab-pane fade" id="catTab">
      <div class="row g-3">
        <div class="col-md-5">
          <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">
              <i class="bi bi-plus-circle me-1"></i>New category
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="newCatName" placeholder="e.g. food expense">
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Icon</label>
                <select class="form-select" id="newCatIcon">
                  <option value="bi-receipt">🧾 Receipt</option>
                  <option value="bi-house-door">🏠 House/Rent</option>
                  <option value="bi-person-badge">👤 Salary</option>
                  <option value="bi-lightning-charge">⚡ Electricity</option>
                  <option value="bi-wifi">📡 Internet</option>
                  <option value="bi-truck">🚛 Transport</option>
                  <option value="bi-tools">🔧 Repair</option>
                  <option value="bi-bag">🛍️ Purchase</option>
                  <option value="bi-telephone">📞 Phone</option>
                  <option value="bi-water">💧 Water bill</option>
                  <option value="bi-three-dots">⋯ Misc</option>
                </select>
              </div>
              <button class="btn btn-primary w-100" onclick="addCategory()">
                <i class="bi bi-plus-lg me-1"></i>Add
              </button>
            </div>
          </div>
        </div>
        <div class="col-md-7">
          <div class="card shadow-sm">
            <div class="card-header fw-semibold">
              <i class="bi bi-tags me-1"></i>Existing categories
            </div>
            <div class="table-responsive">
              <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                  <tr><th>Category</th><th class="text-center">Action</th></tr>
                </thead>
                <tbody id="catBody">
                  <?php foreach ($categories as $c): ?>
                  <tr id="catrow<?= $c['id'] ?>">
                    <td><i class="bi <?= e($c['icon']) ?> me-2 text-secondary"></i><?= e($c['name']) ?></td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-outline-danger"
                              onclick="deleteCategory(<?= $c['id'] ?>, '<?= e(addslashes($c['name'])) ?>')">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div><!-- /catTab -->

  </div><!-- /tab-content -->
</div>
</div>

<!-- ===== ADD/EDIT EXPENSE MODAL ===== -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="expModalTitle">
          <i class="bi bi-cash-stack me-2"></i>New expense
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="expEditId">

        <div class="mb-3">
          <label class="form-label fw-semibold">Amount (৳) <span class="text-danger">*</span></label>
          <input type="number" class="form-control form-control-lg" id="expAmount"
                 min="0.01" step="0.01" placeholder="0.00">
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Category</label>
            <select class="form-select" id="expCategory">
              <option value="">— Select —</option>
              <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="expDate" value="<?= date('Y-m-d') ?>">
          </div>
        </div>

        <?php if (!empty($branches)): ?>
        <div class="mb-3">
          <label class="form-label fw-semibold">Branch</label>
          <select class="form-select" id="expBranch">
            <option value="">— All / General —</option>
            <?php foreach ($branches as $b): ?>
            <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <div class="mb-3">
          <label class="form-label fw-semibold text-muted">Details</label>
          <textarea class="form-control" id="expDesc" rows="2" maxlength="500"
                    placeholder="e.g. May office rent"></textarea>
        </div>

        <div id="expError" class="alert alert-danger d-none"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelled</button>
        <button type="button" class="btn btn-danger" id="btnSaveExpense" onclick="saveExpense()">
          <i class="bi bi-check-lg me-1"></i>Save
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const BASE_URL  = '<?= BASE_URL ?>';
const CAN_WRITE = <?= User::isAdminOrManager() ? 'true' : 'false' ?>;
const HAS_BRANCHES = <?= !empty($branches) ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/expenses.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
