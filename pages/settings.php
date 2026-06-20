<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/User.php';
requireLogin();
requireAdmin();

$pageTitle = 'Settings';
$s = Setting::getAll();

// ── System stats ──────────────────────────────────────────────────────────────
$dbVersion    = Database::fetchOne('SELECT VERSION() AS v')['v'] ?? '—';
$totalUsers   = Database::fetchOne('SELECT COUNT(*) AS c FROM users WHERE is_active = 1')['c'] ?? 0;
$totalProd    = Database::fetchOne('SELECT COUNT(*) AS c FROM products WHERE is_active = 1')['c'] ?? 0;
$totalCust    = Database::fetchOne('SELECT COUNT(*) AS c FROM customers WHERE is_active = 1')['c'] ?? 0;
$totalSales   = Database::fetchOne('SELECT COUNT(*) AS c FROM sales WHERE status = "completed"')['c'] ?? 0;
$totalCats    = Database::fetchOne('SELECT COUNT(*) AS c FROM product_categories')['c'] ?? 0;
$totalSupp    = Database::fetchOne('SELECT COUNT(*) AS c FROM suppliers WHERE is_active = 1')['c'] ?? 0;
$totalBranch  = Database::fetchOne('SELECT COUNT(*) AS c FROM branches WHERE is_active = 1')['c'] ?? 0;
$totalInbound = Database::fetchOne('SELECT COUNT(*) AS c FROM stock_inbound')['c'] ?? 0;
$totalPay     = Database::fetchOne('SELECT COUNT(*) AS c FROM payments')['c'] ?? 0;

$salesAmount  = (float)(Database::fetchOne('SELECT COALESCE(SUM(total_amount),0) AS t FROM sales WHERE status="completed"')['t'] ?? 0);
$paidAmount   = (float)(Database::fetchOne('SELECT COALESCE(SUM(paid_amount),0) AS t FROM sales WHERE status="completed"')['t'] ?? 0);
$dueAmount    = (float)(Database::fetchOne('SELECT COALESCE(SUM(due_amount),0) AS t FROM sales WHERE status="completed"')['t'] ?? 0);

try {
    $stockValue = (float)(Database::fetchOne('SELECT COALESCE(SUM(current_stock * buy_price),0) AS t FROM vw_current_stock')['t'] ?? 0);
} catch (\Throwable $e) { $stockValue = 0; }

try {
    $totalExpense = (float)(Database::fetchOne('SELECT COALESCE(SUM(amount),0) AS t FROM expenses')['t'] ?? 0);
    $totalExpCat  = Database::fetchOne('SELECT COUNT(*) AS c FROM expense_categories')['c'] ?? 0;
} catch (\Throwable $e) { $totalExpense = 0; $totalExpCat = 0; }

try {
    $totalQuote = Database::fetchOne('SELECT COUNT(*) AS c FROM quotations')['c'] ?? 0;
} catch (\Throwable $e) { $totalQuote = 0; }

try {
    $totalAdj = Database::fetchOne('SELECT COUNT(*) AS c FROM stock_adjustments')['c'] ?? 0;
} catch (\Throwable $e) { $totalAdj = 0; }

try {
    $totalTrf = Database::fetchOne('SELECT COUNT(*) AS c FROM stock_transfers')['c'] ?? 0;
} catch (\Throwable $e) { $totalTrf = 0; }

$dbSize = Database::fetchOne("SELECT COALESCE(SUM(data_length + index_length),0) AS s FROM information_schema.tables WHERE table_schema = DATABASE()")['s'] ?? 0;

$diskFree     = function_exists('disk_free_space')  ? disk_free_space('/')  : null;
$diskTotal    = function_exists('disk_total_space') ? disk_total_space('/') : null;
$memLimit     = ini_get('memory_limit');
$uploadMax    = ini_get('upload_max_filesize');
$maxExecTime  = ini_get('max_execution_time');
$serverSW     = $_SERVER['SERVER_SOFTWARE'] ?? '—';
$phpExt       = implode(', ', array_intersect(['pdo_mysql','mbstring','json','curl','openssl'], get_loaded_extensions()));
$serverIP     = $_SERVER['SERVER_ADDR'] ?? '—';
$httpsEnabled = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'Yes' : 'No';

function fmtBytes(float $bytes): string {
    if ($bytes >= 1_073_741_824) return round($bytes / 1_073_741_824, 2) . ' GB';
    if ($bytes >= 1_048_576)     return round($bytes / 1_048_576, 2)     . ' MB';
    return round($bytes / 1024, 2) . ' KB';
}

