<?php
require_once 'config.php';
checkLogin(); // Ensure user is logged in

// Fetch real-time metrics (placeholders/zero if no data)
// 1. Total Active Teachers
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM teachers WHERE status = 'active'");
$stmt->execute();
$total_teachers = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// 2. Today's Attendance (Present / Absent / Late / Leave)
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM attendance WHERE attendance_date = ? GROUP BY status");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

$attendance_stats = ['present' => 0, 'absent' => 0, 'late' => 0, 'leave' => 0];
while ($row = $result->fetch_assoc()) {
    $attendance_stats[$row['status']] = $row['count'];
}
$stmt->close();

// 3. Monthly Payroll Commitment
$stmt = $conn->prepare("SELECT SUM(base_salary) as total_payroll FROM teachers WHERE status = 'active'");
$stmt->execute();
$total_payroll = $stmt->get_result()->fetch_assoc()['total_payroll'] ?? 0.00;
$stmt->close();

include 'header.php';
?>

<?php if (isset($_SESSION['login_success'])): ?>
<div id="authPopup" style="position: fixed; top: 20px; right: 20px; background: #10B981; color: white; padding: 1rem 1.5rem; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4); z-index: 9999; display: flex; align-items: center; gap: 1rem; font-weight: 500; animation: slideInRight 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards, fadeOut 0.5s ease-in 3.5s forwards;">
    <div style="background: rgba(255,255,255,0.2); padding: 0.5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
        <i class="fa-solid fa-check" style="font-size: 1.25rem;"></i>
    </div>
    <div>
        <div style="font-size: 1.1rem; font-weight: 600;">Authorized Successfully</div>
        <div style="font-size: 0.85rem; opacity: 0.9; margin-top: 0.2rem;">Welcome to your dashboard</div>
    </div>
</div>
<style>
    @keyframes slideInRight {
        0% { transform: translateX(120%); opacity: 0; }
        100% { transform: translateX(0); opacity: 1; }
    }
    @keyframes fadeOut {
        0% { opacity: 1; visibility: visible; transform: translateX(0); }
        100% { opacity: 0; visibility: hidden; transform: translateX(120%); }
    }
</style>
<?php 
    unset($_SESSION['login_success']);
endif; 
?>

<div class="dashboard-header" style="margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; gap: 1rem;">
        <h1 style="color: var(--text-main); font-size: 1.75rem; font-weight: 700;">Admin Dashboard</h1>
        <span style="background: rgba(16, 185, 129, 0.1); color: #10B981; padding: 0.35rem 0.85rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; border: 1px solid rgba(16,185,129,0.2);">
            <span style="width: 8px; height: 8px; background: #10B981; border-radius: 50%; display: inline-block; box-shadow: 0 0 0 2px rgba(16,185,129,0.2); animation: pulse 2s infinite;"></span>
            Logged In
        </span>
    </div>
    <p style="color: var(--text-muted); margin-top: 0.5rem;">Overview of your institution's staff and payroll.</p>
</div>
<style>
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
        70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
</style>

<!-- Metrics Cards -->
<div class="metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    
    <!-- Total Teachers Card -->
    <div class="card metric-card" style="display: flex; align-items: center; gap: 1.5rem; border-left: 4px solid var(--primary-color);">
        <div class="metric-icon" style="background: rgba(79, 70, 229, 0.1); color: var(--primary-color); padding: 1rem; border-radius: 50%; font-size: 1.5rem;">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <h3 style="color: var(--text-muted); font-size: 0.875rem; font-weight: 500;">Total Active Teachers</h3>
            <p style="color: var(--text-main); font-size: 1.5rem; font-weight: 700;"><?php echo number_format($total_teachers); ?></p>
        </div>
    </div>

    <!-- Present Today -->
    <div class="card metric-card" style="display: flex; align-items: center; gap: 1.5rem; border-left: 4px solid var(--secondary-color);">
        <div class="metric-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--secondary-color); padding: 1rem; border-radius: 50%; font-size: 1.5rem;">
            <i class="fa-solid fa-user-check"></i>
        </div>
        <div>
            <h3 style="color: var(--text-muted); font-size: 0.875rem; font-weight: 500;">Present Today</h3>
            <p style="color: var(--text-main); font-size: 1.5rem; font-weight: 700;"><?php echo number_format($attendance_stats['present']); ?></p>
        </div>
    </div>

    <!-- Absent Today -->
    <div class="card metric-card" style="display: flex; align-items: center; gap: 1.5rem; border-left: 4px solid var(--danger-color);">
        <div class="metric-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--danger-color); padding: 1rem; border-radius: 50%; font-size: 1.5rem;">
            <i class="fa-solid fa-user-xmark"></i>
        </div>
        <div>
            <h3 style="color: var(--text-muted); font-size: 0.875rem; font-weight: 500;">Absent Today</h3>
            <p style="color: var(--text-main); font-size: 1.5rem; font-weight: 700;"><?php echo number_format($attendance_stats['absent']); ?></p>
        </div>
    </div>

    <!-- Monthly Payroll -->
    <div class="card metric-card" style="display: flex; align-items: center; gap: 1.5rem; border-left: 4px solid var(--warning-color);">
        <div class="metric-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning-color); padding: 1rem; border-radius: 50%; font-size: 1.5rem;">
            <i class="fa-solid fa-wallet"></i>
        </div>
        <div>
            <h3 style="color: var(--text-muted); font-size: 0.875rem; font-weight: 500;">Est. Monthly Payroll</h3>
            <p style="color: var(--text-main); font-size: 1.5rem; font-weight: 700;">रू <?php echo number_format($total_payroll, 0); ?></p>
        </div>
    </div>
    
