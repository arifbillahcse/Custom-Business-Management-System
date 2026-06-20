<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Customer.php';
requireLogin();

$pageTitle = 'Customer Management';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content" id="mainContent">
<div class="container-fluid py-4">

  <div class="page-header">
    <h4 class="mb-0"><i class="bi bi-people me-2"></i>Customer Management</h4>
    <button class="btn btn-primary" onclick="openAddModal()">
      <i class="bi bi-person-plus me-1"></i>New customer
    </button>
  </div>

  <div class="mb-3">
    <input type="text" id="searchInput" class="form-control"
           placeholder="Search by name or phone...">
  </div>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover mb-0" id="customersTable">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Address</th>
            <th class="text-end">Total purchase</th>
            <th class="text-end">Due</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody id="customersBody">
          <tr>
            <td colspan="7" class="text-center py-5 text-muted">
              <div class="spinner-border spinner-border-sm me-2"></div>Loading...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <!-- Pagination -->
    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top" id="custPaginationBar" style="display:none!important">
      <small class="text-muted" id="custPageInfo"></small>
      <nav><ul class="pagination pagination-sm mb-0" id="custPagination"></ul></nav>
    </div>
  </div>

</div>
</div>

<!-- Add / Edit Modal -->
<div class="modal fade" id="customerModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalTitle">New customer</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="customerForm" onsubmit="submitCustomer(event)">
        <div class="modal-body">
          <input type="hidden" id="customerId" name="id" value="">
          <div class="mb-3">
            <label class="form-label fw-semibold">
              Name <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" id="customerName"
                   name="name" required maxlength="150" autocomplete="off">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Phone number</label>
            <input type="text" class="form-control" id="customerPhone"
                   name="phone" maxlength="20">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Address</label>
            <textarea class="form-control" id="customerAddress"
                      name="address" rows="2" maxlength="500"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelled</button>
          <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const IS_ADMIN = <?= User::isAdminOrManager() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/customers.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
