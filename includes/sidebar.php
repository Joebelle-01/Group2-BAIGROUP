<nav class="col-md-2 sidebar py-3 no-print min-vh-100">
  <div class="sidebar-sticky d-flex flex-column h-100">
    <div class="brand-area px-3 mb-4">
      <h5 class="fw-bold text-white mb-0 text-center"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Payroll</h5>
    </div>
    
    <ul class="nav flex-column mb-auto">
      <li class="nav-item">
        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>index.php">
          <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= (strpos($_SERVER['PHP_SELF'], 'employees.php') !== false) ? 'active' : '' ?>" href="<?= BASE_URL ?>pages/employees.php">
          <i class="fas fa-users"></i> Employees
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= (strpos($_SERVER['PHP_SELF'], 'departments.php') !== false) ? 'active' : '' ?>" href="<?= BASE_URL ?>pages/departments.php">
          <i class="fas fa-building"></i> Departments
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= (strpos($_SERVER['PHP_SELF'], 'process_payroll.php') !== false) ? 'active' : '' ?>" href="<?= BASE_URL ?>pages/process_payroll.php">
          <i class="fas fa-money-check-alt"></i> Payroll
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= (strpos($_SERVER['PHP_SELF'], 'history.php') !== false) ? 'active' : '' ?>" href="<?= BASE_URL ?>pages/history.php">
          <i class="fas fa-history"></i> History
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= (strpos($_SERVER['PHP_SELF'], 'users.php') !== false) ? 'active' : '' ?>" href="<?= BASE_URL ?>pages/users.php">
          <i class="fas fa-user-shield"></i> Users
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= (strpos($_SERVER['PHP_SELF'], 'data_warehouse.php') !== false) ? 'active' : '' ?>" href="<?= BASE_URL ?>pages/data_warehouse.php">
          <i class="fas fa-database"></i> Warehouse
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= (strpos($_SERVER['PHP_SELF'], 'analytics.php') !== false) ? 'active' : '' ?>" href="<?= BASE_URL ?>pages/analytics.php">
          <i class="fas fa-chart-pie"></i> SQL Views & Ranks
        </a>
      </li>
    </ul>

    <div class="px-3 mt-auto mb-3">
      <a href="<?= BASE_URL ?>logout.php" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center py-2 shadow-sm border-0 bg-danger-subtle text-danger" title="Sign Out">
        <i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span>
      </a>
    </div>
  </div>
</nav>

