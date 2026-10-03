# EduStaff: Teacher Attendance and Salary Management System
**Tagline:** *Simplifying Staff Management in Education.*

---

## 1. Project Overview
**EduStaff** is a web-based management system designed using **PHP, MySQL, HTML5, CSS3, and JavaScript**. It simplifies administrative workflows for educational institutions (schools, colleges, coaching institutes) by digitizing teacher attendance tracking, automating monthly payroll calculations based on attendance records, and generating printable payslips.

---

## 2. Core System Features

### A. Admin / Accountant Dashboard
* **Real-time Overview:** Total active teachers, daily present/absent count, and monthly payroll commitment.
* **Activity Log:** Recent attendance entries for the current date.

### B. Teacher Management (CRUD)
* **Registration:** Record teacher details (code, name, email, phone, subject, joining date, base salary).
* **Status Management:** Toggle teacher status between `active` and `inactive`.

### C. Attendance Tracking
* **Batch Daily Marking:** Mark status (`present`, `absent`, `late`, `leave`) with optional remarks for all active teachers on a selected date.
* **Duplicate Protection:** MySQL unique constraint prevents duplicate attendance records for the same teacher on the same date.

### D. Payroll & Salary Calculation Engine
* **Attendance-based Auto-Deductions:**
  $$\text{Per Day Rate} = \frac{\text{Base Salary}}{\text{Total Days in Month}}$$
  $$\text{Unauthorized Deduction} = (\text{Absent Days} \times \text{Per Day Rate}) + \left(\left\lfloor\frac{\text{Late Days}}{3}\right\rfloor \times \text{Per Day Rate}\right)$$
  $$\text{Net Salary} = \max(0, \text{Base Salary} + \text{Allowances} - \text{Total Deductions})$$
* **Custom Adjustments:** Add manual allowances/bonuses and custom fine deductions.

### E. Reports & Payslips
* **Printable Payslip:** Detailed monthly summary including teacher info, attendance metrics, earnings, deductions, and net salary disburse details.

---

## 3. Database Architecture (`edustaff_db`)

### Entity-Relationship (ER) Structure
1. **`users`**: System authentication for admin/accountant users.
2. **`teachers`**: Master table for faculty profile and base financial data.
3. **`attendance`**: Daily log linking teachers to attendance status (`teacher_id` foreign key).
4. **`salary`**: Monthly calculated payroll entries (`teacher_id` foreign key).

### SQL Schema Script
```sql
CREATE DATABASE IF NOT EXISTS edustaff_db;
USE edustaff_db;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'accountant') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Teachers Table
CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    joining_date DATE NOT NULL,
    base_salary DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Attendance Table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'leave') NOT NULL,
    remarks VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_date (teacher_id, attendance_date)
);

-- 4. Salary Table
CREATE TABLE IF NOT EXISTS salary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    salary_month DATE NOT NULL, -- Stored as YYYY-MM-01
    working_days INT NOT NULL DEFAULT 0,
    present_days INT NOT NULL DEFAULT 0,
    absent_days INT NOT NULL DEFAULT 0,
    late_days INT NOT NULL DEFAULT 0,
    leave_days INT NOT NULL DEFAULT 0,
    base_salary DECIMAL(10,2) NOT NULL,
    allowance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    deduction DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    net_salary DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid') DEFAULT 'pending',
    paid_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_month (teacher_id, salary_month)
);

-- Default Admin Credentials (Username: admin | Password: admin123)
INSERT INTO users (username, password_hash, role) 
VALUES ('admin', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1T9.q2Q4l9QpA.1N/6nB7E4A1c5sXm6', 'admin')
ON DUPLICATE KEY UPDATE id=id;
```

---

## 4. Directory & File Organization

```text
htdocs/edustaff/
├── config.php        # Database connection & global functions
├── header.php        # Global navigation header
├── footer.php        # Global page footer
├── login.php         # Authentication page
├── logout.php        # Session termination
├── index.php         # Admin dashboard with key metrics
├── teachers.php      # Teacher management CRUD
├── attendance.php    # Batch attendance marking tool
├── salary.php        # Payroll calculation engine
├── payslip.php       # Printable salary invoice
└── assets/
    ├── css/
    │   └── style.css # Application stylesheet
    └── js/
        └── main.js   # Client-side validation / interactions
```

---

## 5. Technical Highlights for Project Defense / Viva Voce

1. **Prepared Statements & Security:** Uses MySQLi prepared statements (`prepare()`, `bind_param()`) to prevent SQL Injection attacks.
2. **Password Hashing:** Uses PHP's `password_hash()` (Bcrypt) and `password_verify()` for secure credentials storage.
3. **Database Integrity & UPSERTs:** Uses `ON DUPLICATE KEY UPDATE` to allow updating existing records without primary key or unique key collisions.
4. **Relational Constraints:** Cascading deletes (`ON DELETE CASCADE`) maintain relational integrity between `teachers`, `attendance`, and `salary` tables.
5. **Dynamic Deduction Rule:** Demonstrates business logic application directly linked to daily operational data.