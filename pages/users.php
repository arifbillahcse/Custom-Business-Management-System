<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Branch.php';
requireLogin();
requireAdmin();

$pageTitle  = 'User Management';
$currentUid = (int)($_SESSION['user_id'] ?? 0);
$branches   = Branch::getBranches();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content" id="mainContent">
<div class="container-fluid py-4">

  <div class="page-header">
    <h4 class="mb-0"><i class="bi bi-people-fill me-2"></i>User Management</h4>
    <button class="btn btn-primary" onclick="openAddModal()">
      <i class="bi bi-person-plus me-1"></i>New user
    </button>
  </div>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Username</th>
            <th class="text-center">Role</th>
            <?php if (!empty($branches)): ?>
            <th>Branch</th>
            <?php endif; ?>
            <th class="text-center">Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody id="usersBody">
          <tr>
            <td colspan="6" class="text-center py-5 text-muted">
              <div class="spinner-border spinner-border-sm me-2"></div>Loading...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>
</div>

<!-- Add / Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="userModalTitle">New user</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="userForm" onsubmit="submitUser(event)">
        <div class="modal-body">
          <input type="hidden" id="userId" name="id" value="">
          <div class="mb-3">
            <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="userName" name="name" required maxlength="100">
          </div>
          <div class="mb-3" id="usernameGroup">
            <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="userUsername" name="username"
                   maxlength="50" autocomplete="off">
            <small class="text-muted">Username cannot be changed later.</small>
          </div>
          <div class="mb-3" id="passwordGroup">
            <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="userPassword" name="password"
                   minlength="4" autocomplete="new-password">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Role</label>
            <select class="form-select" id="userRole" name="role" onchange="toggleBranchField()">
              <option value="staff">Staff (Staff)</option>
              <option value="manager">Manager (Manager)</option>
              <option value="admin">Admin (Admin)</option>
            </select>
          </div>
          <?php if (!empty($branches)): ?>
          <div class="mb-3" id="branchFieldGroup">
            <label class="form-label fw-semibold">Branch</label>
            <select class="form-select" id="userBranch" name="branch_id">
              <option value="">— Select a branch —</option>
              <?php foreach ($branches as $b): ?>
              <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted">Select a branch for the staff user.</small>
          </div>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelled</button>
          <button type="submit" class="btn btn-primary" id="userSaveBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bi bi-key me-1"></i>Reset password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="passwordForm" onsubmit="submitPassword(event)">
        <div class="modal-body">
          <input type="hidden" id="pwUserId" value="">
          <p class="text-muted small mb-3">
            <span id="pwUserName" class="fw-semibold"></span> Enter a new password for
          </p>
          <div class="mb-3">
            <label class="form-label fw-semibold">New password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="pwNew" minlength="4" required autocomplete="new-password">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelled</button>
          <button type="submit" class="btn btn-warning" id="pwSaveBtn">Change</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const BASE_URL    = '<?= BASE_URL ?>';
const CURRENT_UID = <?= $currentUid ?>;
const HAS_BRANCHES = <?= !empty($branches) ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/users.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
