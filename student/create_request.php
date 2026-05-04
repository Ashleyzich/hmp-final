<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'student'){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

$message = "";

if(isset($_POST['submit_request'])){

    $student_id = $_SESSION['user_id'];
    $issue_type = $_POST['issue_type'];
    $hostel = $_POST['hostel'];
    $room = $_POST['room'];
    $description = $_POST['description'];
    $available_time = $_POST['available_time'];

    $sql = "INSERT INTO requests
            (student_id,issue_type_id,hostel,room,description,available_time,status)
            VALUES
            ('$student_id','$issue_type','$hostel','$room','$description','$available_time','pending')";

    $conn->query($sql);

    $request_id = $conn->insert_id;

   // NEW LOGIC: Do NOT assign immediately. Status remains 'pending'.
$message = "<div class='alert alert-success'>Request submitted successfully. We will assign a technician at your preferred time.</div>";

}
?>

<?php include("../includes/header.php"); ?>
<?php include("../includes/sidebar.php"); ?>

<div class="container-fluid">

<!-- Page Header -->

<div class="page-header">

<div class="d-flex justify-content-between align-items-center">

<div>

<h3 class="mb-1 fw-bold text-white">
<i class="bi bi-tools text-warning"></i>
Submit Maintenance Request
</h3>

<p class="mb-0 text-white">
Report a maintenance issue in your hostel room.
</p>

</div>

<i class="bi bi-wrench-adjustable display-5 text-warning"></i>

</div>

</div>

<!-- Request Form -->

<div class="card shadow border-0">

<div class="card-body">

<?php echo $message; ?>

<form method="POST">

<div class="row g-3">

<div class="col-md-6">
<label class="form-label">Hostel</label>
<input type="text" name="hostel" class="form-control" required>
</div>

<div class="col-md-6">
<label class="form-label">Room Number</label>
<input type="text" name="room" class="form-control" required>
</div>

<div class="col-md-6">
<label class="form-label">Issue Type</label>

<select name="issue_type" class="form-select">

<option value="1">Plumbing</option>
<option value="2">Electrical</option>
<option value="3">Broken Furniture</option>

</select>

</div>

<div class="col-md-12">
<label class="form-label">Description of Problem</label>
<textarea name="description" class="form-control" rows="4" required></textarea>
</div>

<div class="col-md-6">
<label class="form-label">Available Time for Repair</label>
<input type="datetime-local" name="available_time" class="form-control" required>
</div>

</div>

<div class="mt-4">

<button type="submit" name="submit_request" class="btn btn-primary">
Submit Request
</button>

<a href="dashboard.php" class="btn btn-secondary">
Back to Dashboard
</a>

</div>

</form>

</div>

</div>

</div>

<?php include("../includes/sidebar_end.php"); ?>
<?php include("../includes/footer.php"); ?>