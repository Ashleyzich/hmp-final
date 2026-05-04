<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'supervisor'){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

$sql = "SELECT requests.*, users.name AS student_name,
        issue_types.issue_name,
        staff_users.name AS technician

        FROM requests

        JOIN users ON requests.student_id = users.id
        JOIN issue_types ON requests.issue_type_id = issue_types.id

        LEFT JOIN staff ON requests.assigned_staff = staff.id
        LEFT JOIN users AS staff_users ON staff.user_id = staff_users.id

        ORDER BY requests.created_at DESC";

$result = $conn->query($sql);


?>

<?php include("../includes/header.php"); ?>
<?php include("../includes/sidebar.php"); ?>

<div class="container-fluid">

<!-- Page Header -->

<div class="card shadow border-0 mb-4 page-header">

<div class="card-body d-flex justify-content-between align-items-center">

<div>
<h3 class="mb-1 text-white">
<i class="bi bi-list-check text-warning"></i> Maintenance Requests
</h3>

<p class="mb-0 text-white">
Monitor all hostel maintenance requests.
</p>
</div>

<i class="bi bi-tools display-5 text-warning"></i>

</div>
</div>

<!-- Requests Table -->

<div class="card shadow border-0">

<div class="card-body">

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>
<tr>
<th>ID</th>
<th>Student</th>
<th>Issue</th>
<th>Description</th>
<th>Hostel</th>
<th>Room</th>
<th>Technician</th>
<th>Status</th>
<th>Available Time</th>
<th>Action</th> <!-- ✅ NEW COLUMN -->
</tr>
</thead>

<tbody>

<?php
if($result->num_rows > 0){
while($row = $result->fetch_assoc()){
?>

<tr>

<td><?php echo $row['id']; ?></td>
<td><?php echo $row['student_name']; ?></td>
<td><?php echo $row['issue_name']; ?></td>
<td><?php echo $row['description']; ?></td>
<td><?php echo $row['hostel']; ?></td>
<td><?php echo $row['room']; ?></td>

<td>
<?php
if($row['technician']){
    echo "<span class='badge bg-success'>".$row['technician']."</span>";
}else{
    echo "<span class='badge bg-danger'>Unassigned</span>";
}
?>
</td>

<td>
<?php
if($row['status'] == "pending"){
    echo "<span class='badge bg-warning text-dark'>Pending</span>";
}
elseif($row['status'] == "in_progress"){
    echo "<span class='badge bg-primary'>In Progress</span>";
}
elseif($row['status'] == "completed"){
    echo "<span class='badge bg-success'>Completed</span>";
}
elseif($row['status'] == "conflict"){
    echo "<span class='badge bg-danger'>Conflict</span>";
}
?>
</td>

<td><?php echo $row['available_time']; ?></td>

<!-- ✅ DELETE BUTTON -->
<td>
<a href="delete_request.php?id=<?php echo $row['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this request?')">
Delete
</a>
</td>

</tr>

<?php
}
}else{
echo "<tr><td colspan='10' class='text-center'>No maintenance requests found</td></tr>";
}
?>

</tbody>

</table>

</div>

</div>

</div>

<div class="mt-4">
<a href="dashboard.php" class="btn btn-primary">
← Back to Dashboard
</a>
</div>

</div>

<?php include("../includes/sidebar_end.php"); ?>
<?php include("../includes/footer.php"); ?>

