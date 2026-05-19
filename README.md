# Payroll PIT System - Modernized

A comprehensive, modern payroll management system built with PHP and MySQL. This system features automated payroll calculations, department analytics, and a built-in Data Warehouse for advanced reporting.

## 🚀 How to Run the System

### 1. Prerequisites
*   **XAMPP** (or any server environment with Apache and MySQL).
*   **PHP 8.0 or higher** is recommended.

### 2. Database Setup
1.  Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
2.  Create a new database named **`payroll_db`**.
3.  Click on the **Import** tab.
4.  Choose the file located at: `database/database.sql` in this project folder.
5.  Click **Go** to run the import.

### 3. File Placement
1.  Copy the entire `PayrollPIT` folder into your XAMPP's `htdocs` directory (usually `C:\xampp\htdocs`).

### 4. Configuration
1.  Open `config/db.php`.
2.  Ensure the credentials match your local setup:
    ```php
    $host = 'localhost';
    $db   = 'payroll_db';
    $user = 'root';
    $pass = ''; // Default XAMPP password is empty
    ```

### 5. Accessing the System
1. Run terminal php -S localhost:8000
2.  Open your browser and go to: `http://localhost/PayrollPIT`
3.  **Default Login Credentials:**
    *   **Username:** `admin`
    *   **Password:** `password`

---

## 🛠️ How it Works (Technical Overview)

### 🏗️ Architecture
The system follows a modular PHP architecture designed for scalability and ease of maintenance:
*   **Core Logic**: Located in `includes/` (authentication, constants, and utilities).
*   **Database Access**: Uses **PDO (PHP Data Objects)** for secure, SQL-injection-resistant database interactions.
*   **UI/UX**: Built with **Bootstrap 5**, **Vanilla CSS**, and **FontAwesome** icons for a premium, responsive experience.

### 🧩 Key Modules

#### 1. Authentication & Security
*   Uses a centralized `auth.php` utility to protect every page.
*   Unauthorized users are automatically redirected to the login page.

#### 2. Payroll Processing
*   **Automatic Calculations**: Calculates SSS, PhilHealth, and Pag-IBIG contributions based on monthly salary.
*   **Concurrency Control**: Uses a "versioning" system in the database to prevent duplicate or conflicting payroll runs.

#### 3. Data Warehouse (DW) & ETL
*   **Star Schema**: The system includes a dedicated Data Warehouse (`dim_employees`, `dim_departments`, `fact_payroll`).
*   **ETL Process**: When you click "Sync Analytics," the system extracts data from the operational tables, transforms it, and loads it into the DW for fast reporting without slowing down the main system.

#### 4. Health Check System
*   The Dashboard features a diagnostic utility (`includes/health_check.php`) that automatically verifies database tables and data integrity every time you log in.

#### 5. History & Reporting
*   All finalized payroll runs are stored in the History module, allowing you to view detailed breakdowns of past transactions at any time.
