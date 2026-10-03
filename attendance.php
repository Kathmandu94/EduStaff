<?php
require_once 'config.php';
checkLogin();

$message = '';
$messageType = '';

// Default date to today, or get from URL
$date = $_GET['date'] ?? date('Y-m-d');

// Process Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_attendance'])) {
    $attendance_date = $_POST['attendance_date'] ?? $date;
    $records = $_POST['attendance'] ?? [];
    
    $success_count = 0;
    
    // Prepare statement for Insert/Update
    $stmt = $conn->prepare("INSERT INTO attendance (teacher_id, attendance_date, status, remarks) 
                            VALUES (?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE status=VALUES(status), remarks=VALUES(remarks)");
    
    foreach ($records as $teacher_id => $data) {
        if (!empty($data['status'])) {
            $status = $data['status'];
            $remarks = $data['remarks'] ?? '';
            $stmt->bind_param("isss", $teacher_id, $attendance_date, $status, $remarks);
            if ($stmt->execute()) {
                $success_count++;
            }
        }
    }
    
    if ($success_count > 0) {
        $message = "Attendance saved successfully for $success_count teachers.";
        $messageType = "success";
    } else {
        $message = "No attendance records were updated.";
        $messageType = "warning";
    }
    
    // Redirect to prevent form resubmission
    header("Location: attendance.php?date=$attendance_date&msg=" . urlencode($message) . "&type=$messageType");
    exit;
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $messageType = $_GET['type'] ?? 'info';
}

// Fetch all active teachers
$teachers_query = "SELECT id, name, teacher_code, subject FROM teachers WHERE status = 'active' ORDER BY name ASC";
$teachers_result = $conn->query($teachers_query);

// Fetch existing attendance for the selected date
$existing_attendance = [];
$att_query = $conn->prepare("SELECT teacher_id, status, remarks FROM attendance WHERE attendance_date = ?");
$att_query->bind_param("s", $date);
$att_query->execute();
$att_result = $att_query->get_result();
while ($row = $att_result->fetch_assoc()) {
    $existing_attendance[$row['teacher_id']] = $row;
}

include 'header.php';
?>

<div class="dashboard-header" style="margin-bottom: 2rem;">
    <h1 style="color: var(--text-main); font-size: 1.75rem; font-weight: 700;">Attendance Management</h1>
    <p style="color: var(--text-muted);">Mark and view daily attendance for your teachers.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>" style="margin-bottom: 1.5rem;">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="card" style="margin-bottom: 2rem;">
    <form method="GET" action="attendance.php" style="display: flex; gap: 1rem; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0; flex: 1; max-width: 300px;">
            <label class="form-label" for="date">Select Date</label>
            <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>" required max="<?php echo date('Y-m-d'); ?>">
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-filter"></i> Load Attendance
        </button>
    </form>
</div>

<div class="card">
    <form method="POST" action="attendance.php">
        <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($date); ?>">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.125rem; font-weight: 600;">Attendance for <?php echo date('F d, Y', strtotime($date)); ?></h3>
            <div>
                <button type="button" class="btn btn-outline" onclick="markAll('present')" style="border-color: #10B981; color: #10B981; margin-right: 0.5rem; background: transparent; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer;">Mark All Present</button>
                <button type="submit" name="save_attendance" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Save Attendance
                </button>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted);">
                        <th style="padding: 0.75rem; font-weight: 500;">Teacher Info</th>
                        <th style="padding: 0.75rem; font-weight: 500;">Status</th>
                        <th style="padding: 0.75rem; font-weight: 500;">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($teachers_result->num_rows > 0): ?>
                        <?php while ($teacher = $teachers_result->fetch_assoc()): 
                            $t_id = $teacher['id'];
                            $current_status = $existing_attendance[$t_id]['status'] ?? '';
                            $current_remarks = $existing_attendance[$t_id]['remarks'] ?? '';
                        ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 1rem 0.75rem;">
                                    <div style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($teacher['name']); ?></div>
                                    <div style="font-size: 0.875rem; color: var(--text-muted);"><?php echo htmlspecialchars($teacher['teacher_code']); ?> &bull; <?php echo htmlspecialchars($teacher['subject']); ?></div>
                                </td>
                                <td style="padding: 1rem 0.75rem;">
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <label class="status-radio present">
                                            <input type="radio" name="attendance[<?php echo $t_id; ?>][status]" value="present" <?php echo $current_status === 'present' ? 'checked' : ''; ?> required>
                                            <span>Present</span>
                                        </label>
                                        <label class="status-radio absent">
                                            <input type="radio" name="attendance[<?php echo $t_id; ?>][status]" value="absent" <?php echo $current_status === 'absent' ? 'checked' : ''; ?>>
                                            <span>Absent</span>
                                        </label>
                                        <label class="status-radio late">
                                            <input type="radio" name="attendance[<?php echo $t_id; ?>][status]" value="late" <?php echo $current_status === 'late' ? 'checked' : ''; ?>>
                                            <span>Late</span>
                                        </label>
                                        <label class="status-radio leave">
                                            <input type="radio" name="attendance[<?php echo $t_id; ?>][status]" value="leave" <?php echo $current_status === 'leave' ? 'checked' : ''; ?>>
                                            <span>Leave</span>
                                        </label>
                                    </div>
                                </td>
                                <td style="padding: 1rem 0.75rem;">
                                    <input type="text" name="attendance[<?php echo $t_id; ?>][remarks]" class="form-control" value="<?php echo htmlspecialchars($current_remarks); ?>" placeholder="Optional notes" style="padding: 0.5rem;">
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="padding: 2rem; text-align: center; color: var(--text-muted);">
                                No active teachers found. <a href="teachers.php" style="color: var(--primary-color);">Add a teacher</a> first.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($teachers_result->num_rows > 0): ?>
            <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
                <button type="submit" name="save_attendance" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-size: 1rem;">
                    <i class="fa-solid fa-save"></i> Save All Attendance
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<style>
    .status-radio input[type="radio"] {
        display: none;
    }
    .status-radio span {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 6px;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        border: 1px solid var(--border-color);
        transition: all 0.2s ease;
        background: var(--bg-color);
        color: var(--text-muted);
    }
    
    .status-radio.present input:checked + span {
        background: rgba(16, 185, 129, 0.1);
        border-color: #10B981;
        color: #065F46;
    }
    .status-radio.absent input:checked + span {
        background: rgba(239, 68, 68, 0.1);
        border-color: #EF4444;
        color: #991B1B;
    }
    .status-radio.late input:checked + span {
        background: rgba(245, 158, 11, 0.1);
        border-color: #F59E0B;
        color: #92400E;
    }
    .status-radio.leave input:checked + span {
        background: rgba(107, 114, 128, 0.1);
        border-color: #6B7280;
        color: #374151;
    }
    
    .status-radio:hover span {
        background: var(--hover-color);
    }
</style>

<script>
    function markAll(status) {
        document.querySelectorAll('input[type="radio"][value="' + status + '"]').forEach(radio => {
            radio.checked = true;
        });
    }
</script>

<?php include 'footer.php'; ?>
