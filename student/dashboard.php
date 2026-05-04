<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'student'){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

$student_id = $_SESSION['user_id'];

/* Get request statistics */

$total = $conn->query("SELECT COUNT(*) as total FROM requests WHERE student_id='$student_id'")
              ->fetch_assoc()['total'];

$pending = $conn->query("SELECT COUNT(*) as total FROM requests WHERE student_id='$student_id' AND status='pending'")
                ->fetch_assoc()['total'];

$completed = $conn->query("SELECT COUNT(*) as total FROM requests WHERE student_id='$student_id' AND status='completed'")
                  ->fetch_assoc()['total'];
?>

<?php include("../includes/header.php"); ?>
<?php include("../includes/sidebar.php"); ?>

<div class="page-header">

<div class="d-flex justify-content-between align-items-center">

<!-- LEFT -->
<div>
<h3 class="mb-1 fw-bold text-white">
<i class="bi bi-speedometer2 text-warning"></i>
Student Dashboard
</h3>

<p class="mb-0 text-white">
Welcome back, 
<strong class="text-warning"><?php echo $_SESSION['name']; ?></strong> 👋
</p>
</div>

<!-- RIGHT -->
<i class="bi bi-person-circle display-5 text-warning"></i>

</div>

</div>
<!-- Statistics Row -->

<div class="row g-4 mb-4">

<div class="col-md-4">
<div class="card bg-primary text-white shadow border-0">
<div class="card-body">
<h5>Total Requests</h5>
<h2><?php echo $total; ?></h2>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card bg-warning text-white shadow border-0">
<div class="card-body">
<h5>Pending Requests</h5>
<h2><?php echo $pending; ?></h2>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card bg-success text-white shadow border-0">
<div class="card-body">
<h5>Completed Requests</h5>
<h2><?php echo $completed; ?></h2>
</div>
</div>
</div>

</div>

<!-- Action Cards -->

<div class="row g-4">

<div class="col-md-4">

<div class="card shadow border-0 h-100">

<div class="card-body text-center">

<i class="bi bi-tools display-4 text-primary"></i>

<h4 class="mt-3">Submit Request</h4>

<p>Report a hostel maintenance issue.</p>

<a href="create_request.php" class="btn btn-primary w-100">
Submit Maintenance Request
</a>

</div>

</div>

</div>


<div class="col-md-4">

<div class="card shadow border-0 h-100">

<div class="card-body text-center">

<i class="bi bi-list-check display-4 text-success"></i>

<h4 class="mt-3">My Requests</h4>

<p>Track the status of your requests.</p>

<a href="view_requests.php" class="btn btn-success w-100">
View My Requests
</a>

</div>

</div>

</div>


<div class="col-md-4">

<div class="card shadow border-0 h-100">

<div class="card-body text-center">

<i class="bi bi-person-circle display-4 text-warning"></i>

<h4 class="mt-3">Profile</h4>

<p>View your student profile.</p>

<a href="#" class="btn btn-warning w-100">
View Profile
</a>

</div>

</div>

</div>

</div>

</div>

<?php include("../includes/sidebar_end.php"); ?>
<?php include("../includes/footer.php"); ?>