-- Payroll Management System Database Script
-- For IT221 Final Project

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- 1. OPERATIONAL DATABASE
-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS payroll_db;
USE payroll_db;

-- Users Table (Admin Login)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Departments Table
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Employees Table
CREATE TABLE IF NOT EXISTS `employees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` VARCHAR(20) NOT NULL UNIQUE,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `department_id` INT,
  `position` VARCHAR(100),
  `salary_rate` DECIMAL(10,2) NOT NULL,
  `employment_status` ENUM('Active', 'Inactive', 'Resigned') DEFAULT 'Active',
  `date_hired` DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Payroll Master Table
CREATE TABLE IF NOT EXISTS `payroll` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ref_no` VARCHAR(50) NOT NULL UNIQUE,
  `date_from` DATE NOT NULL,
  `date_to` DATE NOT NULL,
  `month` VARCHAR(20) NOT NULL,
  `year` INT NOT NULL,
  `status` ENUM('Draft', 'Finalized') DEFAULT 'Draft',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Payroll Details Table (The Transactional Part)
CREATE TABLE IF NOT EXISTS `payroll_details` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `payroll_id` INT NOT NULL,
  `employee_id` INT NOT NULL,
  `basic_pay` DECIMAL(10,2) NOT NULL,
  `allowances` DECIMAL(10,2) DEFAULT 0.00,
  `deductions` DECIMAL(10,2) DEFAULT 0.00,
  `net_pay` DECIMAL(10,2) NOT NULL,
  `version` INT DEFAULT 1, -- For Optimistic Concurrency Control
  FOREIGN KEY (`payroll_id`) REFERENCES `payroll`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 2. DATA WAREHOUSE (STAR SCHEMA)
-- --------------------------------------------------------

-- Dimension Tables
CREATE TABLE IF NOT EXISTS `dim_employees` (
  `emp_key` INT AUTO_INCREMENT PRIMARY KEY,
  `original_id` INT UNIQUE,
  `employee_id` VARCHAR(20),
  `full_name` VARCHAR(150),
  `position` VARCHAR(100)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `dim_departments` (
  `dept_key` INT AUTO_INCREMENT PRIMARY KEY,
  `original_id` INT UNIQUE,
  `dept_name` VARCHAR(100)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `dim_dates` (
  `date_key` INT AUTO_INCREMENT PRIMARY KEY,
  `full_date` DATE UNIQUE,
  `month` VARCHAR(20),
  `year` INT,
  `quarter` INT
) ENGINE=InnoDB;

-- Fact Table
CREATE TABLE IF NOT EXISTS `fact_payroll` (
  `fact_id` INT AUTO_INCREMENT PRIMARY KEY,
  `emp_key` INT,
  `dept_key` INT,
  `date_key` INT,
  `net_pay` DECIMAL(10,2),
  `total_deductions` DECIMAL(10,2),
  `total_allowances` DECIMAL(10,2),
  `original_detail_id` INT UNIQUE,
  FOREIGN KEY (`emp_key`) REFERENCES `dim_employees`(`emp_key`),
  FOREIGN KEY (`dept_key`) REFERENCES `dim_departments`(`dept_key`),
  FOREIGN KEY (`date_key`) REFERENCES `dim_dates`(`date_key`)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 3. ADVANCED SQL: VIEWS & WINDOW FUNCTIONS
-- --------------------------------------------------------

-- View for Department Payroll Summary
CREATE OR REPLACE VIEW vw_department_payroll_summary AS
SELECT 
    d.name AS department_name,
    COUNT(pd.employee_id) AS total_employees,
    SUM(pd.net_pay) AS total_payroll_cost,
    AVG(pd.net_pay) AS average_net_pay
FROM departments d
JOIN employees e ON d.id = e.department_id
JOIN payroll_details pd ON e.id = pd.employee_id
GROUP BY d.id;

-- View for Employee Ranking based on Salary (Window Function)
CREATE OR REPLACE VIEW vw_employee_salary_rank AS
SELECT 
    e.employee_id,
    CONCAT(e.first_name, ' ', e.last_name) AS full_name,
    d.name AS department,
    e.salary_rate,
    RANK() OVER (ORDER BY e.salary_rate DESC) as salary_rank,
    ROW_NUMBER() OVER (PARTITION BY d.name ORDER BY e.salary_rate DESC) as dept_rank
FROM employees e
JOIN departments d ON e.department_id = d.id;

-- --------------------------------------------------------
-- 4. SAMPLE DATA
-- --------------------------------------------------------

INSERT INTO `users` (`username`, `password`, `full_name`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator'); -- password is 'password'

INSERT INTO `departments` (`name`, `description`) VALUES
('IT Department', 'Information Technology and Support'),
('Human Resources', 'HR and Recruitment'),
('Finance', 'Accounting and Payroll');

INSERT INTO `employees` (`employee_id`, `first_name`, `last_name`, `department_id`, `position`, `salary_rate`, `date_hired`) VALUES
('EMP-001', 'John', 'Doe', 1, 'Senior Developer', 75000.00, '2023-01-15'),
('EMP-002', 'Jane', 'Smith', 1, 'Junior Developer', 45000.00, '2023-06-20'),
('EMP-003', 'Robert', 'Brown', 2, 'HR Manager', 60000.00, '2022-11-10'),
('EMP-004', 'Emily', 'Davis', 3, 'Accountant', 55000.00, '2023-03-05');

COMMIT;
