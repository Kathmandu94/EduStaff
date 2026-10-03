<?php
require_once 'config.php';
checkLogin();

// ─── AUTO-SEED 3 SAMPLE TEACHERS (runs only if they don't already exist) ───
$seed_teachers = [
    [
        'code'         => 'TCH-001',
        'name'         => 'Renu Raj Bhandari',
        'email'        => 'renu.bhandari@edustaff.edu.np',
        'phone'        => '9841100001',
        'subject'      => 'Mathematics',
        'joining_date' => '2022-01-15',
        'base_salary'  => 35000.00,
        'status'       => 'active',
    ],
    [
        'code'         => 'TCH-002',
        'name'         => 'Sohil Shrestha',
        'email'        => 'sohil.shrestha@edustaff.edu.np',
        'phone'        => '9841100002',
        'subject'      => 'English',
        'joining_date' => '2022-03-10',
        'base_salary'  => 25000.00,
        'status'       => 'active',
    ],
    [
        'code'         => 'TCH-003',
        'name'         => 'Arjan Maharjan',
        'email'        => 'arjan.maharjan@edustaff.edu.np',
        'phone'        => '9841100003',
        'subject'      => 'Science',
        'joining_date' => '2023-06-01',
        'base_salary'  => 18000.00,
        'status'       => 'active',
    ],
];

