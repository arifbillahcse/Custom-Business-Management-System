<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Branch.php';
requireLogin();
requireManagerOrAdmin();

$pageTitle = 'Branch Management';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content" id="mainContent">
<div class="container-fluid py-4">

  <div class="page-header">
    <h4 class="mb-0"><i class="bi bi-shop me-2 text-danger"></i>Branch Management</h4>
    <button class="btn btn-primary" onclick="openAddModal()">
      <i class="bi bi-plus-circle me-1"></i>New branch
    </button>
  </div>

  <!-- Info note -->
  <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-info-circle-fill me-2"></i>
    <strong>Branch system:</strong>
    Each branch will have its own separate stock. While creating an order, you can select which branch the products will come from.
    for staff users to a specific branch assign can be done.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>Branch name</th>
            <th>Phone</th>
            <th>Address</th>
            <th class="text-center">Staff</th>
            <th class="text-center">Stock entry</th>
            <th class="text-center">Sales</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody id="branchesBody">
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">
              <div class="spinner-border spinner-border-sm me-2"></div>Loading...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>
</div>

<!-- Add / Edit Modal -->
<div class="modal fade" id="branchModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="branchModalTitle">New branch</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="branchForm" onsubmit="submitBranch(event)">
        <div class="modal-body">
          <input type="hidden" id="branchId" name="id" value="">

          <div class="mb-3">
            <label class="form-label fw-semibold">
              Branch name <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" id="branchName" name="name"
                   required maxlength="150" autocomplete="off"
                   placeholder="e.g. Mirpur Branch, Uttara Branch">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Phone number</label>
            <input type="text" class="form-control" id="branchPhone" name="phone"
                   maxlength="20" placeholder="01XXXXXXXXX">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Address</label>
            <textarea class="form-control" id="branchAddress" name="address"
                      rows="2" maxlength="500"
                      placeholder="Full address of the branch"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelled</button>
          <button type="submit" class="btn btn-danger" id="branchSaveBtn">
            <i class="bi bi-check-circle me-1"></i>Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>/assets/js/branches.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
