<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'supervisor'){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

/* ===============================
   SYSTEM STATISTICS
================================*/

$total_requests = $conn->query("SELECT COUNT(*) AS total FROM requests");
$total_requests = $total_requests->fetch_assoc()['total'];

$pending = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE status='pending'");
$pending = $pending->fetch_assoc()['total'];

$progress = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE status='in_progress'");
$progress = $progress->fetch_assoc()['total'];

$completed = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE status='completed'");
$completed = $completed->fetch_assoc()['total'];

$staff = $conn->query("SELECT COUNT(*) AS total FROM staff");
$staff = $staff->fetch_assoc()['total'];


/* ===============================
   ISSUE TYPE STATISTICS
================================*/

$plumbing = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE issue_type_id=1")->fetch_assoc()['total'];
$electrical = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE issue_type_id=2")->fetch_assoc()['total'];
$furniture = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE issue_type_id=3")->fetch_assoc()['total'];


/* ===============================
   TECHNICIAN ABSENCE ALERTS
================================*/

$failed_repairs = $conn->query("
SELECT requests.*, users.name AS student_name
FROM requests
JOIN users ON requests.student_id = users.id
WHERE technician_arrived = 0
AND status='pending'
ORDER BY available_time DESC
");

?>

<?php include("../includes/header.php"); ?>
<?php include("../includes/sidebar.php"); ?>

<div class="container-fluid">

<!-- DASHBOARD HEADER -->

<div class="card shadow border-0 mb-4 text-white"
style="background: linear-gradient(135deg, #003366, #00509E); border-radius:15px;">

<div class="card-body d-flex justify-content-between align-items-center">

<div>

<h3 class="mb-1 fw-bold text-white">
    <i class="bi bi-person-gear text-warning"></i> Supervisor Dashboard
</h3>

<p class="mb-0 text-light">
Welcome back, <strong><?php echo $_SESSION['name']; ?></strong>
</p>

</div>

<i class="bi bi-shield-check display-5 text-warning"></i>

</div>

</div>


<!-- TECHNICIAN ALERTS -->

<?php if($failed_repairs && $failed_repairs->num_rows > 0){ ?>

<div class="card shadow border-0 mb-4 border-danger">

<div class="card-header bg-danger text-white">
<i class="bi bi-exclamation-triangle"></i> Technician Absence Alerts
</div>

<div class="card-body">

<table class="table table-hover">

<thead>
<tr>
<th>Request</th>
<th>Student</th>
<th>Hostel</th>
<th>Room</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php while($row = $failed_repairs->fetch_assoc()){ ?>

<tr>

<td>#<?php echo $row['id']; ?></td>
<td><?php echo htmlspecialchars($row['student_name']); ?></td>
<td><?php echo htmlspecialchars($row['hostel']); ?></td>
<td><?php echo htmlspecialchars($row['room']); ?></td>

<td>
<a href="reassign_request.php?id=<?php echo $row['id']; ?>" 
class="btn btn-warning btn-sm">
Reassign Technician
</a>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

<?php } ?>


<!-- STATISTICS CARDS -->

<div class="row g-4 mb-4">

<div class="col-md-3">
<div class="card bg-primary text-white shadow border-0">
<div class="card-body">
<h6>Total Requests</h6>
<h2><?php echo $total_requests; ?></h2>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card bg-warning text-white shadow border-0">
<div class="card-body">
<h6>Pending Requests</h6>
<h2><?php echo $pending; ?></h2>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card bg-info text-white shadow border-0">
<div class="card-body">
<h6>In Progress</h6>
<h2><?php echo $progress; ?></h2>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card bg-success text-white shadow border-0">
<div class="card-body">
<h6>Completed</h6>
<h2><?php echo $completed; ?></h2>
</div>
</div>
</div>

</div>


<!-- ACTION CARDS -->

<div class="row g-4 mb-5">

<div class="col-md-4">

<div class="card shadow border-0 h-100">

<div class="card-body text-center">

<i class="bi bi-people display-4 text-primary"></i>

<h4 class="mt-3">Manage Technicians</h4>

<p>Register and manage maintenance staff.</p>

<a href="manage_staff.php" class="btn btn-primary w-100">
Open
</a>

</div>

</div>

</div>


<div class="col-md-4">

<div class="card shadow border-0 h-100">

<div class="card-body text-center">

<i class="bi bi-list-check display-4 text-success"></i>

<h4 class="mt-3">View Requests</h4>

<p>Monitor all maintenance requests.</p>

<a href="view_requests.php" class="btn btn-success w-100">
Open
</a>

</div>

</div>

</div>


<div class="col-md-4">

<div class="card shadow border-0 h-100">

<div class="card-body text-center">

<i class="bi bi-person-workspace display-4 text-warning"></i>

<h4 class="mt-3">Total Technicians</h4>

<h2 class="mt-3"><?php echo $staff; ?></h2>

</div>

</div>

</div>

</div>



<?php include("../includes/sidebar_end.php"); ?>
<?php include("../includes/footer.php"); ?>