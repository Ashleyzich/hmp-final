<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'supervisor'){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

$request_id = $_GET['id'];

/* Get request information */

$request = $conn->query("
SELECT requests.*, issue_types.issue_name
FROM requests
JOIN issue_types ON requests.issue_type_id = issue_types.id
WHERE requests.id='$request_id'
")->fetch_assoc();

$issue_type = $request['issue_type_id'];

/* Determine specialization */

if($issue_type == 1){
    $specialization = "plumber";
}
elseif($issue_type == 2){
    $specialization = "electrician";
}
else{
    $specialization = "carpenter";
}

/* Get available technicians */

$techs = $conn->query("
SELECT staff.id, users.name
FROM staff
JOIN users ON staff.user_id = users.id
WHERE staff.specialization='$specialization'
AND staff.status='free'
");

?>

<?php include("../includes/header.php"); ?>
<?php include("../includes/sidebar.php"); ?>

<div class="container-fluid">

<div class="card shadow border-0">

<div class="card-body">

<h4>Reassign Technician</h4>

<p><strong>Issue:</strong> <?php echo $request['issue_name']; ?></p>

<form method="POST">

<div class="mb-3">

<label>Select Technician</label>

<select name="staff_id" class="form-select">

<?php while($tech = $techs->fetch_assoc()){ ?>

<option value="<?php echo $tech['id']; ?>">
<?php echo $tech['name']; ?>
</option>

<?php } ?>

</select>

</div>

<button type="submit" name="assign" class="btn btn-primary">
Assign Technician
</button>

<a href="dashboard.php" class="btn btn-secondary">
Back
</a>

</form>

</div>

</div>

</div>

<?php include("../includes/sidebar_end.php"); ?>
<?php include("../includes/footer.php"); ?>

<?php

if(isset($_POST['assign'])){

$staff_id = $_POST['staff_id'];

/* Assign technician */

$conn->query("
UPDATE requests
SET assigned_staff='$staff_id',
status='in_progress'
WHERE id='$request_id'
");

/* Update technician status */

$conn->query("
UPDATE staff
SET status='occupied'
WHERE id='$staff_id'
");

header("Location: dashboard.php");

}

?>