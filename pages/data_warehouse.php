<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkLoginForPages();



// Analytics: Dept summary from DW
$dw_dept_summary = $pdo->query("
    SELECT dd.dept_name, COUNT(*) AS fact_count, SUM(fp.net_pay) AS total_pay, AVG(fp.net_pay) AS avg_pay
    FROM fact_payroll fp
    JOIN dim_departments dd ON fp.dept_key = dd.dept_key
    GROUP BY dd.dept_key
")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="container-fluid">
  <div class="row">
    <?php include '../includes/sidebar.php'; ?>
    <main class="col-md-10 py-4 px-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h3 class="fw-bold mb-1">Data Warehouse & Analytics</h3>
          <p class="text-muted small mb-0">Synchronize operational data for long-term storage and analytical reporting.</p>
        </div>
        <div class="d-flex gap-2">
           <button type="button" class="btn btn-primary shadow-sm px-4" id="btnSync">
            <i class="fas fa-sync me-2"></i>Sync Analytics
          </button>
          <button type="button" class="btn btn-outline-danger btn-sm" id="btnClear">
            <i class="fas fa-trash-alt me-2"></i>Clear Warehouse
          </button>
        </div>
      </div>

      <div id="alertPlaceholder"></div>



      <div class="row g-4">
        <div class="col-md-12">
          <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
              <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-chart-line me-2"></i>Department Performance (DW Data)</h6>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th>Department</th>
                      <th class="text-center">Records</th>
                      <th class="text-end">Total Net Pay</th>
                      <th class="text-end">Avg Net Pay</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($dw_dept_summary): foreach ($dw_dept_summary as $row): ?>
                    <tr>
                      <td class="fw-bold"><?= htmlspecialchars($row['dept_name']) ?></td>
                      <td class="text-center"><span class="badge bg-primary rounded-pill"><?= $row['fact_count'] ?></span></td>
                      <td class="text-end fw-bold text-success">₱<?= number_format($row['total_pay'], 2) ?></td>
                      <td class="text-end">₱<?= number_format($row['avg_pay'], 2) ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="4" class="text-center py-5 text-muted">
                      <i class="fas fa-database fa-3x mb-3 opacity-25"></i><br>
                      Warehouse is empty. Sync data to see analytics.
                    </td></tr>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnSync = document.getElementById('btnSync');
    const btnClear = document.getElementById('btnClear');

    async function submitDW(action, button) {
        const res = await App.ajaxRequest(action, {}, button);
        if (res.success) {
            App.showAlert('success', res.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            App.showAlert('danger', res.message);
        }
    }

    btnSync.addEventListener('click', function() {
        submitDW('run_etl', btnSync);
    });

    btnClear.addEventListener('click', function() {
        if (confirm('CAUTION: This will permanently wipe all data from the warehouse. Dimensions and Facts will be cleared. Proceed?')) {
            submitDW('clear_warehouse', btnClear);
        }
    });
});
</script>
<?php include '../includes/footer.php'; ?>
