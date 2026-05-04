<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'supervisor'){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

$message = "";

/* REGISTER TECHNICIAN */

if(isset($_POST['add_staff'])){

    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $specialization = $_POST['specialization'];
    $password = $_POST['password'];

    // Restrict to HIT emails
    if (!str_ends_with($email, '@hit.ac.zw')) {
        $message = "<div class='alert alert-danger'>Technician email must end with @hit.ac.zw</div>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql1 = "INSERT INTO users (name,email,password,role)
                 VALUES ('$name','$email','$hashed_password','staff')";

        if($conn->query($sql1)){

            $user_id = $conn->insert_id;

            $sql2 = "INSERT INTO staff (user_id,specialization,phone,status)
                     VALUES ('$user_id','$specialization','$phone','free')";

            if($conn->query($sql2)){
                $message = "<div class='alert alert-success'>Technician registered successfully.</div>";
            }

        } else {
            $message = "<div class='alert alert-danger'>".$conn->error."</div>";
        }
    }
}


/* FETCH TECHNICIANS */

$staff_list = "SELECT staff.*, users.name 
               FROM staff
               JOIN users ON staff.user_id = users.id";

$staff_result = $conn->query($staff_list);

?>

<?php include("../includes/header.php"); ?>
<?php include("../includes/sidebar.php"); ?>

<div class="container-fluid">

<!-- Page Header -->

<div class="page-header">

<div class="d-flex justify-content-between align-items-center">

<div>

<h3 class="mb-1 fw-bold text-white">
<i class="bi bi-person-plus text-warning"></i>
Register Technician
</h3>

<p class="mb-0 text-white">
Add and manage maintenance technicians.
</p>

</div>

<i class="bi bi-person-workspace display-5 text-warning"></i>

</div>

</div>

<!-- Technician Registration Form -->

<div class="card shadow border-0 mb-4">

<div class="card-body">

<?php echo $message; ?>

<form method="POST">

<div class="row g-3">

<div class="col-md-6">
<label class="form-label">Full Name</label>
<input type="text" name="name" class="form-control" required>
</div>

<div class="col-md-6">
<label class="form-label">Email</label>
<input type="email" name="email" class="form-control" required placeholder="technician@hit.ac.zw">
</div>

<div class="col-md-6">
<label class="form-label">Password</label>
<input type="password" name="password" class="form-control" required>
</div>

<div class="col-md-6">
<label class="form-label">Phone Number</label>
<input type="text" name="phone" class="form-control" required>
</div>

<div class="col-md-6">
<label class="form-label">Specialization</label>

<select name="specialization" class="form-select">
<option value="plumber">Plumber</option>
<option value="electrician">Electrician</option>
<option value="carpenter">Carpenter</option>
</select>

</div>

</div>

<div class="mt-4">

<button type="submit" name="add_staff" class="btn btn-primary">
Register Technician
</button>

<a href="dashboard.php" class="btn btn-secondary">
Back to Dashboard
</a>

</div>

</form>

</div>

</div>


<!-- Technician List -->

<div class="card shadow border-0">

<div class="card-body">

<h5 class="mb-3">Technician List</h5>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>

<tr>
<th>Name</th>
<th>Specialization</th>
<th>Phone</th>
<th>Status</th>
<th>Action</th>
</tr>

<thead class="table-dark">

<tbody>

<?php

if($staff_result->num_rows > 0){

while($row = $staff_result->fetch_assoc()){

?>

<tr>

<td><?php echo $row['name']; ?></td>

<td><?php echo ucfirst($row['specialization']); ?></td>

<td><?php echo $row['phone']; ?></td>

<td>

<?php
if($row['status'] == "free"){
echo "<span class='badge bg-success'>Free</span>";
}else{
echo "<span class='badge bg-danger'>Occupied</span>";
}
?>

</td>

<td>

<a href="update_staff_status.php?id=<?php echo $row['id']; ?>&status=free"
class="btn btn-success btn-sm">
Free
</a>

<a href="update_staff_status.php?id=<?php echo $row['id']; ?>&status=occupied"
class="btn btn-warning btn-sm">
Occupied
</a>

</td>

</tr>

<?php
}

}else{

echo "<tr><td colspan='5' class='text-center'>No technicians registered</td></tr>";

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