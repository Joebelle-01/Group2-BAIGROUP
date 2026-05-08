<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkLoginForPages();

// Fetch active employees
$employees = $pdo->query("
    SELECT e.*, d.name as dept_name 
    FROM employees e 
    LEFT JOIN departments d ON e.department_id = d.id 
    WHERE e.employment_status = 'Active'
    ORDER BY e.last_name ASC
")->fetchAll();

$current_month = date('F');
$current_year = date('Y');
?>
<?php include '../includes/header.php'; ?>
<div class="container-fluid">
  <div class="row">
    <?php include '../includes/sidebar.php'; ?>
    <main class="col-md-10 py-4 px-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h3 class="fw-bold mb-1">Process Payroll</h3>
          <p class="text-muted small mb-0">Generate payroll for active employees for the selected period.</p>
        </div>
        <a href="<?= BASE_URL ?>index.php" class="btn btn-outline-primary btn-sm mb-3 mb-md-0">
          <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
      </div>

      <div id="alertPlaceholder"></div>

      <form id="payrollForm">
        <div class="row g-4">
          <!-- Period Selection -->
          <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
              <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-calendar-alt me-2"></i>Payroll Period</h6>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label small fw-bold">Month</label>
                  <select name="month" class="form-select shadow-sm" required>
                    <?php
                    $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                    foreach($months as $m) {
                        echo "<option value=\"$m\" ".($current_month == $m ? 'selected' : '').">$m</option>";
                    }
                    ?>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-bold">Year</label>
                  <input type="number" name="year" class="form-control shadow-sm" value="<?= $current_year ?>" required>
                </div>
                <div class="row g-2">
                  <div class="col-6">
                    <label class="form-label small fw-bold">From</label>
                    <input type="date" name="date_from" id="date_from" class="form-control shadow-sm" required>
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-bold">To</label>
                    <input type="date" name="date_to" id="date_to" class="form-control shadow-sm" required>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Employee Selection -->
          <div class="col-md-8">
            <div class="card h-100 shadow-sm border-0">
              <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-user-check me-2"></i>Select Employees</h6>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="selectAll">
                  <label class="form-check-label small fw-bold" for="selectAll">Select All</label>
                </div>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 400px;">
                  <table class="table table-hover align-middle">
                    <thead class="sticky-top bg-light">
                      <tr>
                        <th width="40"></th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th class="text-end">Salary Rate</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if ($employees): foreach ($employees as $emp): ?>
                      <tr>
                        <td>
                          <input type="checkbox" name="emp_ids[]" value="<?= $emp['id'] ?>" class="emp-check form-check-input">
                        </td>
                        <td>
                          <div class="fw-bold"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></div>
                          <div class="text-muted smaller"><?= $emp['employee_id'] ?></div>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($emp['dept_name'] ?? 'N/A') ?></span></td>
                        <td class="text-end fw-bold">₱<?= number_format($emp['salary_rate'], 2) ?></td>
                      </tr>
                      <?php endforeach; else: ?>
                      <tr><td colspan="4" class="text-center py-5">No active employees found.</td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="card-footer bg-white py-3 text-center">
                <button type="submit" id="btnProcess" class="btn btn-primary btn-lg px-5 shadow-sm">
                  <i class="fas fa-check-circle me-2"></i>Finalize and Process Payroll
                </button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </main>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const empChecks = document.querySelectorAll('.emp-check');
    const form = document.getElementById('payrollForm');
    const btnProcess = document.getElementById('btnProcess');
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');

    selectAll.addEventListener('change', function() {
        empChecks.forEach(cb => cb.checked = selectAll.checked);
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const checkedCount = Array.from(empChecks).filter(cb => cb.checked).length;
        if (checkedCount === 0) {
            App.showAlert('warning', 'Please select at least one employee to process payroll.');
            return;
        }

        if (new Date(dateTo.value) < new Date(dateFrom.value)) {
            App.showAlert('danger', 'Pay period end date cannot be earlier than start date.');
            return;
        }

        if (confirm(`Are you sure you want to process payroll for ${checkedCount} employees? This action will finalize the records.`)) {
            const res = await App.ajaxRequest('process_payroll', new FormData(form), btnProcess);
            if (res.success) {
                App.showAlert('success', res.message);
                setTimeout(() => {
                    window.location.href = 'history.php?view=' + res.id;
                }, 1500);
            } else {
                App.showAlert('danger', res.message);
            }
        }
    });
});
</script>
<?php include '../includes/footer.php'; ?>
