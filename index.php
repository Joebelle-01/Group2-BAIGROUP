<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkLogin();

// Stats
$total_emp = $pdo->query("SELECT COUNT(*) FROM employees WHERE employment_status='Active'")->fetchColumn();
$total_dept = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$last_payroll = $pdo->query("SELECT * FROM payroll ORDER BY id DESC LIMIT 1")->fetch();
$total_payroll_cost = $pdo->query("SELECT COALESCE(SUM(net_pay),0) FROM payroll_details")->fetchColumn();

// Department payroll summary for Table and Chart
$dept_summary = $pdo->query("
    SELECT d.name AS department_name, COUNT(DISTINCT e.id) AS total_employees,
           COALESCE(SUM(pd.net_pay),0) AS total_payroll_cost
    FROM departments d
    LEFT JOIN employees e ON d.id = e.department_id
    LEFT JOIN payroll_details pd ON e.id = pd.employee_id
    GROUP BY d.id
")->fetchAll();

// Recent payrolls
$recent_payrolls = $pdo->query("SELECT p.*, COUNT(pd.id) as emp_count FROM payroll p LEFT JOIN payroll_details pd ON p.id=pd.payroll_id GROUP BY p.id ORDER BY p.id DESC LIMIT 5")->fetchAll();

require_once 'includes/health_check.php';
$health = runHealthCheck($pdo);
?>
<?php include 'includes/header.php'; ?>
<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-10 py-4 px-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h3 class="fw-bold mb-1">Dashboard</h3>
          <p class="text-muted small mb-0">Welcome back! Here's what's happening with your payroll today.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
          <?php if ($health['status'] !== 'healthy'): ?>
            <span class="badge bg-<?= $health['status'] === 'warning' ? 'warning text-dark' : 'danger' ?> p-2 shadow-sm" data-bs-toggle="collapse" data-bs-target="#healthDetails" style="cursor: pointer;">
              <i class="fas fa-heartbeat me-1"></i> System Status: <?= ucfirst($health['status']) ?>
            </span>
          <?php endif; ?>
          <button class="btn btn-primary" onclick="location.href='pages/process_payroll.php'">
            <i class="fas fa-plus me-2"></i>New Payroll Run
          </button>
        </div>
      </div>

      <?php if ($health['status'] !== 'healthy'): ?>
        <div class="collapse mb-4" id="healthDetails">
          <div class="card border-0 shadow-sm bg-light">
            <div class="card-body">
              <h6 class="fw-bold small mb-3">System Diagnostic Results</h6>
              <div class="row g-2">
                <?php foreach ($health['checks'] as $check): ?>
                  <div class="col-md-4">
                    <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded border-start border-4 border-<?= $check['type'] ?>">
                      <small><?= $check['name'] ?></small>
                      <span class="badge bg-<?= $check['type'] ?>"><?= $check['status'] ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- KPI Cards -->
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="card kpi-card text-primary h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3"><i class="fas fa-users fa-lg"></i></div>
                <span class="text-success small fw-bold"><i class="fas fa-arrow-up me-1"></i>Active</span>
              </div>
              <div class="text-muted small fw-bold text-uppercase">Total Employees</div>
              <div class="fs-2 fw-bold text-dark"><?= $total_emp ?></div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card kpi-card text-success h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="rounded-circle bg-success bg-opacity-10 p-3"><i class="fas fa-building fa-lg"></i></div>
              </div>
              <div class="text-muted small fw-bold text-uppercase">Departments</div>
              <div class="fs-2 fw-bold text-dark"><?= $total_dept ?></div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card kpi-card text-warning h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3"><i class="fas fa-peso-sign fa-lg"></i></div>
              </div>
              <div class="text-muted small fw-bold text-uppercase">Total Expenditure</div>
              <div class="fs-3 fw-bold text-dark">₱<?= number_format($total_payroll_cost, 2) ?></div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card kpi-card text-info h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="rounded-circle bg-info bg-opacity-10 p-3"><i class="fas fa-file-invoice-dollar fa-lg"></i></div>
              </div>
              <div class="text-muted small fw-bold text-uppercase">Last Payroll ID</div>
              <div class="fs-4 fw-bold text-dark"><?= $last_payroll ? $last_payroll['ref_no'] : 'N/A' ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Chart Section -->
        <div class="col-md-8">
          <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-white py-3">
              <h6 class="mb-0 fw-bold"><i class="fas fa-chart-pie me-2 text-primary"></i>Payroll Distribution by Department</h6>
            </div>
            <div class="card-body">
              <canvas id="payrollChart" height="250"></canvas>
            </div>
          </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="col-md-4">
          <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-white py-3">
              <h6 class="mb-0 fw-bold"><i class="fas fa-clock me-2 text-success"></i>Recent Payroll Runs</h6>
            </div>
            <div class="card-body p-0">
              <div class="list-group list-group-flush">
                <?php if ($recent_payrolls): foreach ($recent_payrolls as $rp): ?>
                <a href="pages/history.php?view=<?= $rp['id'] ?>" class="list-group-item list-group-item-action py-3 px-4 border-0">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="fw-bold text-dark small"><?= htmlspecialchars($rp['ref_no']) ?></div>
                      <div class="text-muted smaller"><?= $rp['month'] . ' ' . $rp['year'] ?> • <?= $rp['emp_count'] ?> Emp.</div>
                    </div>
                    <span class="badge <?= $rp['status']==='Finalized' ? 'bg-success' : 'bg-secondary' ?> rounded-pill">
                      <?= $rp['status'] ?>
                    </span>
                  </div>
                </a>
                <?php endforeach; else: ?>
                <div class="text-center py-5 text-muted">No payroll records yet.</div>
                <?php endif; ?>
              </div>
            </div>
            <div class="card-footer bg-white text-center border-0 py-3">
              <a href="pages/history.php" class="small fw-bold text-decoration-none">View All History <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('payrollChart').getContext('2d');
    const data = <?= json_encode($dept_summary) ?>;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map(d => d.department_name),
            datasets: [{
                data: data.map(d => d.total_payroll_cost),
                backgroundColor: [
                    '#4361ee', '#4cc9f0', '#4895ef', '#7209b7', '#f72585', '#b5179e'
                ],
                borderWidth: 0,
                hoverOffset: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { usePointStyle: true, padding: 20 } },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) label += ': ';
                            label += '₱' + new Intl.NumberFormat().format(context.raw);
                            return label;
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
});
</script>
<?php include 'includes/footer.php'; ?>
