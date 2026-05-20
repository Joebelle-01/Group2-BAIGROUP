<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkLoginForPages();

// Fetch Employee Salary Ranks from the vw_employee_salary_rank view (demonstrates window functions)
$salary_ranks = [];
try {
    $salary_ranks = $pdo->query("SELECT * FROM vw_employee_salary_rank ORDER BY salary_rank ASC")->fetchAll();
} catch (PDOException $e) {
    // If the database views haven't been created yet, let's gracefully capture this
    $error_msg = "Error reading view: " . $e->getMessage();
}

// Fetch Department summary from the vw_department_payroll_summary view (demonstrates aggregations/joins in views)
$dept_summaries = [];
try {
    $dept_summaries = $pdo->query("SELECT * FROM vw_department_payroll_summary ORDER BY total_payroll_cost DESC")->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Error reading view: " . $e->getMessage();
}
?>
<?php include '../includes/header.php'; ?>
<div class="container-fluid">
  <div class="row">
    <?php include '../includes/sidebar.php'; ?>
    <main class="col-md-10 py-4 px-4">
      
      <!-- Premium Page Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>Database Views & Window Ranks</h3>
        <a href="<?= BASE_URL ?>index.php" class="btn btn-outline-primary btn-sm">
          <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
      </div>

      <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center" role="alert">
          <i class="fas fa-exclamation-triangle me-2 fs-4"></i>
          <div>
            <strong>Database Error!</strong> <?= htmlspecialchars($error_msg) ?><br>
            <span class="small">Make sure to import <code>database/database.sql</code> which sets up these views.</span>
          </div>
        </div>
      <?php endif; ?>

      <div class="row g-4">
        <!-- Leaderboard Card -->
        <div class="col-lg-8">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
              <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-trophy text-warning me-2"></i>Employee Salary Leaderboard</h6>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th class="ps-4" width="70">Rank</th>
                      <th>Employee</th>
                      <th>Department</th>
                      <th>Position</th>
                      <th class="text-end">Salary Rate</th>
                      <th class="text-center pe-4">Dept. Rank</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($salary_ranks)): foreach ($salary_ranks as $row): ?>
                      <tr>
                        <td class="ps-4">
                          <?php if ($row['salary_rank'] == 1): ?>
                            <span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-crown"></i> 1st</span>
                          <?php elseif ($row['salary_rank'] == 2): ?>
                            <span class="badge bg-secondary text-white px-2 py-1">2nd</span>
                          <?php elseif ($row['salary_rank'] == 3): ?>
                            <span class="badge bg-danger bg-opacity-75 text-white px-2 py-1">3rd</span>
                          <?php else: ?>
                            <span class="badge bg-light text-dark border px-2 py-1">#<?= $row['salary_rank'] ?></span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <div class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></div>
                          <small class="text-muted"><?= htmlspecialchars($row['employee_id']) ?></small>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['department']) ?></span></td>
                        <td><?= htmlspecialchars($row['position'] ?? 'N/A') ?></td>
                        <td class="text-end fw-bold text-dark">₱<?= number_format($row['salary_rate'], 2) ?></td>
                        <td class="text-center pe-4">
                          <span class="badge bg-info bg-opacity-10 text-info fw-bold rounded-pill px-3">
                            #<?= $row['dept_rank'] ?> in <?= htmlspecialchars($row['department']) ?>
                          </span>
                        </td>
                      </tr>
                    <?php endforeach; else: ?>
                      <tr><td colspan="6" class="text-center py-5 text-muted">No ranking data. Set up employees first.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Departmental Aggregations Card -->
        <div class="col-lg-4">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
              <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-building me-2"></i>Department Summary</h6>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th class="ps-4">Department</th>
                      <th class="text-center">Staff</th>
                      <th class="text-end pe-4">Avg Net Pay</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($dept_summaries)): foreach ($dept_summaries as $row): ?>
                      <tr>
                        <td class="ps-4">
                          <div class="fw-bold"><?= htmlspecialchars($row['department_name']) ?></div>
                          <small class="text-muted text-uppercase smaller">Total Cost: ₱<?= number_format($row['total_payroll_cost'], 2) ?></small>
                        </td>
                        <td class="text-center">
                          <span class="badge bg-secondary rounded-pill"><?= $row['total_employees'] ?></span>
                        </td>
                        <td class="text-end fw-bold text-success pe-4">
                          ₱<?= number_format($row['average_net_pay'], 2) ?>
                        </td>
                      </tr>
                    <?php endforeach; else: ?>
                      <tr><td colspan="3" class="text-center py-5 text-muted">No department records. Add departments first.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
