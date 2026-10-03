<?php
require_once 'config.php';
checkLogin();

// ─── FETCH ALL ACTIVE TEACHERS WITH PAYROLL DATA ─────────────────────────────
$working_days_in_month = 26;
$current_month_label   = date('F Y');

$query = "
    SELECT
        t.id,
        t.teacher_code,
        t.name,
        t.subject,
        t.base_salary,
        t.status,
        COUNT(a.id)                                         AS total_att_days,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_days,
        SUM(CASE WHEN a.status = 'absent'  THEN 1 ELSE 0 END) AS absent_days,
        SUM(CASE WHEN a.status = 'late'    THEN 1 ELSE 0 END) AS late_days,
        SUM(CASE WHEN a.status = 'leave'   THEN 1 ELSE 0 END) AS leave_days
    FROM teachers t
    LEFT JOIN attendance a ON a.teacher_id = t.id
    WHERE t.status = 'active'
    GROUP BY t.id
    ORDER BY t.name ASC
";

$result      = $conn->query($query);
$teachers    = [];
$grand_total = 0;

while ($row = $result->fetch_assoc()) {
    $present        = (int)$row['present_days'];
    $total_att      = (int)$row['total_att_days'];
    $base           = (float)$row['base_salary'];
    $att_pct        = $total_att > 0 ? round(($present / $total_att) * 100, 1) : 0;

    // Net salary = (base / working_days) × present_days
    $daily_rate     = $base / $working_days_in_month;
    $net_salary     = round($daily_rate * $present, 2);
    $deduction      = round($base - $net_salary, 2);

    $row['att_pct']   = $att_pct;
    $row['net_salary'] = $net_salary;
    $row['deduction']  = $deduction;
    $row['daily_rate'] = round($daily_rate, 2);

    $grand_total += $net_salary;
    $teachers[]   = $row;
}

include 'header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="color: var(--text-main); font-size: 1.75rem; font-weight: 700;">Payroll</h1>
        <p style="color: var(--text-muted); margin-top: 0.25rem;">
            Monthly salary report &mdash; <strong><?php echo $current_month_label; ?></strong>
        </p>
    </div>
    <button onclick="window.print()" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-print"></i> Print Payroll
    </button>
</div>

<!-- Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">

    <div class="card metric-card" style="border-left: 4px solid var(--primary-color); display: flex; align-items: center; gap: 1rem;">
        <div style="background: rgba(79,70,229,0.1); color: var(--primary-color); padding: 0.875rem; border-radius: 50%; font-size: 1.25rem;">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 500;">Total Staff</div>
            <div style="font-size: 1.5rem; font-weight: 700;"><?php echo count($teachers); ?></div>
        </div>
    </div>

    <div class="card metric-card" style="border-left: 4px solid #10B981; display: flex; align-items: center; gap: 1rem;">
        <div style="background: rgba(16,185,129,0.1); color: #10B981; padding: 0.875rem; border-radius: 50%; font-size: 1.25rem;">
            <i class="fa-solid fa-money-bill-wave"></i>
        </div>
        <div>
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 500;">Total Payable</div>
            <div style="font-size: 1.5rem; font-weight: 700;">रू <?php echo number_format($grand_total, 0); ?></div>
        </div>
    </div>

    <div class="card metric-card" style="border-left: 4px solid var(--warning-color); display: flex; align-items: center; gap: 1rem;">
        <div style="background: rgba(245,158,11,0.1); color: var(--warning-color); padding: 0.875rem; border-radius: 50%; font-size: 1.25rem;">
            <i class="fa-solid fa-calendar-days"></i>
        </div>
        <div>
            <div style="color: var(--text-muted); font-size: 0.8rem; font-weight: 500;">Working Days</div>
            <div style="font-size: 1.5rem; font-weight: 700;"><?php echo $working_days_in_month; ?></div>
        </div>
    </div>

</div>

