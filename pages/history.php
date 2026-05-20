<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkLoginForPages();

// Fetch departments and years for filtering
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
$years = $pdo->query("SELECT DISTINCT year FROM payroll ORDER BY year DESC")->fetchAll();

$filter_dept = isset($_GET['department_id']) && $_GET['department_id'] !== '' ? (int)$_GET['department_id'] : '';
$filter_year = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : '';

// Fetch all payroll runs with totals, applying dynamic filters
$where_clauses = [];
$params = [];

if ($filter_year) {
    $where_clauses[] = "p.year = ?";
    $params[] = $filter_year;
}

if ($filter_dept) {
    // Show payroll runs that have at least one employee in the filtered department
    $where_clauses[] = "p.id IN (SELECT DISTINCT pd.payroll_id FROM payroll_details pd JOIN employees e ON pd.employee_id = e.id WHERE e.department_id = ?)";
    $params[] = $filter_dept;
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

$payroll_stmt = $pdo->prepare("
    SELECT p.*, 
           COUNT(pd.id) AS emp_count,
           COALESCE(SUM(pd.net_pay),0) AS total_net,
           COALESCE(SUM(pd.deductions),0) AS total_deductions
    FROM payroll p
    LEFT JOIN payroll_details pd ON p.id = pd.payroll_id
    $where_sql
    GROUP BY p.id
    ORDER BY p.id DESC
");
$payroll_stmt->execute($params);
$payrolls = $payroll_stmt->fetchAll();

// If viewing a specific payroll
$view_id = isset($_GET['view']) ? (int)$_GET['view'] : null;
$view_details = [];
$view_payroll = null;
if ($view_id) {
    $view_payroll = $pdo->prepare("SELECT * FROM payroll WHERE id = ?");
    $view_payroll->execute([$view_id]);
    $view_payroll = $view_payroll->fetch();

    // Prepare query for specific payroll slip details with optional department filter
    $view_details_query = "
        SELECT pd.*, 
               CONCAT(e.first_name,' ',e.last_name) AS full_name,
               e.employee_id AS emp_code,
               e.position,
               d.name AS dept_name
        FROM payroll_details pd
        JOIN employees e ON pd.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE pd.payroll_id = ?
    ";
    $params_details = [$view_id];
    
    if ($filter_dept) {
        $view_details_query .= " AND e.department_id = ?";
        $params_details[] = $filter_dept;
    }
    
    $view_details_query .= " ORDER BY e.last_name";
    
    $view_details_stmt = $pdo->prepare($view_details_query);
    $view_details_stmt->execute($params_details);
    $view_details = $view_details_stmt->fetchAll();
}
?>
<?php include '../includes/header.php'; ?>
<div class="container-fluid">
  <div class="row">
    <?php include '../includes/sidebar.php'; ?>

    <main class="col-md-10 py-4 px-4">
      <div id="alertPlaceholder"></div>
      <h4 class="fw-bold mb-4"><i class="fas fa-history me-2 text-primary"></i>Payroll History</h4>

      <!-- Filtering Form (Syllabus Requirement) -->
      <div class="card shadow-sm border-0 mb-4 no-print">
        <div class="card-body p-3">
          <form method="GET" class="row g-3 align-items-end">
            <?php if ($view_payroll): ?>
              <input type="hidden" name="view" value="<?= $view_id ?>">
            <?php endif; ?>

            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Department</label>
              <select name="department_id" class="form-select shadow-sm">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                  <option value="<?= $dept['id'] ?>" <?= $filter_dept === (int)$dept['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dept['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <?php if (!$view_payroll): ?>
              <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Year</label>
                <select name="year" class="form-select shadow-sm">
                  <option value="">All Years</option>
                  <?php foreach ($years as $y): ?>
                    <option value="<?= $y['year'] ?>" <?= $filter_year === (int)$y['year'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($y['year']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>

            <div class="col-md-3 d-flex gap-2">
              <button type="submit" class="btn btn-primary px-3 shadow-sm d-flex align-items-center justify-content-center flex-grow-1">
                <i class="fas fa-filter me-2"></i>Apply Filter
              </button>
              <a href="<?= $view_payroll ? '?view=' . $view_payroll['id'] : 'history.php' ?>" class="btn btn-light px-3 shadow-sm border d-flex align-items-center justify-content-center flex-grow-1">
                <i class="fas fa-undo me-2"></i>Reset
              </a>
            </div>
          </form>
        </div>
      </div>

      <?php if ($view_payroll): ?>
      <!-- Detail View with Print capability -->
      <div class="d-flex justify-content-between mb-3">
        <a href="history.php" class="btn btn-outline-secondary btn-sm no-print"><i class="fas fa-arrow-left me-1"></i>Back to History</a>
        <button onclick="window.print()" class="btn btn-outline-primary btn-sm no-print"><i class="fas fa-print me-1"></i>Print Pay Slips</button>
      </div>
      <div class="card shadow-sm mb-3">
        <div class="card-header"><strong>Payroll Ref:</strong> <?= htmlspecialchars($view_payroll['ref_no']) ?> &nbsp;|&nbsp; <strong>Period:</strong> <?= $view_payroll['month'] . ' ' . $view_payroll['year'] ?> &nbsp;|&nbsp; <span class="badge bg-success"><?= $view_payroll['status'] ?></span></div>
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr><th>Emp ID</th><th>Name</th><th>Department</th><th>Position</th><th>Basic Pay</th><th>Allowances</th><th>Deductions</th><th class="text-success">Net Pay</th><th>Version</th><th class="no-print text-end">Action</th></tr>
            </thead>
            <tbody>
              <?php foreach ($view_details as $d): ?>
              <tr>
                <td><code><?= htmlspecialchars($d['emp_code']) ?></code></td>
                <td><?= htmlspecialchars($d['full_name']) ?></td>
                <td><?= htmlspecialchars($d['dept_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($d['position']) ?></td>
                <td>₱<?= number_format($d['basic_pay'],2) ?></td>
                <td class="text-success">+₱<?= number_format($d['allowances'],2) ?></td>
                <td class="text-danger">-₱<?= number_format($d['deductions'],2) ?></td>
                <td class="fw-bold text-success">₱<?= number_format($d['net_pay'],2) ?></td>
                <td><span class="badge bg-secondary">v<?= $d['version'] ?></span></td>
                <td class="no-print text-end">
                  <button class="btn btn-outline-primary btn-sm rounded-circle border shadow-sm" type="button" 
                          onclick='openEditDetailModal(<?= json_encode([
                              'id' => $d['id'],
                              'full_name' => $d['full_name'],
                              'basic_pay' => $d['basic_pay'],
                              'allowances' => $d['allowances'],
                              'deductions' => $d['deductions'],
                              'version' => $d['version']
                          ]) ?>)' title="Adjust Pay Slip Details">
                    <i class="fas fa-edit"></i>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
              <tr>
                <td colspan="7" class="text-end">Total Net Pay:</td>
                <td class="text-success">₱<?= number_format(array_sum(array_column($view_details,'net_pay')),2) ?></td>
                <td></td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <?php else: ?>
      <div class="card shadow-sm">
        <div class="card-header">All Payroll Runs</div>
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr><th>Ref No</th><th>Period</th><th>Date Range</th><th>Employees</th><th>Total Net Pay</th><th>Total Deductions</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php if ($payrolls): foreach ($payrolls as $p): ?>
              <tr>
                <td><code><?= htmlspecialchars($p['ref_no']) ?></code></td>
                <td><?= $p['month'] . ' ' . $p['year'] ?></td>
                <td><small><?= $p['date_from'] ?> to <?= $p['date_to'] ?></small></td>
                <td><?= $p['emp_count'] ?></td>
                <td class="fw-bold text-success">₱<?= number_format($p['total_net'],2) ?></td>
                <td class="text-danger">₱<?= number_format($p['total_deductions'],2) ?></td>
                <td><span class="badge <?= $p['status']==='Finalized' ? 'bg-success' : 'bg-secondary' ?>"><?= $p['status'] ?></span></td>
                <td><a href="?view=<?= $p['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-eye me-1"></i>View</a></td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="8" class="text-center text-muted py-4">No payroll runs yet. Go to <a href="process_payroll.php">Process Payroll</a>.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>
    </main>
  </div>
</div>
<!-- Edit Payroll Detail Modal (Optimistic Concurrency Control Demo) -->
<div class="modal fade" id="editDetailModal" tabindex="-1" aria-labelledby="editDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="editDetailModalLabel">
          <i class="fas fa-calculator text-primary me-2"></i>Edit Pay Slip: <span id="lblEmpName" class="text-secondary"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editDetailForm">
        <input type="hidden" name="action" value="edit_payroll_detail">
        <input type="hidden" name="id" id="detail_id">
        <input type="hidden" name="version" id="detail_version">
        
        <div class="modal-body p-4">
          <!-- OCC Concurrency Control explanation alert in modal -->
          <div class="alert alert-warning border-start border-4 border-warning rounded shadow-sm py-2 px-3 mb-4 small bg-warning-subtle text-warning-emphasis">
            <i class="fas fa-shield-alt me-1 text-warning"></i>
            <strong>Optimistic Concurrency Active!</strong> If another administrator updates this record concurrently, version tracking will prevent conflicts.
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Basic Pay (₱)</label>
            <input type="number" name="basic_pay" id="detail_basic_pay" class="form-control shadow-sm" step="0.01" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Allowances (₱)</label>
            <input type="number" name="allowances" id="detail_allowances" class="form-control shadow-sm" step="0.01" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Deductions (₱)</label>
            <input type="number" name="deductions" id="detail_deductions" class="form-control shadow-sm" step="0.01" required>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light shadow-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btnSaveDetail" class="btn btn-primary px-4 shadow-sm">Save Adjustments</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let detailModal;
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('editDetailModal');
    if (modalEl) {
        detailModal = new bootstrap.Modal(modalEl);
        
        document.getElementById('editDetailForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveDetail');
            
            const res = await App.ajaxRequest('edit_payroll_detail', new FormData(this), btn);
            if (res.success) {
                detailModal.hide();
                App.showAlert('success', res.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                // If it is a concurrency clash, the alert will pop up with the full detailed message
                App.showAlert('danger', res.message);
            }
        });
    }
});

function openEditDetailModal(data) {
    document.getElementById('lblEmpName').innerText = data.full_name;
    document.getElementById('detail_id').value = data.id;
    document.getElementById('detail_version').value = data.version;
    document.getElementById('detail_basic_pay').value = parseFloat(data.basic_pay).toFixed(2);
    document.getElementById('detail_allowances').value = parseFloat(data.allowances).toFixed(2);
    document.getElementById('detail_deductions').value = parseFloat(data.deductions).toFixed(2);
    detailModal.show();
}
</script>
<?php include '../includes/footer.php'; ?>
