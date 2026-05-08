<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkLoginForPages();

// Fetch departments with employee counts
$departments = $pdo->query("
    SELECT d.*, COUNT(e.id) as employee_count 
    FROM departments d 
    LEFT JOIN employees e ON d.id = e.department_id 
    GROUP BY d.id 
    ORDER BY d.name ASC
")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="container-fluid">
  <div class="row">
    <?php include '../includes/sidebar.php'; ?>
    <main class="col-md-10 py-4 px-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-building me-2 text-primary"></i>Department Management</h4>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#deptModal" onclick="resetForm()">
          <i class="fas fa-plus me-2"></i>Add New Department
        </button>
      </div>

      <div id="alertPlaceholder"></div>

      <div class="card shadow-sm border-0">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
              <thead class="bg-light">
                <tr>
                  <th class="ps-4">Department Name</th>
                  <th>Description</th>
                  <th class="text-center">Employees</th>
                  <th class="text-end pe-4">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($departments): foreach ($departments as $d): ?>
                <tr id="dept-row-<?= $d['id'] ?>">
                  <td class="ps-4 fw-semibold"><?= htmlspecialchars($d['name']) ?></td>
                  <td class="text-muted"><?= htmlspecialchars($d['description'] ?: 'No description provided') ?></td>
                  <td class="text-center">
                    <span class="badge rounded-pill bg-info bg-opacity-10 text-info px-3">
                      <?= $d['employee_count'] ?> Members
                    </span>
                  </td>
                  <td class="text-end pe-4">
                    <div class="btn-group shadow-sm">
                      <button class="btn btn-white btn-sm border" onclick="editDept(<?= $d['id'] ?>)" title="Edit">
                        <i class="fas fa-edit text-primary"></i>
                      </button>
                      <button class="btn btn-white btn-sm border" onclick="deleteDept(<?= $d['id'] ?>)" title="Delete">
                        <i class="fas fa-trash text-danger"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; else: ?>
                <tr id="no-depts">
                  <td colspan="4" class="text-center py-5 text-muted">
                    <i class="fas fa-folder-open fa-3x mb-3 opacity-25"></i>
                    <p>No departments found. Click the button above to add one.</p>
                  </td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Department Modal -->
<div class="modal fade" id="deptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 bg-light">
        <h5 class="modal-title fw-bold" id="modalTitle">Add Department</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="deptForm">
        <div class="modal-body py-4">
          <input type="hidden" name="action" value="save_department">
          <input type="hidden" name="id" id="dept_id" value="0">
          <div class="mb-3">
            <label class="form-label small fw-bold">Department Name</label>
            <input type="text" name="name" id="dept_name" class="form-control bg-light border-0" placeholder="e.g. Finance, Marketing" required>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-bold">Description</label>
            <textarea name="description" id="dept_description" class="form-control bg-light border-0" rows="3" placeholder="Brief overview of department duties"></textarea>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 shadow-sm" id="btnSave">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let deptModal;
let deptForm;

document.addEventListener('DOMContentLoaded', function() {
    deptModal = new bootstrap.Modal(document.getElementById('deptModal'));
    deptForm = document.getElementById('deptForm');

    deptForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btnSave = document.getElementById('btnSave');
        
        const res = await App.ajaxRequest('save_department', new FormData(this), btnSave);
        if (res.success) {
            App.showAlert('success', res.message);
            deptModal.hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            App.showAlert('danger', res.message);
        }
    });
});

function resetForm() {
    if (deptForm) {
        deptForm.reset();
        document.getElementById('dept_id').value = 0;
        document.getElementById('modalTitle').innerText = 'Add Department';
    }
}

async function editDept(id) {
    const res = await App.ajaxRequest({
        url: `ajax_handler.php?action=get_department&id=${id}`,
        method: 'GET'
    });

    if (res.success) {
        document.getElementById('dept_id').value = res.data.id;
        document.getElementById('dept_name').value = res.data.name;
        document.getElementById('dept_description').value = res.data.description;
        document.getElementById('modalTitle').innerText = 'Edit Department';
        deptModal.show();
    } else {
        App.showAlert('danger', res.message);
    }
}

async function deleteDept(id) {
    if (confirm('Are you sure you want to delete this department? This cannot be undone.')) {
        const row = document.getElementById('dept-row-' + id);
        const res = await App.ajaxRequest('delete_department', { id: id });

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
