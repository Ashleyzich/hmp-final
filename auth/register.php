<?php
include("../config/database.php");

$message = "";

if(isset($_POST['register'])){

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $hostel = $_POST['hostel'];
    $room = $_POST['room'];

    // Restrict to HIT emails only
    if (!str_ends_with($email, '@hit.ac.zw')) {
        $message = "<div class='alert alert-danger'>Only @hit.ac.zw email addresses are allowed.</div>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (name,email,password,role,hostel,room)
                VALUES ('$name','$email','$hashed_password','student','$hostel','$room')";

        if($conn->query($sql)){
            $message = "<div class='alert alert-success'>Registration successful. You can now login.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: ".$conn->error."</div>";
        }
    }
}
?>

<?php include("../includes/header.php"); ?>

<div class="container-fluid vh-100">
<div class="row h-100">

<!-- LEFT SIDE -->
<div class="col-md-6 d-none d-md-flex align-items-center justify-content-center text-white"
style="background: linear-gradient(rgba(0,51,102,0.9), rgba(0,51,102,0.9)), url('https://images.unsplash.com/photo-1581092334498-2f7d5a5f4f5c'); background-size: cover;">

<div class="text-center px-4">
<h1 class="fw-bold text-warning">Join the Portal</h1>
<p class="lead">Register to submit maintenance requests.</p>
</div>

</div>

<!-- RIGHT SIDE -->
<div class="col-md-6 d-flex align-items-center justify-content-center">

<div class="card shadow border-0 p-4" style="max-width:450px; width:100%;">

<h3 class="text-center mb-3">Student Registration</h3>

<?php echo $message; ?>

<form method="POST">

<div class="mb-3">
<label>Full Name</label>
<input type="text" name="name" class="form-control" required>
</div>

<div class="mb-3">
<label>Email</label>
<input type="email" name="email" class="form-control" required placeholder="student@hit.ac.zw">
</div>

<div class="mb-3">
<label>Password</label>
<input type="password" name="password" class="form-control" required>
</div>

<div class="mb-3">
<label>Hostel</label>
<input type="text" name="hostel" class="form-control" required>
</div>

<div class="mb-3">
<label>Room</label>
<input type="text" name="room" class="form-control" required>
</div>

<button type="submit" name="register" class="btn btn-primary w-100">
Register
</button>

</form>

<p class="text-center mt-3">
Already have an account? <a href="login.php">Login</a>
</p>

</div>

</div>

</div>
</div>

<?php include("../includes/footer.php"); ?>