</div>

<!-- Recent Activity & Quick Actions -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <!-- Recent Attendance Log -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.125rem; font-weight: 600;">Recent Attendance (<?php echo date('M d, Y'); ?>)</h3>
            <a href="attendance.php" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">Mark Attendance</a>
        </div>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted);">
                        <th style="padding: 0.75rem; font-weight: 500;">Teacher Name</th>
                        <th style="padding: 0.75rem; font-weight: 500;">Subject</th>
                        <th style="padding: 0.75rem; font-weight: 500;">Status</th>
                        <th style="padding: 0.75rem; font-weight: 500;">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch recent attendance
                    $stmt = $conn->prepare("
                        SELECT t.name, t.subject, a.status, a.remarks 
                        FROM attendance a 
                        JOIN teachers t ON a.teacher_id = t.id 
                        WHERE a.attendance_date = ? 
                        ORDER BY a.created_at DESC 
                        LIMIT 5
                    ");
                    $stmt->bind_param("s", $today);
                    $stmt->execute();
                    $recent_att = $stmt->get_result();
                    
                    if ($recent_att->num_rows > 0):
                        while($row = $recent_att->fetch_assoc()):
                            $status_color = match($row['status']) {
                                'present' => 'color: #065F46; background: #D1FAE5;',
                                'absent' => 'color: #991B1B; background: #FEE2E2;',
                                'late' => 'color: #92400E; background: #FEF3C7;',
                                'leave' => 'color: #374151; background: #F3F4F6;',
                                default => ''
                            };
                    ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 1rem 0.75rem; font-weight: 500;"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td style="padding: 1rem 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td style="padding: 1rem 0.75rem;">
                                <span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; <?php echo $status_color; ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                            <td style="padding: 1rem 0.75rem; color: var(--text-muted); font-size: 0.875rem;">
                                <?php echo htmlspecialchars($row['remarks'] ?? '-'); ?>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="4" style="padding: 2rem; text-align: center; color: var(--text-muted);">
                                <i class="fa-solid fa-folder-open" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                                No attendance records found for today.
                            </td>
                        </tr>
                    <?php endif; $stmt->close(); ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card">
        <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1.5rem;">Quick Actions</h3>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <a href="teachers.php" class="btn" style="background: var(--bg-color); color: var(--text-main); border: 1px solid var(--border-color); text-align: left; display: flex; align-items: center; gap: 1rem; transition: var(--transition);">
                <i class="fa-solid fa-user-plus" style="color: var(--primary-color); font-size: 1.25rem; width: 24px; text-align: center;"></i> Add New Teacher
            </a>
            <a href="payroll.php" class="btn" style="background: var(--bg-color); color: var(--text-main); border: 1px solid var(--border-color); text-align: left; display: flex; align-items: center; gap: 1rem; transition: var(--transition);">
                <i class="fa-solid fa-file-invoice-dollar" style="color: var(--secondary-color); font-size: 1.25rem; width: 24px; text-align: center;"></i> View Payroll
            </a>
            <a href="attendance.php" class="btn" style="background: var(--bg-color); color: var(--text-main); border: 1px solid var(--border-color); text-align: left; display: flex; align-items: center; gap: 1rem; transition: var(--transition);">
                <i class="fa-solid fa-calendar-check" style="color: var(--text-muted); font-size: 1.25rem; width: 24px; text-align: center;"></i> Mark Attendance
            </a>
        </div>
    </div>
</div>

<style>
    .metric-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .metric-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }
    .btn:hover {
        background: rgba(79, 70, 229, 0.05) !important;
        border-color: var(--primary-color) !important;
    }
</style>

<?php include 'footer.php'; ?>