function money2(float $v): string {
    return '৳ ' . number_format($v, 2, '.', ',');
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content" id="mainContent">
<div class="container-fluid py-4">

  <div class="page-header">
    <h4 class="mb-0"><i class="bi bi-gear-fill me-2"></i>Settings & system information</h4>
    <span class="badge bg-secondary fs-6">v<?= APP_VERSION ?></span>
  </div>

  <!-- Tabs -->
  <ul class="nav nav-pills mb-4" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#shopTab" type="button">
        <i class="bi bi-shop me-1"></i>Shop information
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="pill" data-bs-target="#sysTab" type="button">
        <i class="bi bi-cpu me-1"></i>System information
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="pill" data-bs-target="#statsTab" type="button">
        <i class="bi bi-bar-chart me-1"></i>Statistics
      </button>
    </li>
  </ul>

  <div class="tab-content">

    <!-- ═══════════════════════════════════ SHOP TAB ═══════════════════════ -->
    <div class="tab-pane fade show active" id="shopTab">
      <div class="row g-4">

        <!-- Shop form -->
        <div class="col-lg-7">
          <div class="card shadow-sm border-0">
            <div class="card-header d-flex align-items-center gap-2 bg-primary text-white py-3">
              <i class="bi bi-shop-window fs-5"></i>
              <span class="fw-semibold">Edit shop information</span>
            </div>
            <div class="card-body p-4">
              <form id="settingsForm" onsubmit="saveSettings(event)">

                <div class="mb-4">
                  <label class="form-label fw-semibold text-muted small text-uppercase" style="letter-spacing:.5px">Shop name</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shop"></i></span>
                    <input type="text" class="form-control form-control-lg" name="shop_name"
                           value="<?= e($s['shop_name'] ?? '') ?>" maxlength="150"
                           placeholder="e.g. Niharika Enterprise">
                  </div>
                </div>

                <div class="mb-4">
                  <label class="form-label fw-semibold text-muted small text-uppercase" style="letter-spacing:.5px">Address</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                    <textarea class="form-control" name="shop_address" rows="2"
                              maxlength="500" placeholder="Full address"><?= e($s['shop_address'] ?? '') ?></textarea>
                  </div>
                </div>

                <div class="row g-3 mb-4">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-muted small text-uppercase" style="letter-spacing:.5px">Phone number</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                      <input type="text" class="form-control" name="shop_phone"
                             value="<?= e($s['shop_phone'] ?? '') ?>" maxlength="50"
                             placeholder="01XXXXXXXXX">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-muted small text-uppercase" style="letter-spacing:.5px">Email</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                      <input type="email" class="form-control" name="shop_email"
                             value="<?= e($s['shop_email'] ?? '') ?>" maxlength="100"
                             placeholder="shop@example.com">
                    </div>
                  </div>
                </div>

                <div class="row g-3 mb-4">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-muted small text-uppercase" style="letter-spacing:.5px">Currency (Currency)</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                      <input type="text" class="form-control" name="currency"
                             value="<?= e($s['currency'] ?? 'BDT') ?>" maxlength="10">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-muted small text-uppercase" style="letter-spacing:.5px">Invoice prefix</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-receipt"></i></span>
                      <input type="text" class="form-control" name="invoice_prefix"
                             value="<?= e($s['invoice_prefix'] ?? 'INV') ?>" maxlength="10">
                    </div>
                    <small class="text-muted">e.g. INV-20260601-0001</small>
                  </div>
                </div>

                <div class="d-grid">
                  <button type="submit" class="btn btn-primary btn-lg" id="settingsSaveBtn">
                    <i class="bi bi-check-circle me-2"></i>Save settings
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Right: quick info + branding -->
        <div class="col-lg-5 d-flex flex-column gap-4">

          <!-- Quick preview -->
          <div class="card shadow-sm border-0">
            <div class="card-header fw-semibold bg-light py-3">
              <i class="bi bi-eye me-2 text-primary"></i>Invoice preview
            </div>
            <div class="card-body text-center py-4">
              <div class="border rounded p-3 bg-white text-start" style="font-size:.88rem">
                <div class="fw-bold fs-5 text-center mb-1"><?= e($s['shop_name'] ?? 'Shop name') ?></div>
                <div class="text-muted text-center small mb-1"><?= e($s['shop_address'] ?? 'Address') ?></div>
                <div class="text-muted text-center small">📞 <?= e($s['shop_phone'] ?? '—') ?></div>
                <hr class="my-2">
                <div class="d-flex justify-content-between small">
                  <span>Invoice No.:</span>
                  <span class="fw-semibold"><?= e($s['invoice_prefix'] ?? 'INV') ?>-20260601-0001</span>
                </div>
              </div>
              <small class="text-muted mt-2 d-block">After saving settings, it will appear like this on invoices/quotations</small>
            </div>
          </div>

          <!-- Branding card -->
          <div class="card border-0 shadow-sm overflow-hidden">
            <div style="background:linear-gradient(135deg,#0f0c29,#302b63,#24243e)">
              <div class="card-body text-center py-4">
                <div class="mb-3">
                  <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-10"
                       style="width:56px;height:56px">
                    <i class="bi bi-code-slash text-white fs-4"></i>
                  </div>
                </div>
                <p class="text-white-50 small mb-1">This software was developed by</p>
                <h5 class="text-white fw-bold mb-1">Softorio</h5>
                <p class="text-white-50 small mb-3">Custom Software Development<br>Bangladesh</p>
                <a href="https://softorio.com/our-founders.html" target="_blank" rel="noopener noreferrer"
                   class="btn btn-sm btn-outline-light px-4">
                  <i class="bi bi-people me-1"></i>About us
                </a>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div><!-- /shopTab -->

    <!-- ═══════════════════════════════════ SYSTEM TAB ════════════════════ -->
    <div class="tab-pane fade" id="sysTab">
      <div class="row g-4">

        <!-- Software -->
        <div class="col-md-6">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-header fw-semibold py-3 d-flex align-items-center gap-2">
              <i class="bi bi-box-seam text-primary"></i>Software information
            </div>
            <div class="list-group list-group-flush">
              <?php
              $swRows = [
                  ['bi-box-seam text-primary',   'App version',       'v' . APP_VERSION],
                  ['bi-app-indicator text-info',  'App name',          APP_NAME],
                  ['bi-filetype-php text-purple', 'PHP Version',         PHP_VERSION],
                  ['bi-database text-success',    'MySQL Version',       $dbVersion],
                  ['bi-hdd-rack text-warning',    'Web server',       $serverSW],
                  ['bi-plug text-secondary',      'Loaded extensions', $phpExt],
              ];
              foreach ($swRows as [$ico, $lbl, $val]): ?>
              <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                <span class="text-muted small"><i class="bi <?= $ico ?> me-2"></i><?= $lbl ?></span>
                <span class="fw-semibold small text-end" style="max-width:60%;word-break:break-all"><?= e($val) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Server -->
        <div class="col-md-6">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-header fw-semibold py-3 d-flex align-items-center gap-2">
              <i class="bi bi-server text-danger"></i>Server information
            </div>
            <div class="list-group list-group-flush">
              <?php
              $srvRows = [
                  ['bi-clock text-primary',       'Server time',      date('d M Y, h:i A')],
                  ['bi-calendar3 text-info',       'Today\'s date',      date('d F Y, l')],
                  ['bi-globe text-success',        'Timezone',          date_default_timezone_get()],
                  ['bi-shield-lock text-warning',  'HTTPS Active',     $httpsEnabled],
                  ['bi-memory text-danger',        'Memory limit',     $memLimit],
                  ['bi-hourglass text-secondary',  'Max Exec Time',    $maxExecTime . 's'],
                  ['bi-upload text-primary',       'Upload limit',     $uploadMax],
                  ['bi-hdd text-info',             'DB Size',          fmtBytes((float)$dbSize)],
              ];
              foreach ($srvRows as [$ico, $lbl, $val]): ?>
              <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                <span class="text-muted small"><i class="bi <?= $ico ?> me-2"></i><?= $lbl ?></span>
                <span class="fw-semibold small"><?= e($val) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Disk space full width -->
        <?php if ($diskTotal): ?>
        <div class="col-12">
          <div class="card shadow-sm border-0">
            <div class="card-header fw-semibold py-3 d-flex align-items-center gap-2">
              <i class="bi bi-hdd-fill text-secondary"></i>Disk space
            </div>
            <div class="card-body">
              <?php
              $used    = $diskTotal - $diskFree;
              $usedPct = round($used / $diskTotal * 100);
              $barCls  = $usedPct > 85 ? 'bg-danger' : ($usedPct > 65 ? 'bg-warning' : 'bg-success');
              ?>
              <div class="d-flex justify-content-between small mb-2">
                <span class="text-muted">Used: <strong><?= fmtBytes($used) ?></strong></span>
                <span class="text-muted">Total: <strong><?= fmtBytes($diskTotal) ?></strong></span>
                <span class="text-muted">Free: <strong class="text-success"><?= fmtBytes($diskFree) ?></strong></span>
              </div>
              <div class="progress" style="height:14px;border-radius:8px">
                <div class="progress-bar <?= $barCls ?> fw-semibold"
                     style="width:<?= $usedPct ?>%;font-size:.75rem">
                  <?= $usedPct ?>%
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div><!-- /sysTab -->

    <!-- ══════════════════════════════════ STATS TAB ══════════════════════ -->
    <div class="tab-pane fade" id="statsTab">

      <!-- Financial summary -->
      <div class="row g-3 mb-4">
        <?php
        $finCards = [
            ['Total sales',   money2($salesAmount), 'bi-cart-check-fill', 'primary'],
            ['Total paid',   money2($paidAmount),   'bi-check-circle-fill','success'],
            ['Total due',     money2($dueAmount),    'bi-exclamation-circle-fill','danger'],
            ['Total expense',      money2($totalExpense), 'bi-cash-stack',       'warning'],
            ['Stock value',   money2($stockValue),   'bi-boxes',            'info'],
            ['Net profit',      money2($salesAmount - $totalExpense), 'bi-graph-up-arrow', $salesAmount >= $totalExpense ? 'success' : 'danger'],
        ];
        foreach ($finCards as [$lbl, $val, $ico, $clr]): ?>
        <div class="col-md-4 col-sm-6">
          <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 py-3">
              <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-<?= $clr ?> bg-opacity-10"
                   style="width:48px;height:48px;flex-shrink:0">
                <i class="bi <?= $ico ?> text-<?= $clr ?> fs-5"></i>
              </div>
              <div class="overflow-hidden">
                <div class="fw-bold fs-6 text-truncate"><?= $val ?></div>
                <div class="text-muted small"><?= $lbl ?></div>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Record counts grid -->
      <div class="card shadow-sm border-0">
        <div class="card-header fw-semibold py-3 d-flex align-items-center gap-2">
          <i class="bi bi-database-fill text-primary"></i>Database records
        </div>
        <div class="card-body p-0">
          <div class="row g-0">
            <?php
            $records = [
                ['bi-people-fill',         'User',      $totalUsers,   'primary'],
                ['bi-box-seam',            'Products (active)',    $totalProd,    'success'],
                ['bi-tags-fill',           'Category',        $totalCats,    'info'],
                ['bi-person-lines-fill',   'Customer',         $totalCust,    'warning'],
                ['bi-shop-window',         'Branch',           $totalBranch,  'secondary'],
                ['bi-truck',               'Supplier',        $totalSupp,    'dark'],
                ['bi-cart-check',          'Sales',            $totalSales,   'primary'],
                ['bi-file-earmark-text',   'Quotation',           $totalQuote,   'info'],
                ['bi-arrow-down-circle',   'Stock Inbound',    $totalInbound, 'success'],
                ['bi-sliders',             'Stock Adjustment',      $totalAdj,     'warning'],
                ['bi-arrow-left-right',    'Stock Transfer',  $totalTrf,     'danger'],
                ['bi-cash-coin',           'Payment records',   $totalPay,     'success'],
            ];
            foreach ($records as $i => [$ico, $lbl, $cnt, $clr]):
                $border = $i % 3 !== 2 ? 'border-end' : '';
            ?>
            <div class="col-md-4 col-6 <?= $border ?> border-bottom">
              <div class="p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <div class="d-inline-flex align-items-center justify-content-center rounded bg-<?= $clr ?> bg-opacity-10"
                       style="width:36px;height:36px;flex-shrink:0">
                    <i class="bi <?= $ico ?> text-<?= $clr ?> small"></i>
                  </div>
                  <span class="small text-muted"><?= $lbl ?></span>
                </div>
                <span class="badge bg-<?= $clr ?> fs-6"><?= number_format((int)$cnt) ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div><!-- /statsTab -->

  </div><!-- /tab-content -->
</div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

function saveSettings(e) {
    e.preventDefault();
    const form = document.getElementById('settingsForm');
    const data = Object.fromEntries(new FormData(form).entries());
    const btn  = document.getElementById('settingsSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    ajaxPost(BASE_URL + '/api/save_settings.php', data, res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Save settings';
        showToast(res.message, res.success ? 'success' : 'danger');
    });
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
