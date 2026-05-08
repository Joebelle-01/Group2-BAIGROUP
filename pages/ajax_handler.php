<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'delete_employee':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception("Invalid Employee ID.");

            $pdo->beginTransaction();
            
            // Cleanup Data Warehouse to prevent FK issues
            $pdo->prepare("DELETE FROM fact_payroll WHERE emp_key IN (SELECT emp_key FROM dim_employees WHERE original_id = ?)")->execute([$id]);
            $pdo->prepare("DELETE FROM dim_employees WHERE original_id = ?")->execute([$id]);
            
            // Delete the employee (payroll_details will CASCADE delete due to FK constraint)
            $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Employee deleted successfully.']);
            } else {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Employee not found.']);
            }
            break;

        case 'add_employee':
            $emp_id = trim($_POST['employee_id'] ?? '');
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $salary_rate = (float)($_POST['salary_rate'] ?? 0);
            
            if (empty($emp_id) || empty($first_name) || empty($last_name)) throw new Exception("Required fields are missing.");

            $stmt = $pdo->prepare("INSERT INTO employees (employee_id, first_name, last_name, department_id, position, salary_rate, date_hired, employment_status) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $emp_id, $first_name, $last_name,
                (int)$_POST['department_id'],
                $_POST['position'],
                $salary_rate,
                $_POST['date_hired'],
                $_POST['employment_status']
            ]);
            echo json_encode(['success' => true, 'message' => 'Employee added successfully!']);
            break;

        case 'edit_employee':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception("Invalid Employee ID.");

            $stmt = $pdo->prepare("UPDATE employees SET first_name=?, last_name=?, department_id=?, position=?, salary_rate=?, employment_status=? WHERE id=?");
            $stmt->execute([
                $_POST['first_name'],
                $_POST['last_name'],
                (int)$_POST['department_id'],
                $_POST['position'],
                (float)$_POST['salary_rate'],
                $_POST['employment_status'],
                $id
            ]);
            echo json_encode(['success' => true, 'message' => 'Employee updated successfully!']);
            break;

        case 'get_employee':
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
            $stmt->execute([$id]);
            $emp = $stmt->fetch();
            if (!$emp) throw new Exception("Employee not found.");
            echo json_encode(['success' => true, 'data' => $emp]);
            break;

        case 'toggle_status':
            $id = (int)($_POST['id'] ?? 0);
            $current_status = $_POST['current_status'] ?? 'Active';
            $new_status = ($current_status === 'Active') ? 'Inactive' : 'Active';
            
            $stmt = $pdo->prepare("UPDATE employees SET employment_status = ? WHERE id = ?");
            $stmt->execute([$new_status, $id]);
            
            echo json_encode(['success' => true, 'message' => "Status updated to $new_status.", 'new_status' => $new_status]);
            break;

        case 'process_payroll':
            $date_from = $_POST['date_from'] ?? '';
            $date_to = $_POST['date_to'] ?? '';
            $month = $_POST['month'] ?? '';
            $year = (int)($_POST['year'] ?? 0);
            $checked_ids = $_POST['emp_ids'] ?? [];

            if (empty($checked_ids)) throw new Exception("Please select at least one employee.");
            if (empty($date_from) || empty($date_to)) throw new Exception("Pay period dates are required.");

            $pdo->beginTransaction();

            $ref_no = 'PR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
            
            // Insert the payroll record directly as Finalized
            $stmt = $pdo->prepare("INSERT INTO payroll (ref_no, date_from, date_to, month, year, status) VALUES (?,?,?,?,?,'Finalized')");
            $stmt->execute([$ref_no, $date_from, $date_to, $month, $year]);
            $payroll_id = $pdo->lastInsertId();

            foreach ($checked_ids as $emp_id) {
                $emp_stmt = $pdo->prepare("SELECT salary_rate FROM employees WHERE id = ?");
                $emp_stmt->execute([$emp_id]);
                $emp = $emp_stmt->fetch();
                
                if (!$emp) continue;

                $basic_pay = $emp['salary_rate'];
                $allowances = 0; 
                $deductions = 0; 
                $net_pay = $basic_pay + $allowances - $deductions;

                $pd_stmt = $pdo->prepare("INSERT INTO payroll_details (payroll_id, employee_id, basic_pay, allowances, deductions, net_pay) VALUES (?,?,?,?,?,?)");
                $pd_stmt->execute([$payroll_id, $emp_id, $basic_pay, $allowances, $deductions, $net_pay]);
            }

            // AUTOMATIC SYNC: Update Data Warehouse immediately
            // We call it here before commit to ensure data integrity
            syncDataWarehouse($pdo);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Payroll processed and Data Warehouse updated!', 'id' => $payroll_id]);
            break;

        case 'run_etl':
            $pdo->beginTransaction();
            syncDataWarehouse($pdo);
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Warehouse synced successfully.']);
            break;

        case 'clear_warehouse':
            $pdo->beginTransaction();
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE fact_payroll; TRUNCATE TABLE dim_employees; TRUNCATE TABLE dim_departments; TRUNCATE TABLE dim_dates; SET FOREIGN_KEY_CHECKS = 1;");
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Warehouse cleared.']);
            break;

        case 'save_department':
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            if (empty($name)) throw new Exception("Department name is required.");
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE departments SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $description, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
            }
            echo json_encode(['success' => true, 'message' => 'Department saved!']);
            break;

        case 'get_department':
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
            $stmt->execute([$id]);
            $dept = $stmt->fetch();
            if (!$dept) throw new Exception("Department not found.");
            echo json_encode(['success' => true, 'data' => $dept]);
            break;

        case 'delete_department':
            $id = (int)($_POST['id'] ?? 0);
            $check = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE department_id = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) throw new Exception("Cannot delete department with employees.");
            $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Department deleted!']);
            break;

        case 'save_user':
            $id = (int)($_POST['id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($full_name)) throw new Exception("Name and username are required.");

            if ($id > 0) {
                if (!empty($password)) {
                    $stmt = $pdo->prepare("UPDATE users SET username=?, full_name=?, password=? WHERE id=?");
                    $stmt->execute([$username, $full_name, password_hash($password, PASSWORD_DEFAULT), $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username=?, full_name=? WHERE id=?");
                    $stmt->execute([$username, $full_name, $id]);
                }
            } else {
                if (empty($password)) throw new Exception("Password is required for new users.");
                $stmt = $pdo->prepare("INSERT INTO users (username, full_name, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $full_name, password_hash($password, PASSWORD_DEFAULT)]);
            }
            echo json_encode(['success' => true, 'message' => 'User saved successfully!']);
            break;

        case 'delete_user':
            $id = (int)($_POST['id'] ?? 0);
            if ($id == $_SESSION['user_id']) throw new Exception("Cannot delete yourself.");
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'User deleted successfully!']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
            break;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Consolidated ETL logic for the Data Warehouse
 * Ensures dimension tables are up to date before populating the fact table.
 */
function syncDataWarehouse($pdo) {
    // 1. Ensure a default 'N/A' department exists in the dimension table
    $pdo->exec("INSERT IGNORE INTO dim_departments (original_id, dept_name) VALUES (0, 'N/A')");

    // 2. Sync Employees Dimension
    $pdo->exec("INSERT INTO dim_employees (original_id, employee_id, full_name, position)
                SELECT id, employee_id, CONCAT(first_name,' ',last_name), position FROM employees
                ON DUPLICATE KEY UPDATE employee_id=VALUES(employee_id), full_name=VALUES(full_name), position=VALUES(position)");

    // 3. Sync Departments Dimension
    $pdo->exec("INSERT INTO dim_departments (original_id, dept_name)
                SELECT id, name FROM departments
                ON DUPLICATE KEY UPDATE dept_name=VALUES(dept_name)");

    // 4. Sync Dates Dimension (based on all payroll records)
    $pdo->exec("INSERT INTO dim_dates (full_date, month, year, quarter)
                SELECT DISTINCT date_from, month, year, QUARTER(date_from) FROM payroll
                ON DUPLICATE KEY UPDATE month=VALUES(month), year=VALUES(year), quarter=VALUES(quarter)");

    // 5. Populate Fact Table
    // Use COALESCE for dept_key to handle employees with no department
    $pdo->exec("INSERT INTO fact_payroll (emp_key, dept_key, date_key, net_pay, total_deductions, total_allowances, original_detail_id)
        SELECT 
            de.emp_key, 
            COALESCE(dd.dept_key, (SELECT dept_key FROM dim_departments WHERE original_id=0 LIMIT 1)), 
            dt.date_key, 
            pd.net_pay, 
            pd.deductions, 
            pd.allowances, 
            pd.id
        FROM payroll_details pd
        JOIN payroll p ON pd.payroll_id = p.id
        JOIN employees e ON pd.employee_id = e.id
        JOIN dim_employees de ON de.original_id = e.id
        LEFT JOIN dim_departments dd ON dd.original_id = e.department_id
        JOIN dim_dates dt ON dt.full_date = p.date_from
        ON DUPLICATE KEY UPDATE 
            emp_key=VALUES(emp_key),
            dept_key=VALUES(dept_key),
            date_key=VALUES(date_key),
            net_pay=VALUES(net_pay), 
            total_deductions=VALUES(total_deductions), 
            total_allowances=VALUES(total_allowances)");
}
