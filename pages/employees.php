<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkLoginForPages();

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$employees   = $pdo->query("
    SELECT e.*, d.name AS dept_name
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    ORDER BY e.employment_status ASC, e.last_name ASC
")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="container-fluid">
  <div class="row">
    <?php include '../includes/sidebar.php'; ?>

    <main class="col-md-10 py-4 px-4">
      <div id="alertPlaceholder"></div>

      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-users me-2 text-primary"></i>Employee Management</h4>
        <button class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#addEmpModal" onclick="resetAddForm()"><i class="fas fa-plus me-1"></i>Add Employee</button>
      </div>

      <div class="card shadow-sm border-0">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">Employee</th>
                  <th>Department</th>
                  <th>Position</th>
                  <th>Salary Rate</th>
                  <th>Status</th>
                  <th class="text-end pe-4">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($employees)): ?>
                  <tr><td colspan="6" class="text-center text-muted py-5">No employees found. Add one to get started.</td></tr>
                <?php else: ?>
                  <?php foreach ($employees as $emp): ?>
                  <tr class="<?= $emp['employment_status'] === 'Inactive' ? 'table-light opacity-75' : '' ?>" id="emp_row_<?= $emp['id'] ?>">
                    <td class="ps-4">
                      <div class="fw-bold"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></div>
                      <small class="text-muted"><?= htmlspecialchars($emp['employee_id']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($emp['dept_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($emp['position']) ?></td>
                    <td>₱<?= number_format($emp['salary_rate'], 2) ?></td>
                    <td>
                      <span class="badge rounded-pill status-badge <?= $emp['employment_status'] === 'Active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                        <?= $emp['employment_status'] ?>
                      </span>
                    </td>
                    <td class="text-end pe-4">
                      <div class="dropdown">
                        <div class="d-flex justify-content-end align-items-center gap-2">
                          <button class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="deleteEmployee(<?= $emp['id'] ?>, this)" title="Delete Permanently">
                            <i class="fas fa-trash-alt"></i>
                          </button>
                          <button class="btn btn-light btn-sm rounded-circle border shadow-sm" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2">
                            <li><a class="dropdown-item rounded py-2" href="#" onclick='openEditModal(<?= json_encode($emp) ?>)'><i class="fas fa-edit me-2 text-primary"></i>Edit Details</a></li>
                            <li><a class="dropdown-item rounded py-2 toggle-btn" href="javascript:void(0)" onclick="toggleStatus(<?= $emp['id'] ?>, this)"><i class="fas <?= $emp['employment_status'] == 'Active' ? 'fa-user-slash text-warning' : 'fa-user-check text-success' ?> me-2"></i><span><?= $emp['employment_status'] == 'Active' ? 'Deactivate' : 'Activate' ?></span></a></li>
                          </ul>
                        </div>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmpModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header"><h5 class="modal-title fw-bold">Add New Employee</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form id="addEmpForm">
        <input type="hidden" name="action" value="add_employee">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label small fw-bold">Employee ID</label><input type="text" name="employee_id" class="form-control" required placeholder="e.g. EMP-005"></div>
            <div class="col-md-4"><label class="form-label small fw-bold">First Name</label><input type="text" name="first_name" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label small fw-bold">Last Name</label><input type="text" name="last_name" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label small fw-bold">Department</label>
              <select name="department_id" class="form-select" required>
                <option value="">Select Department</option>
                <?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4"><label class="form-label small fw-bold">Position</label><input type="text" name="position" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label small fw-bold">Salary Rate (₱)</label><input type="number" name="salary_rate" class="form-control" step="0.01" required></div>
            <div class="col-md-4"><label class="form-label small fw-bold">Date Hired</label><input type="date" name="date_hired" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-4"><label class="form-label small fw-bold">Status</label>
              <select name="employment_status" class="form-select">
                <option>Active</option><option>Inactive</option><option>Resigned</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary px-4 shadow-sm">Save Employee</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="fas fa-user-edit me-2 text-primary"></i>Edit Employee</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="editEmpForm">
        <input type="hidden" name="action" value="edit_employee">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label small fw-bold">First Name</label><input type="text" name="first_name" id="edit_fname" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label small fw-bold">Last Name</label><input type="text" name="last_name" id="edit_lname" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label small fw-bold">Department</label>
              <select name="department_id" id="edit_dept" class="form-select" required>
                <?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>"><?= $d['name'] ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label small fw-bold">Position</label><input type="text" name="position" id="edit_pos" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label small fw-bold">Salary Rate (Monthly)</label><input type="number" name="salary_rate" id="edit_salary" class="form-control" step="0.01" required></div>
            <div class="col-md-6"><label class="form-label small fw-bold">Employment Status</label>
              <select name="employment_status" id="edit_status" class="form-select">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
                <option value="Resigned">Resigned</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let editModal, addModal;

document.addEventListener('DOMContentLoaded', function() {
    editModal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
    addModal = new bootstrap.Modal(document.getElementById('addEmpModal'));

    // Add Form Submit
    document.getElementById('addEmpForm').onsubmit = async function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const res = await App.ajaxRequest('add_employee', new FormData(this), btn);
        if (res.success) {
            addModal.hide();
            App.showAlert('success', res.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            App.showAlert('danger', res.message);
        }
    };

    // Edit Form Submit
    document.getElementById('editEmpForm').onsubmit = async function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const res = await App.ajaxRequest('edit_employee', new FormData(this), btn);
        if (res.success) {
            editModal.hide();
            App.showAlert('success', res.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            App.showAlert('danger', res.message);
        }
    };
});

function resetAddForm() {
    document.getElementById('addEmpForm').reset();
}

function openEditModal(emp) {
    document.getElementById('edit_id').value = emp.id;
    document.getElementById('edit_fname').value = emp.first_name;
    document.getElementById('edit_lname').value = emp.last_name;
    document.getElementById('edit_dept').value = emp.department_id;
    document.getElementById('edit_pos').value = emp.position;
    document.getElementById('edit_salary').value = emp.salary_rate;
    document.getElementById('edit_status').value = emp.employment_status;
    editModal.show();
}

async function deleteEmployee(id, btn) {
    if (!App.confirm('ARE YOU SURE? This will permanently delete the employee and all their payroll history!')) return;
    
    const res = await App.ajaxRequest('delete_employee', { id: id }, btn);
    if (res.success) {
        const row = document.getElementById(`emp_row_${id}`);
        row.style.transition = 'all 0.5s ease';
        row.style.opacity = '0';
        row.style.transform = 'translateX(20px)';
        setTimeout(() => row.remove(), 500);
        App.showAlert('success', res.message);
    } else {
        App.showAlert('danger', res.message);
    }
}

async function toggleStatus(id, link) {
    const badge = link.closest('tr').querySelector('.status-badge');
    const currentStatus = badge.innerText.trim();
    
    const res = await App.ajaxRequest('toggle_status', { id: id, current_status: currentStatus });
    if (res.success) {
        // Dynamic update
        const isNowActive = res.new_status === 'Active';
        badge.innerText = res.new_status;
        badge.className = `badge rounded-pill status-badge ${isNowActive ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'}`;
        
        const icon = link.querySelector('i');
        const span = link.querySelector('span');
        icon.className = `fas ${isNowActive ? 'fa-user-slash text-warning' : 'fa-user-check text-success'} me-2`;
        span.innerText = isNowActive ? 'Deactivate' : 'Activate';
        
        link.closest('tr').className = isNowActive ? '' : 'table-light opacity-75';
        App.showAlert('success', res.message);
    } else {
        App.showAlert('danger', res.message);
    }
}
</script>
    </main>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