<!-- Payroll Table -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.125rem; font-weight: 600;">
            <i class="fa-solid fa-table-list"></i> Payroll Breakdown
        </h3>
        <span style="background: rgba(79,70,229,0.1); color: var(--primary-color); padding: 0.35rem 0.85rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 600;">
            <?php echo date('F Y'); ?>
        </span>
    </div>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 0.75rem; color: var(--text-muted); font-weight: 500; font-size: 0.875rem;">Teacher</th>
                    <th style="padding: 0.75rem; color: var(--text-muted); font-weight: 500; font-size: 0.875rem; text-align: center;">Attendance</th>
                    <th style="padding: 0.75rem; color: var(--text-muted); font-weight: 500; font-size: 0.875rem; text-align: center;">Present / Absent</th>
                    <th style="padding: 0.75rem; color: var(--text-muted); font-weight: 500; font-size: 0.875rem; text-align: right;">Base Salary</th>
                    <th style="padding: 0.75rem; color: var(--text-muted); font-weight: 500; font-size: 0.875rem; text-align: right;">Deduction</th>
                    <th style="padding: 0.75rem; color: var(--text-muted); font-weight: 500; font-size: 0.875rem; text-align: right;">Net Payable</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($teachers) > 0): foreach ($teachers as $t):
                $att_color  = $t['att_pct'] >= 90 ? '#10B981' : ($t['att_pct'] >= 75 ? '#F59E0B' : '#EF4444');
                $initials   = implode('', array_map(fn($w) => strtoupper($w[0]), explode(' ', $t['name'])));
                $initials   = substr($initials, 0, 2);
                $hue_map    = ['RR' => '240', 'SS' => '160', 'AM' => '30'];
                $hue        = $hue_map[$initials] ?? '260';
            ?>
                <tr style="border-bottom: 1px solid var(--border-color);" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">

                    <!-- Teacher Info -->
                    <td style="padding: 1rem 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 42px; height: 42px; border-radius: 50%; background: hsl(<?php echo $hue; ?>, 70%, 90%); color: hsl(<?php echo $hue; ?>, 60%, 35%); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.875rem; flex-shrink: 0;">
                                <?php echo $initials; ?>
                            </div>
                            <div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($t['name']); ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($t['subject']); ?></div>
                            </div>
                        </div>
                    </td>

                    <!-- Attendance Bar -->
                    <td style="padding: 1rem 0.75rem; text-align: center; min-width: 140px;">
                        <div style="font-weight: 700; font-size: 1.1rem; color: <?php echo $att_color; ?>;"><?php echo $t['att_pct']; ?>%</div>
                        <div style="background: var(--border-color); border-radius: 9999px; height: 6px; margin-top: 4px;">
                            <div style="width: <?php echo $t['att_pct']; ?>%; background: <?php echo $att_color; ?>; height: 6px; border-radius: 9999px;"></div>
                        </div>
                    </td>

                    <!-- Present / Absent -->
                    <td style="padding: 1rem 0.75rem; text-align: center;">
                        <span style="background: #D1FAE5; color: #065F46; padding: 0.2rem 0.6rem; border-radius: 5px; font-size: 0.8rem; font-weight: 600;">
                            ✓ <?php echo $t['present_days']; ?> present
                        </span>
                        &nbsp;
                        <span style="background: #FEE2E2; color: #991B1B; padding: 0.2rem 0.6rem; border-radius: 5px; font-size: 0.8rem; font-weight: 600;">
                            ✗ <?php echo $t['absent_days']; ?> absent
                        </span>
                    </td>

                    <!-- Base Salary -->
                    <td style="padding: 1rem 0.75rem; text-align: right; font-weight: 500;">
                        रू <?php echo number_format($t['base_salary'], 0); ?>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">रू <?php echo number_format($t['daily_rate'], 0); ?>/day</div>
                    </td>

                    <!-- Deduction -->
                    <td style="padding: 1rem 0.75rem; text-align: right; color: #EF4444; font-weight: 500;">
                        <?php echo $t['deduction'] > 0 ? '- रू ' . number_format($t['deduction'], 0) : '—'; ?>
                    </td>

                    <!-- Net Payable -->
                    <td style="padding: 1rem 0.75rem; text-align: right;">
                        <span style="font-size: 1.125rem; font-weight: 700; color: #10B981;">
                            रू <?php echo number_format($t['net_salary'], 0); ?>
                        </span>
                    </td>

                </tr>
            <?php endforeach; else: ?>
                <tr>
                    <td colspan="6" style="padding: 3rem; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-file-slash" style="font-size: 2.5rem; display: block; margin-bottom: 1rem;"></i>
                        No active teachers with attendance data found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
            <!-- Totals Footer -->
            <tfoot>
                <tr style="border-top: 2px solid var(--border-color); background: #f9fafb;">
                    <td colspan="5" style="padding: 1rem 0.75rem; font-weight: 700; text-align: right; color: var(--text-muted);">Grand Total Payable:</td>
                    <td style="padding: 1rem 0.75rem; text-align: right; font-size: 1.25rem; font-weight: 800; color: var(--primary-color);">
                        रू <?php echo number_format($grand_total, 0); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<style>
    .metric-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .metric-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }

    @media print {
        .sidebar, .topbar, button, .btn { display: none !important; }
        .main-content { margin-left: 0 !important; }
        .card { box-shadow: none; border: 1px solid #e5e7eb; }
    }
</style>

<?php include 'footer.php'; ?>
