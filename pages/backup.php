<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/User.php';
requireAdmin();

$pageTitle = 'Backup & Restore';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content" id="mainContent">
<div class="container-fluid py-4">

  <div class="page-header">
    <h4 class="mb-0"><i class="bi bi-database-fill-down me-2"></i>Backup & Restore</h4>
  </div>

  <!-- Alert area -->
  <div id="importAlert" class="d-none mb-4"></div>

  <div class="row g-4">

    <!-- Export -->
    <div class="col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
              <i class="bi bi-download fs-4"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold">Data export</h5>
              <small class="text-muted">SQL Take a backup as a file</small>
            </div>
          </div>

          <p class="text-muted mb-4">
            All data (customers, sales, stock, payments, etc.) into a single <code>.sql</code> Download to a file.
            With this file, the database can be restored at any time.
          </p>

          <ul class="list-unstyled mb-4">
            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Full data of all tables</li>
            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>with table structure</li>
            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>phpMyAdmin directly importable into</li>
          </ul>

          <a href="<?= BASE_URL ?>/api/export_db.php" class="btn btn-primary w-100" id="exportBtn">
            <i class="bi bi-download me-2"></i>SQL Download backup
          </a>
          <small class="text-muted d-block text-center mt-2">File name: backup_<?= DB_NAME ?>_YYYYMMDD_HHMMSS.sql</small>
        </div>
      </div>
    </div>

    <!-- Import -->
    <div class="col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="stat-icon bg-danger bg-opacity-10 text-danger">
              <i class="bi bi-upload fs-4"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold">Data import</h5>
              <small class="text-muted">SQL Restore from file</small>
            </div>
          </div>

          <div class="alert alert-warning d-flex gap-2 mb-3 py-2">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
            <small><strong>Warning:</strong> Importing will erase the current data. Use only trusted backup files.</small>
          </div>

          <p class="text-muted mb-4">
            previously downloaded <code>.sql</code> Upload a backup file to restore the database.
            Maximum file size: <strong>50 MB</strong>।
          </p>

          <form id="importForm">
            <div class="mb-3">
              <label class="form-label fw-semibold">SQL Select a file</label>
              <input type="file" class="form-control" id="sqlFile" accept=".sql" required>
            </div>
            <button type="submit" class="btn btn-danger w-100" id="importBtn">
              <i class="bi bi-upload me-2"></i>Import & Restore
            </button>
          </form>

          <!-- Confirm Import Modal -->
          <div class="modal fade" id="confirmImportModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                  <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Dangerous action — confirm</h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <div class="alert alert-danger mb-3">
                    <strong>This action is irreversible!</strong> When importing:
                    <ul class="mb-0 mt-2">
                      <li>Current <strong>All data will be erased</strong></li>
                      <li>All sales, payment, and stock records will be lost</li>
                      <li>this action being undone <strong>not possible</strong></li>
                    </ul>
                  </div>
                  <p class="mb-2 fw-semibold">in the box below to confirm <code class="text-danger">I am sure</code> Type:</p>
                  <input type="text" id="confirmPhrase" class="form-control" placeholder='Type here...' autocomplete="off">
                  <div class="form-text text-muted mt-1">must be typed exactly</div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="button" class="btn btn-danger" id="confirmImportBtn" disabled>
                    <i class="bi bi-upload me-2"></i>Yes, import
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- Info cards -->
  <div class="row g-4 mt-2">
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-info"></i>Backup-related information</h6>
          <div class="row g-3">
            <div class="col-md-4">
              <div class="p-3 bg-light rounded">
                <h6 class="text-muted small mb-1">Regular backup</h6>
                <p class="mb-0 small">Make a habit of taking daily or weekly backups. Especially take a backup after large sales.</p>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 bg-light rounded">
                <h6 class="text-muted small mb-1">Save file</h6>
                <p class="mb-0 small">Keep the backup file on Google Drive or a pen drive. Keeping it in multiple places is safer.</p>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 bg-light rounded">
                <h6 class="text-muted small mb-1">Restore process</h6>
                <p class="mb-0 small">Take a backup of the current data before restoring. Reload the page once the import is complete.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const CONFIRM_PHRASE = 'I am sure';

let confirmModal = null;

// Step 1: form submit → open confirmation modal
document.getElementById('importForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const file = document.getElementById('sqlFile').files[0];
    if (!file) return;

    // Reset modal state
    document.getElementById('confirmPhrase').value = '';
    document.getElementById('confirmImportBtn').disabled = true;

    if (!confirmModal) confirmModal = new bootstrap.Modal(document.getElementById('confirmImportModal'));
    confirmModal.show();

    // Focus phrase input after modal opens
    document.getElementById('confirmImportModal').addEventListener('shown.bs.modal', () => {
        document.getElementById('confirmPhrase').focus();
    }, { once: true });
});

// Enable confirm button only when phrase matches exactly
document.getElementById('confirmPhrase').addEventListener('input', function() {
    document.getElementById('confirmImportBtn').disabled = (this.value !== CONFIRM_PHRASE);
});

// Step 2: confirmed — run import
document.getElementById('confirmImportBtn').addEventListener('click', function() {
    const file = document.getElementById('sqlFile').files[0];
    if (!file) return;

    confirmModal.hide();

    const importBtn = document.getElementById('importBtn');
    importBtn.disabled = true;
    importBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importing...';

    const formData = new FormData();
    formData.append('sql_file', file);

    fetch(BASE_URL + '/api/import_db.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            const alertEl = document.getElementById('importAlert');
            alertEl.className = 'mb-4 alert alert-' + (res.success ? 'success' : 'danger');
            alertEl.innerHTML = '<i class="bi bi-' + (res.success ? 'check-circle' : 'x-circle') + ' me-2"></i>' + res.message;
            alertEl.classList.remove('d-none');
            alertEl.scrollIntoView({ behavior: 'smooth' });

            importBtn.disabled = false;
            importBtn.innerHTML = '<i class="bi bi-upload me-2"></i>Import & Restore';

            if (res.success) document.getElementById('sqlFile').value = '';
        })
        .catch(() => {
            const alertEl = document.getElementById('importAlert');
            alertEl.className = 'mb-4 alert alert-danger';
            alertEl.innerHTML = '<i class="bi bi-x-circle me-2"></i>Connection to the server was lost.';
            alertEl.classList.remove('d-none');

            importBtn.disabled = false;
            importBtn.innerHTML = '<i class="bi bi-upload me-2"></i>Import & Restore';
        });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
