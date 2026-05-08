<?php
/**
 * Health Check Utility
 * Verifies the integrity of the system and database schema
 */

function runHealthCheck($pdo) {
    $results = [
        'status' => 'healthy',
        'checks' => []
    ];

    // 1. Check Tables
    $required_tables = ['users', 'employees', 'departments', 'payroll', 'payroll_details', 'dim_employees', 'dim_departments', 'dim_dates', 'fact_payroll'];
    foreach ($required_tables as $table) {
        try {
            $pdo->query("SELECT 1 FROM $table LIMIT 1");
            $results['checks'][] = ['name' => "Table: $table", 'status' => 'OK', 'type' => 'success'];
        } catch (PDOException $e) {
            $results['checks'][] = ['name' => "Table: $table", 'status' => 'Missing', 'type' => 'danger'];
            $results['status'] = 'warning';
        }
    }

    // 2. Check Version Column for Concurrency
    try {
        $pdo->query("SELECT version FROM payroll_details LIMIT 1");
        $results['checks'][] = ['name' => "Concurrency Column (version)", 'status' => 'OK', 'type' => 'success'];
    } catch (PDOException $e) {
        $results['checks'][] = ['name' => "Concurrency Column (version)", 'status' => 'Missing', 'type' => 'warning'];
        if ($results['status'] === 'healthy') $results['status'] = 'warning';
    }

    // 3. Check for Orphaned Payroll Details
    $orphans = $pdo->query("
        SELECT COUNT(*) 
        FROM payroll_details pd 
        LEFT JOIN employees e ON pd.employee_id = e.id 
        WHERE e.id IS NULL
    ")->fetchColumn();
    
    if ($orphans > 0) {
        $results['checks'][] = ['name' => "Orphaned Payroll Records", 'status' => "$orphans found", 'type' => 'danger'];
        $results['status'] = 'unhealthy';
    } else {
        $results['checks'][] = ['name' => "Data Integrity (No Orphans)", 'status' => "OK", 'type' => 'success'];
    }

    // 4. Check Data Warehouse Mapping
    $missing_emp_keys = $pdo->query("
        SELECT COUNT(*) 
        FROM fact_payroll f 
        LEFT JOIN dim_employees d ON f.emp_key = d.emp_key 
        WHERE d.emp_key IS NULL
    ")->fetchColumn();

    if ($missing_emp_keys > 0) {
        $results['checks'][] = ['name' => "DW Dimension Integrity", 'status' => "$missing_emp_keys broken links", 'type' => 'warning'];
        if ($results['status'] === 'healthy') $results['status'] = 'warning';
    }

    return $results;
}
?>
