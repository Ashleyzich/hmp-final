<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'staff'){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

$user_id = $_SESSION['user_id'];

/* Get staff record */

$staff_query = "SELECT * FROM staff WHERE user_id='$user_id'";
$staff_result = $conn->query($staff_query);
$staff = $staff_result->fetch_assoc();

$staff_id = $staff['id'];

/* Assigned Tasks */

$sql = "SELECT requests.*, issue_types.issue_name
        FROM requests
        JOIN issue_types ON requests.issue_type_id = issue_types.id
        WHERE assigned_staff='$staff_id'
        ORDER BY created_at DESC";

$result = $conn->query($sql);

/* Completed tasks count */

$completed_tasks = $conn->query("SELECT COUNT(*) AS total 
                                 FROM requests 
                                 WHERE assigned_staff='$staff_id' 
                                 AND status='completed'")
                         ->fetch_assoc()['total'];
?>

<?php include("../includes/header.php"); ?>
<?php include("../includes/sidebar.php"); ?>

<div class="container-fluid">

<!-- Dashboard Header -->

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-1 fw-bold text-white">
                <i class="bi bi-tools text-warning"></i> Maintenance Staff Dashboard
            </h3>
            <p class="mb-0 text-white">
                Welcome back, <strong class="text-warning"><?php echo $_SESSION['name']; ?></strong>
            </p>
        </div>
        <i class="bi bi-wrench-adjustable display-5 text-warning"></i>
    </div>
</div>


<!-- Statistics -->

<div class="row g-4 mb-4">

<div class="col-md-6">

<div class="card bg-primary text-white shadow border-0">

<div class="card-body">

<h6>Assigned Tasks</h6>

<h2><?php echo $result->num_rows; ?></h2>

</div>

</div>

</div>


<div class="col-md-6">

<div class="card bg-success text-white shadow border-0">

<div class="card-body">

<h6>Completed Jobs</h6>

<h2><?php echo $completed_tasks; ?></h2>

</div>

</div>

</div>

</div>


<!-- Tasks Table -->

<div class="card shadow border-0">

<div class="card-body">

<h5 class="mb-3">Assigned Maintenance Tasks</h5>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead class="table-dark">

<tr>
<th>ID</th>
<th>Issue</th>
<th>Description</th>
<th>Hostel</th>
<th>Room</th>
<th>Available Time</th>
<th>Status</th>
<th>Action</th>
</tr>

</thead>

<tbody>

<?php

if($result->num_rows > 0){

while($row = $result->fetch_assoc()){

?>

<tr>

<td><?php echo $row['id']; ?></td>

<td><?php echo $row['issue_name']; ?></td>

<td><?php echo $row['description']; ?></td>

<td><?php echo $row['hostel']; ?></td>

<td><?php echo $row['room']; ?></td>

<td><?php echo $row['available_time']; ?></td>

<td>

<?php

if($row['status'] == "pending"){
echo "<span class='badge bg-warning'>Pending</span>";
}
elseif($row['status'] == "in_progress"){
echo "<span class='badge bg-primary'>In Progress</span>";
}
else{
echo "<span class='badge bg-success'>Completed</span>";
}

?>

</td>

<td>

<?php
if($row['status'] == "in_progress"){
?>

<a href="complete_request.php?id=<?php echo $row['id']; ?>"
class="btn btn-success btn-sm">
Mark Completed
</a>

<?php
}
?>

</td>

</tr>

<?php
}

}else{

echo "<tr><td colspan='8' class='text-center'>No tasks assigned</td></tr>";

}

?>

</tbody>

</table>

</div>

</div>

</div>

</div>

<?php include("../includes/sidebar_end.php"); ?>
<?php include("../includes/footer.php"); ?>