$seed_stmt = $conn->prepare(
    "INSERT IGNORE INTO teachers
        (teacher_code, name, email, phone, subject, joining_date, base_salary, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);

foreach ($seed_teachers as $t) {
    $seed_stmt->bind_param(
        "ssssssds",
        $t['code'], $t['name'], $t['email'], $t['phone'],
        $t['subject'], $t['joining_date'], $t['base_salary'], $t['status']
    );
    $seed_stmt->execute();
}
$seed_stmt->close();

// ─── SEED ATTENDANCE FOR THE PAST 26 WORKING DAYS ───────────────────────────
// Renu  → 75% present  (20/26 present, 6 absent)
// Sohil → 90% present  (23/26 present, 3 absent)
// Arjan → 97% present  (25/26 present, 1 absent)
$attendance_targets = [
    'TCH-001' => ['present' => 20, 'absent' => 6],
    'TCH-002' => ['present' => 23, 'absent' => 3],
    'TCH-003' => ['present' => 25, 'absent' => 1],
];

// Get teacher IDs by code
$id_map = [];
foreach ($seed_teachers as $t) {
    $r = $conn->query("SELECT id FROM teachers WHERE teacher_code = '{$t['code']}' LIMIT 1");
    if ($row = $r->fetch_assoc()) { $id_map[$t['code']] = $row['id']; }
}

// Build 26 past weekdays
$working_days = [];
$day = new DateTime();
$day->modify('-1 day');
while (count($working_days) < 26) {
    $dow = (int)$day->format('N');
    if ($dow <= 5) { $working_days[] = $day->format('Y-m-d'); }
    $day->modify('-1 day');
}

$att_ins = $conn->prepare(
    "INSERT IGNORE INTO attendance (teacher_id, attendance_date, status, remarks)
     VALUES (?, ?, ?, '')"
);

foreach ($attendance_targets as $code => $tgt) {
    if (!isset($id_map[$code])) continue;
    $tid = $id_map[$code];

    // Build status list: fill presents then absents
    $statuses = array_merge(
        array_fill(0, $tgt['present'], 'present'),
        array_fill(0, $tgt['absent'], 'absent')
    );

    foreach ($working_days as $i => $wd) {
        $status = $statuses[$i] ?? 'present';
        $att_ins->bind_param("iss", $tid, $wd, $status);
        $att_ins->execute();
    }
}
$att_ins->close();

// ─── HANDLE ADD / EDIT / DELETE ─────────────────────────────────────────────
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // DELETE
    if (isset($_POST['delete_id'])) {
        $del_id = (int)$_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $stmt->close();
        header("Location: teachers.php?msg=Teacher+deleted+successfully.&type=success");
        exit;
    }

    // ADD / EDIT
    $name         = trim($_POST['name'] ?? '');
    $code         = trim($_POST['teacher_code'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $subject      = trim($_POST['subject'] ?? '');
    $joining_date = $_POST['joining_date'] ?? '';
    $base_salary  = (float)($_POST['base_salary'] ?? 0);
    $status       = $_POST['status'] ?? 'active';
    $edit_id      = (int)($_POST['edit_id'] ?? 0);

    if ($edit_id > 0) {
        $stmt = $conn->prepare(
            "UPDATE teachers SET teacher_code=?, name=?, email=?, phone=?, subject=?, joining_date=?, base_salary=?, status=? WHERE id=?"
        );
        $stmt->bind_param("ssssssdsi", $code, $name, $email, $phone, $subject, $joining_date, $base_salary, $status, $edit_id);
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO teachers (teacher_code, name, email, phone, subject, joining_date, base_salary, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssssds", $code, $name, $email, $phone, $subject, $joining_date, $base_salary, $status);
    }

    if ($stmt->execute()) {
        $msg = $edit_id > 0 ? 'Teacher updated successfully.' : 'Teacher added successfully.';
        header("Location: teachers.php?msg=" . urlencode($msg) . "&type=success");
    } else {
        $message = "Error: " . $stmt->error;
        $messageType = "danger";
    }
    $stmt->close();
    if (!$message) exit;
}

if (isset($_GET['msg'])) {
    $message     = $_GET['msg'];
    $messageType = $_GET['type'] ?? 'info';
}

// ─── FETCH TEACHERS ─────────────────────────────────────────────────────────
$teachers = $conn->query(
    "SELECT t.*,
        (SELECT COUNT(*) FROM attendance a WHERE a.teacher_id = t.id) as total_days,
        (SELECT COUNT(*) FROM attendance a WHERE a.teacher_id = t.id AND a.status = 'present') as present_days
     FROM teachers t ORDER BY t.name ASC"
);

// ─── FETCH SINGLE TEACHER FOR EDIT ──────────────────────────────────────────
$edit_teacher = null;
if (isset($_GET['edit'])) {
    $eid  = (int)$_GET['edit'];
    $estmt = $conn->prepare("SELECT * FROM teachers WHERE id = ?");
    $estmt->bind_param("i", $eid);
    $estmt->execute();
    $edit_teacher = $estmt->get_result()->fetch_assoc();
    $estmt->close();
}

include 'header.php';
?>

<div class="dashboard-header" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1 style="color: var(--text-main); font-size: 1.75rem; font-weight: 700;">Teachers</h1>
        <p style="color: var(--text-muted); margin-top: 0.25rem;">Manage your institution's teaching staff.</p>
    </div>
    <button class="btn btn-primary" onclick="toggleForm()" id="toggleFormBtn">
        <i class="fa-solid fa-user-plus"></i> Add Teacher
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>" style="margin-bottom: 1.5rem;">
        <i class="fa-solid fa-<?php echo $messageType === 'success' ? 'check-circle' : 'circle-exclamation'; ?>"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Add / Edit Teacher Form -->
<div class="card" id="teacherForm" style="display: <?php echo $edit_teacher ? 'block' : 'none'; ?>; margin-bottom: 1.5rem;">
    <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1.5rem;">
        <i class="fa-solid fa-<?php echo $edit_teacher ? 'pen-to-square' : 'user-plus'; ?>"></i>
        <?php echo $edit_teacher ? 'Edit Teacher' : 'Add New Teacher'; ?>
    </h3>
    <form method="POST">
        <?php if ($edit_teacher): ?>
            <input type="hidden" name="edit_id" value="<?php echo $edit_teacher['id']; ?>">
        <?php endif; ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Teacher Code</label>
                <input type="text" name="teacher_code" class="form-control" value="<?php echo htmlspecialchars($edit_teacher['teacher_code'] ?? ''); ?>" placeholder="e.g. TCH-004" required>
            </div>
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($edit_teacher['name'] ?? ''); ?>" placeholder="Full name" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($edit_teacher['email'] ?? ''); ?>" placeholder="Email address" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($edit_teacher['phone'] ?? ''); ?>" placeholder="Phone number" required>
            </div>
            <div class="form-group">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($edit_teacher['subject'] ?? ''); ?>" placeholder="e.g. Mathematics" required>
            </div>
            <div class="form-group">
                <label class="form-label">Joining Date</label>
                <input type="date" name="joining_date" class="form-control" value="<?php echo htmlspecialchars($edit_teacher['joining_date'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Base Salary (NPR)</label>
                <input type="number" name="base_salary" class="form-control" value="<?php echo htmlspecialchars($edit_teacher['base_salary'] ?? ''); ?>" placeholder="e.g. 35000" step="100" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="active"   <?php echo ($edit_teacher['status'] ?? '') === 'active'   ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($edit_teacher['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
        </div>
        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> <?php echo $edit_teacher ? 'Update Teacher' : 'Save Teacher'; ?>
            </button>
            <a href="teachers.php" class="btn" style="background: var(--bg-color); border: 1px solid var(--border-color); color: var(--text-main);">Cancel</a>
        </div>
    </form>
</div>

<!-- Teachers Table -->
<div class="card">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 0.75rem; color: var(--text-muted); font-weight: 500;">Teacher</th>
                    <th style="padding: 0.75rem; color: var(--text-muted); font-weight: 500;">Contact</th>
                    <th style="padding: 0.75rem; color: var(--text-muted); font-weight: 500;">Subject</th>
                    <th style="padding: 0.75rem; color: var(--text-muted); font-weight: 500;">Base Salary</th>
                    <th style="padding: 0.75rem; color: var(--text-muted); font-weight: 500;">Attendance</th>
                    <th style="padding: 0.75rem; color: var(--text-muted); font-weight: 500;">Status</th>
                    <th style="padding: 0.75rem; color: var(--text-muted); font-weight: 500;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($teachers->num_rows > 0): while ($t = $teachers->fetch_assoc()):
                $att_pct = $t['total_days'] > 0 ? round(($t['present_days'] / $t['total_days']) * 100) : 0;
                $bar_color = $att_pct >= 90 ? '#10B981' : ($att_pct >= 75 ? '#F59E0B' : '#EF4444');
                $initials  = implode('', array_map(fn($w) => strtoupper($w[0]), explode(' ', $t['name'])));
                $initials  = substr($initials, 0, 2);
                $hue_map   = ['RR' => '240', 'SS' => '160', 'AM' => '30'];
                $hue       = $hue_map[$initials] ?? '260';
            ?>
                <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                    <td style="padding: 1rem 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: hsl(<?php echo $hue; ?>, 70%, 90%); color: hsl(<?php echo $hue; ?>, 60%, 35%); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.875rem; flex-shrink: 0;">
                                <?php echo $initials; ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($t['name']); ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($t['teacher_code']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 1rem 0.75rem;">
                        <div style="color: var(--text-main); font-size: 0.875rem;"><?php echo htmlspecialchars($t['email']); ?></div>
                        <div style="color: var(--text-muted); font-size: 0.8rem;"><?php echo htmlspecialchars($t['phone']); ?></div>
                    </td>
                    <td style="padding: 1rem 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($t['subject']); ?></td>
                    <td style="padding: 1rem 0.75rem; font-weight: 600; color: var(--text-main);">
                        रू <?php echo number_format($t['base_salary'], 0); ?>
                        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 400;">NPR / month</div>
                    </td>
                    <td style="padding: 1rem 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="flex: 1; background: var(--border-color); border-radius: 9999px; height: 6px; min-width: 80px;">
                                <div style="width: <?php echo $att_pct; ?>%; background: <?php echo $bar_color; ?>; height: 6px; border-radius: 9999px; transition: width 0.4s ease;"></div>
                            </div>
                            <span style="font-size: 0.8rem; font-weight: 600; color: <?php echo $bar_color; ?>; min-width: 36px;"><?php echo $att_pct; ?>%</span>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;"><?php echo $t['present_days']; ?>/<?php echo $t['total_days']; ?> days</div>
                    </td>
                    <td style="padding: 1rem 0.75rem;">
                        <?php if ($t['status'] === 'active'): ?>
                            <span style="background: #D1FAE5; color: #065F46; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Active</span>
                        <?php else: ?>
                            <span style="background: #F3F4F6; color: #6B7280; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 1rem 0.75rem;">
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="teachers.php?edit=<?php echo $t['id']; ?>" style="padding: 0.4rem 0.75rem; background: rgba(79,70,229,0.1); color: var(--primary-color); border-radius: 6px; font-size: 0.8rem; font-weight: 500; display: flex; align-items: center; gap: 0.35rem; text-decoration: none;">
                                <i class="fa-solid fa-pen"></i> Edit
                            </a>
                            <form method="POST" onsubmit="return confirm('Delete this teacher?');" style="display: inline;">
                                <input type="hidden" name="delete_id" value="<?php echo $t['id']; ?>">
                                <button type="submit" style="padding: 0.4rem 0.75rem; background: rgba(239,68,68,0.1); color: #EF4444; border-radius: 6px; font-size: 0.8rem; font-weight: 500; display: flex; align-items: center; gap: 0.35rem; border: none; cursor: pointer;">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr>
                    <td colspan="7" style="padding: 3rem; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-users-slash" style="font-size: 2.5rem; display: block; margin-bottom: 1rem;"></i>
                        No teachers found. Click "Add Teacher" to get started.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function toggleForm() {
        const form = document.getElementById('teacherForm');
        const btn  = document.getElementById('toggleFormBtn');
        const open = form.style.display === 'none' || form.style.display === '';
        form.style.display = open ? 'block' : 'none';
        btn.innerHTML = open
            ? '<i class="fa-solid fa-xmark"></i> Cancel'
            : '<i class="fa-solid fa-user-plus"></i> Add Teacher';
    }
</script>

<?php include 'footer.php'; ?>
