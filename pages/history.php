<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkLoginForPages();

// Fetch all payroll runs with totals
$payrolls = $pdo->query("
    SELECT p.*, 
           COUNT(pd.id) AS emp_count,
           COALESCE(SUM(pd.net_pay),0) AS total_net,
           COALESCE(SUM(pd.deductions),0) AS total_deductions
    FROM payroll p
    LEFT JOIN payroll_details pd ON p.id = pd.payroll_id
    GROUP BY p.id
    ORDER BY p.id DESC
")->fetchAll();

// If viewing a specific payroll
$view_id = isset($_GET['view']) ? (int)$_GET['view'] : null;
$view_details = [];
$view_payroll = null;
if ($view_id) {
    $view_payroll = $pdo->prepare("SELECT * FROM payroll WHERE id = ?");
    $view_payroll->execute([$view_id]);
    $view_payroll = $view_payroll->fetch();

    $view_details = $pdo->prepare("
        SELECT pd.*, 
               CONCAT(e.first_name,' ',e.last_name) AS full_name,
               e.employee_id AS emp_code,
               e.position,
               d.name AS dept_name
        FROM payroll_details pd
        JOIN employees e ON pd.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE pd.payroll_id = ?
        ORDER BY e.last_name
    ");
    $view_details->execute([$view_id]);
    $view_details = $view_details->fetchAll();
}
?>
<?php include '../includes/header.php'; ?>
<div class="container-fluid">
  <div class="row">
    <?php include '../includes/sidebar.php'; ?>

    <main class="col-md-10 py-4 px-4">
      <h4 class="fw-bold mb-4"><i class="fas fa-history me-2 text-primary"></i>Payroll History</h4>

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
              <tr><th>Emp ID</th><th>Name</th><th>Department</th><th>Position</th><th>Basic Pay</th><th>Allowances</th><th>Deductions</th><th class="text-success">Net Pay</th><th>Version</th></tr>
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
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
              <tr>
                <td colspan="7" class="text-end">Total Net Pay:</td>
                <td class="text-success">₱<?= number_format(array_sum(array_column($view_details,'net_pay')),2) ?></td>
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
<?php include '../includes/footer.php'; ?>
