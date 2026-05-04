<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'supervisor') {
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

/* ===============================
   PERIOD FILTER (ADDED 'all')
================================*/
$period = $_GET['period'] ?? 'all';   // default = all requests
switch ($period) {
    case 'daily':
        $date_condition = "DATE(requests.created_at) = CURDATE()";
        $rating_date_condition = "DATE(ratings.created_at) = CURDATE()";
        break;
    case 'weekly':
        $date_condition = "YEARWEEK(requests.created_at, 1) = YEARWEEK(CURDATE(), 1)";
        $rating_date_condition = "YEARWEEK(ratings.created_at, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'monthly':
        $date_condition = "MONTH(requests.created_at) = MONTH(CURDATE()) AND YEAR(requests.created_at) = YEAR(CURDATE())";
        $rating_date_condition = "MONTH(ratings.created_at) = MONTH(CURDATE()) AND YEAR(ratings.created_at) = YEAR(CURDATE())";
        break;
    case 'all':
    default:
        $date_condition = "1";                 // no date filter
        $rating_date_condition = "1";
        break;
}

/* ===============================
   STATUS FILTER
================================*/
$status = $_GET['status'] ?? 'all';
switch ($status) {
    case 'pending':
    case 'in_progress':
    case 'completed':
    case 'conflict':
        $status_condition = "requests.status = '$status'";
        break;
    case 'all':
    default:
        $status_condition = "1";
        break;
}

/* ===============================
   ISSUE TYPE FILTER
================================*/
$issue = $_GET['issue'] ?? 'all';
$valid_issues = ['all', 'plumbing', 'electrical', 'furniture'];
if (!in_array($issue, $valid_issues)) {
    $issue = 'all';
}

if ($issue == 'all') {
    $issue_condition = "1";
} else {
    switch ($issue) {
        case 'plumbing':   $issue_id = 1; break;
        case 'electrical': $issue_id = 2; break;
        case 'furniture':  $issue_id = 3; break;
    }
    $issue_condition = "requests.issue_type_id = $issue_id";
}

$detail_condition = "$date_condition AND $status_condition AND $issue_condition";

/* ===============================
   REPORT DATA
================================*/
// Bar chart data
$plumbing   = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE issue_type_id = 1 AND $date_condition AND $status_condition")->fetch_assoc()['total'];
$electrical = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE issue_type_id = 2 AND $date_condition AND $status_condition")->fetch_assoc()['total'];
$furniture  = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE issue_type_id = 3 AND $date_condition AND $status_condition")->fetch_assoc()['total'];

// Pie chart data (full period, no status filter)
$pie_pending   = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE status = 'pending'   AND $date_condition")->fetch_assoc()['total'];
$pie_progress  = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE status = 'in_progress' AND $date_condition")->fetch_assoc()['total'];
$pie_completed = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE status = 'completed' AND $date_condition")->fetch_assoc()['total'];

// Technician workload
$tech_workload = $conn->query("
    SELECT users.name, COUNT(requests.id) AS total_jobs
    FROM staff
    JOIN users ON staff.user_id = users.id
    LEFT JOIN requests ON staff.id = requests.assigned_staff AND $date_condition AND $status_condition AND $issue_condition
    GROUP BY staff.id
");

// Technician performance
$perf_sql = "
    SELECT 
        users.name,
        staff.specialization,
        COUNT(ratings.id) AS total_ratings,
        ROUND(AVG(ratings.rating), 2) AS avg_rating
    FROM staff
    JOIN users ON staff.user_id = users.id
    LEFT JOIN ratings ON staff.id = ratings.staff_id AND $rating_date_condition
    GROUP BY staff.id, users.name, staff.specialization
    ORDER BY avg_rating DESC
";
$perf_result = $conn->query($perf_sql);
$overall_sum = 0;
$overall_count = 0;

// Summary stats for selected issue type
$detail_total     = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE $detail_condition")->fetch_assoc()['total'];
$detail_pending   = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE requests.status = 'pending'    AND $detail_condition")->fetch_assoc()['total'];
$detail_progress  = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE requests.status = 'in_progress' AND $detail_condition")->fetch_assoc()['total'];
$detail_completed = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE requests.status = 'completed'  AND $detail_condition")->fetch_assoc()['total'];
$detail_conflict  = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE requests.status = 'conflict'   AND $detail_condition")->fetch_assoc()['total'];

// Detail request list
$detail_requests = $conn->query("
    SELECT requests.id, users.name AS student_name, requests.hostel, requests.room,
           requests.available_time, requests.status
    FROM requests
    JOIN users ON requests.student_id = users.id
    WHERE $detail_condition
    ORDER BY requests.created_at DESC
    LIMIT 50
");
?>

<?php include("../includes/header.php"); ?>
<?php include("../includes/sidebar.php"); ?>

<div class="container-fluid">

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-1 fw-bold text-white">
                <i class="bi bi-bar-chart text-warning"></i>
                Maintenance Reports
            </h3>
            <p class="mb-0 text-white">
                View maintenance analytics and system performance.
            </p>
        </div>
        <i class="bi bi-graph-up-arrow display-5 text-warning"></i>
    </div>
</div>

<!-- ===============================
     COMBINED FILTER BAR
================================ -->
<div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
    <div class="card-body py-3">
        <div class="row align-items-center g-3">
            <!-- Period filter -->
            <div class="col-md-4">
                <small class="text-muted fw-bold"><i class="bi bi-calendar3"></i> PERIOD</small>
                <div class="btn-group w-100 mt-1" role="group">
                    <a href="?period=all&status=<?php echo $status; ?>&issue=<?php echo $issue; ?>" 
                       class="btn btn-sm flex-fill <?php echo $period=='all' ? 'btn-warning' : 'btn-outline-secondary'; ?>">All</a>
                    <a href="?period=daily&status=<?php echo $status; ?>&issue=<?php echo $issue; ?>" 
                       class="btn btn-sm flex-fill <?php echo $period=='daily' ? 'btn-warning' : 'btn-outline-secondary'; ?>">Daily</a>
                    <a href="?period=weekly&status=<?php echo $status; ?>&issue=<?php echo $issue; ?>" 
                       class="btn btn-sm flex-fill <?php echo $period=='weekly' ? 'btn-warning' : 'btn-outline-secondary'; ?>">Weekly</a>
                    <a href="?period=monthly&status=<?php echo $status; ?>&issue=<?php echo $issue; ?>" 
                       class="btn btn-sm flex-fill <?php echo $period=='monthly' ? 'btn-warning' : 'btn-outline-secondary'; ?>">Monthly</a>
                </div>
            </div>
            <!-- Status filter -->
            <div class="col-md-4">
                <small class="text-muted fw-bold"><i class="bi bi-funnel-fill"></i> STATUS</small>
                <div class="btn-group w-100 mt-1" role="group">
                    <a href="?period=<?php echo $period; ?>&status=all&issue=<?php echo $issue; ?>" 
                       class="btn btn-sm flex-fill <?php echo $status=='all' ? 'btn-warning' : 'btn-outline-secondary'; ?>">All</a>
                    <a href="?period=<?php echo $period; ?>&status=pending&issue=<?php echo $issue; ?>" 
                       class="btn btn-sm flex-fill <?php echo $status=='pending' ? 'btn-warning' : 'btn-outline-secondary'; ?>">Pending</a>
                    <a href="?period=<?php echo $period; ?>&status=in_progress&issue=<?php echo $issue; ?>" 
                       class="btn btn-sm flex-fill <?php echo $status=='in_progress' ? 'btn-warning' : 'btn-outline-secondary'; ?>">In Progress</a>
                    <a href="?period=<?php echo $period; ?>&status=completed&issue=<?php echo $issue; ?>" 
                       class="btn btn-sm flex-fill <?php echo $status=='completed' ? 'btn-warning' : 'btn-outline-secondary'; ?>">Completed</a>
                    <a href="?period=<?php echo $period; ?>&status=conflict&issue=<?php echo $issue; ?>" 
                       class="btn btn-sm flex-fill <?php echo $status=='conflict' ? 'btn-warning' : 'btn-outline-secondary'; ?>">Conflict</a>
                </div>
            </div>
            <!-- Issue type filter -->
            <div class="col-md-4">
                <small class="text-muted fw-bold"><i class="bi bi-tools"></i> ISSUE TYPE</small>
                <div class="btn-group w-100 mt-1" role="group">
                    <a href="?period=<?php echo $period; ?>&status=<?php echo $status; ?>&issue=all" 
                       class="btn btn-sm flex-fill <?php echo $issue=='all' ? 'btn-warning' : 'btn-outline-secondary'; ?>">All</a>
                    <a href="?period=<?php echo $period; ?>&status=<?php echo $status; ?>&issue=plumbing" 
                       class="btn btn-sm flex-fill <?php echo $issue=='plumbing' ? 'btn-warning' : 'btn-outline-secondary'; ?>">Plumbing</a>
                    <a href="?period=<?php echo $period; ?>&status=<?php echo $status; ?>&issue=electrical" 
                       class="btn btn-sm flex-fill <?php echo $issue=='electrical' ? 'btn-warning' : 'btn-outline-secondary'; ?>">Electrical</a>
                    <a href="?period=<?php echo $period; ?>&status=<?php echo $status; ?>&issue=furniture" 
                       class="btn btn-sm flex-fill <?php echo $issue=='furniture' ? 'btn-warning' : 'btn-outline-secondary'; ?>">Furniture</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Buttons -->
<div class="d-flex justify-content-end mb-4">
    <a href="export_word.php?period=<?php echo urlencode($period); ?>&status=<?php echo urlencode($status); ?>&issue=<?php echo urlencode($issue); ?>" 
       class="btn btn-outline-primary me-2">
        <i class="bi bi-file-earmark-word"></i> Export Word
    </a>
    <a href="export_pdf.php?period=<?php echo urlencode($period); ?>&status=<?php echo urlencode($status); ?>&issue=<?php echo urlencode($issue); ?>" 
       class="btn btn-outline-danger">
        <i class="bi bi-file-earmark-pdf"></i> Export PDF
    </a>
</div>

<!-- ===============================
     DETAILED ISSUE STATS
================================ -->
<div class="row g-4 mb-4">
    <div class="col-md-2">
        <div class="card bg-primary text-white shadow-sm border-0 h-100" style="border-radius:10px; transition: transform 0.2s;">
            <div class="card-body text-center">
                <h6 class="text-white-50">Total</h6>
                <h2 class="fw-bold"><?php echo $detail_total; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white shadow-sm border-0 h-100" style="border-radius:10px;">
            <div class="card-body text-center">
                <h6 class="text-white-50">Pending</h6>
                <h2 class="fw-bold"><?php echo $detail_pending; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white shadow-sm border-0 h-100" style="border-radius:10px;">
            <div class="card-body text-center">
                <h6 class="text-white-50">In Progress</h6>
                <h2 class="fw-bold"><?php echo $detail_progress; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white shadow-sm border-0 h-100" style="border-radius:10px;">
            <div class="card-body text-center">
                <h6 class="text-white-50">Completed</h6>
                <h2 class="fw-bold"><?php echo $detail_completed; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-danger text-white shadow-sm border-0 h-100" style="border-radius:10px;">
            <div class="card-body text-center">
                <h6 class="text-white-50">Conflict</h6>
                <h2 class="fw-bold"><?php echo $detail_conflict; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 h-100" style="border-radius:12px;">
            <div class="card-header bg-primary text-white" style="border-radius:12px 12px 0 0;">
                <h6 class="mb-0"><i class="bi bi-bar-chart-line"></i> Requests by Issue Type</h6>
            </div>
            <div class="card-body">
                <canvas id="issueReport" height="220"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100" style="border-radius:12px;">
            <div class="card-header bg-primary text-white" style="border-radius:12px 12px 0 0;">
                <h6 class="mb-0"><i class="bi bi-pie-chart"></i> Status Overview</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="statusReport" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Technician Workload & Performance Row -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100" style="border-radius:12px;">
            <div class="card-header bg-primary text-white" style="border-radius:12px 12px 0 0;">
                <h6 class="mb-0"><i class="bi bi-person-workspace"></i> Technician Workload</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Technician</th>
                                <th class="text-end">Jobs</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $tech_workload->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="text-end"><span class="badge bg-secondary"><?php echo $row['total_jobs']; ?></span></td>
                                </tr>
                            <?php } ?>
                            <?php if ($tech_workload->num_rows == 0) { ?>
                                <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100" style="border-radius:12px;">
            <div class="card-header bg-primary text-white" style="border-radius:12px 12px 0 0;">
                <h6 class="mb-0"><i class="bi bi-star-fill"></i> Technician Performance</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Technician</th>
                                <th>Trade</th>
                                <th class="text-end">Avg Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($perf_result && $perf_result->num_rows > 0) {
                                while ($row = $perf_result->fetch_assoc()) {
                                    $name = htmlspecialchars($row['name']);
                                    $trade = ucfirst($row['specialization']);
                                    $avg   = $row['avg_rating'] ?? '—';
                                    if ($row['total_ratings'] > 0) {
                                        $overall_sum += $row['avg_rating'] * $row['total_ratings'];
                                        $overall_count += $row['total_ratings'];
                                    }
                            ?>
                                    <tr>
                                        <td><?php echo $name; ?></td>
                                        <td><span class="badge bg-info"><?php echo $trade; ?></span></td>
                                        <td class="text-end">
                                            <?php if ($avg !== '—') { ?>
                                                <span class="fw-bold text-warning">⭐ <?php echo $avg; ?></span>
                                            <?php } else { ?>
                                                <span class="text-muted">—</span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                            <?php }
                            } else { ?>
                                <tr><td colspan="3" class="text-center text-muted">No technicians</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <!-- Overall average footer -->
                <div class="px-3 py-2 bg-light border-top">
                    <?php if ($overall_count > 0) {
                        $overall_avg = round($overall_sum / $overall_count, 2);
                    ?>
                        <small class="text-muted">
                            <i class="bi bi-trophy text-warning"></i> Overall average: 
                            <strong>⭐ <?php echo $overall_avg; ?> / 10</strong> 
                            (<?php echo $overall_count; ?> rating<?php echo $overall_count > 1 ? 's' : ''; ?>)
                        </small>
                    <?php } else { ?>
                        <small class="text-muted">No ratings yet</small>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Request List -->
<div class="card shadow-sm border-0 mt-4" style="border-radius:12px;">
    <div class="card-header bg-primary text-white" style="border-radius:12px 12px 0 0;">
        <i class="bi bi-list-ul"></i> Request Details 
        (<?php echo ucfirst($issue); ?>)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Hostel</th>
                        <th>Room</th>
                        <th>Available Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($detail_requests && $detail_requests->num_rows > 0) {
                        while ($r = $detail_requests->fetch_assoc()) {
                            $status_badge = match($r['status']) {
                                'pending'     => 'bg-warning text-dark',
                                'in_progress' => 'bg-primary',
                                'completed'   => 'bg-success',
                                'conflict'    => 'bg-danger',
                                default       => 'bg-secondary'
                            };
                    ?>
                            <tr>
                                <td><span class="text-muted">#<?php echo $r['id']; ?></span></td>
                                <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['hostel']); ?></td>
                                <td><?php echo htmlspecialchars($r['room']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($r['available_time'])); ?></td>
                                <td><span class="badge <?php echo $status_badge; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                            </tr>
                    <?php }
                    } else { ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No requests found for this filter.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div> <!-- /container-fluid -->

<!-- Chart.js -->
<script>
    // Bar chart
    new Chart(document.getElementById("issueReport"), {
        type: "bar",
        data: {
            labels: ["Plumbing", "Electrical", "Furniture"],
            datasets: [{
                label: "Requests",
                backgroundColor: ["#003366", "#FFD700", "#0d6efd"],
                data: [<?php echo $plumbing; ?>, <?php echo $electrical; ?>, <?php echo $furniture; ?>]
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Pie chart
    new Chart(document.getElementById("statusReport"), {
        type: "pie",
        data: {
            labels: ["Pending", "In Progress", "Completed"],
            datasets: [{
                data: [<?php echo $pie_pending; ?>, <?php echo $pie_progress; ?>, <?php echo $pie_completed; ?>],
                backgroundColor: ["#ffc107", "#0d6efd", "#198754"]
            }]
        }
    });
</script>

<?php include("../includes/sidebar_end.php"); ?>
<?php include("../includes/footer.php"); ?>