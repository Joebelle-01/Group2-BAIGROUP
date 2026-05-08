<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkLoginForPages();

$users = $pdo->query("SELECT id, username, full_name, created_at FROM users")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="container-fluid">
  <div class="row">
    <?php include '../includes/sidebar.php'; ?>
    <main class="col-md-10 py-4 px-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h4 class="fw-bold mb-1"><i class="fas fa-user-shield me-2 text-primary"></i>System Users</h4>
          <p class="text-muted small mb-0">Manage administrator accounts and system access.</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
          <i class="fas fa-user-plus me-2"></i>Add Admin User
        </button>
      </div>

      <div id="alertPlaceholder"></div>

      <div class="card shadow-sm border-0">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="bg-light">
                <tr>
                  <th class="ps-4">Full Name</th>
                  <th>Username</th>
                  <th>Created At</th>
                  <th class="text-end pe-4">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $u): ?>
                <tr id="user-row-<?= $u['id'] ?>">
                  <td class="ps-4 fw-bold"><?= htmlspecialchars($u['full_name']) ?></td>
                  <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($u['username']) ?></span></td>
                  <td><small class="text-muted"><?= date('M d, Y h:i A', strtotime($u['created_at'])) ?></small></td>
                  <td class="text-end pe-4">
                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                    <button class="btn btn-outline-danger btn-sm" onclick="deleteUser(<?= $u['id'] ?>)" title="Delete User">
                      <i class="fas fa-trash"></i>
                    </button>
                    <?php else: ?>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3">Current User</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 bg-light">
        <h5 class="modal-title fw-bold" id="modalTitle">Add New Admin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="userForm">
        <input type="hidden" name="action" value="save_user">
        <input type="hidden" name="id" id="user_id" value="0">
        <div class="modal-body py-4">
          <div class="mb-3">
            <label class="form-label small fw-bold">Full Name</label>
            <input type="text" name="full_name" id="full_name" class="form-control bg-light border-0" placeholder="Enter full name" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Username</label>
            <input type="text" name="username" id="username" class="form-control bg-light border-0" placeholder="Enter username" required>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-bold">Password</label>
            <input type="password" name="password" id="password" class="form-control bg-light border-0" placeholder="Enter password">
            <div id="passwordHelp" class="form-text smaller text-muted mt-1">Leave blank to keep existing password when editing.</div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 shadow-sm" id="btnSaveUser">Save User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let userModal;
let userForm;

document.addEventListener('DOMContentLoaded', function() {
    userModal = new bootstrap.Modal(document.getElementById('userModal'));
    userForm = document.getElementById('userForm');

    userForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btnSave = document.getElementById('btnSaveUser');
        
        const res = await App.ajaxRequest('save_user', new FormData(this), btnSave);
        if (res.success) {
            App.showAlert('success', res.message);
            userModal.hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            App.showAlert('danger', res.message);
        }
    });
});

function resetForm() {
    if (userForm) {
        userForm.reset();
        document.getElementById('user_id').value = 0;
        document.getElementById('modalTitle').innerText = 'Add New Admin';
        document.getElementById('password').required = true;
        document.getElementById('passwordHelp').style.display = 'none';
    }
}

async function deleteUser(id) {
    if (confirm('Are you sure you want to delete this administrator account? This cannot be undone.')) {
        const row = document.getElementById('user-row-' + id);
        const res = await App.ajaxRequest('delete_user', { id: id });

        if (res.success) {
            App.showAlert('success', res.message);
            if (row) {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(20px)';
                setTimeout(() => row.remove(), 300);
            }
        } else {
            App.showAlert('danger', res.message);
        }
    }
}
</script>
<?php include '../includes/footer.php'; ?>
