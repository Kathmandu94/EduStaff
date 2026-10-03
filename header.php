<?php 
// Ensure config is loaded if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Get current page for active nav state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduStaff - Teacher Management System</title>
    <!-- Modern Font: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fa-solid fa-graduation-cap"></i>
                <h2>EduStaff</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
                <a href="teachers.php" class="nav-item <?php echo $current_page == 'teachers.php' ? 'active' : ''; ?>"><i class="fa-solid fa-chalkboard-user"></i> Teachers</a>
                <a href="attendance.php" class="nav-item <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>"><i class="fa-solid fa-calendar-check"></i> Attendance</a>
                <a href="salary.php" class="nav-item <?php echo $current_page == 'salary.php' ? 'active' : ''; ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Payroll</a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-item text-danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </div>
        </aside>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <header class="topbar">
                <div class="user-info">
                    <span class="greeting">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                    <div class="avatar">
                        <i class="fa-solid fa-user-circle"></i>
                    </div>
                </div>
            </header>
            <div class="content-wrapper">
    <?php else: ?>
        <div class="auth-container">
    <?php endif; ?>
