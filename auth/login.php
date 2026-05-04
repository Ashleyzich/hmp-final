<?php
session_start();
include("../config/database.php");

$message = "";

if(isset($_POST['login'])){

    $email = $_POST['email'];
    $password = $_POST['password'];

    // ✅ Only HIT emails are allowed to use the system
    if (!str_ends_with($email, '@hit.ac.zw')) {
        $message = "<div class='alert alert-danger'>Only @hit.ac.zw email addresses are permitted.</div>";
    } else {

        $sql = "SELECT * FROM users WHERE email='$email'";
        $result = $conn->query($sql);

        if($result->num_rows > 0){

            $user = $result->fetch_assoc();

            if(password_verify($password,$user['password'])){

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                if($user['role'] == "student"){
                    header("Location: ../student/dashboard.php");
                }
                elseif($user['role'] == "staff"){
                    header("Location: ../staff/dashboard.php");
                }
                elseif($user['role'] == "supervisor"){
                    header("Location: ../supervisor/dashboard.php");
                }
                exit();

            } else {
                $message = "<div class='alert alert-danger'>Incorrect password</div>";
            }

        } else {
            $message = "<div class='alert alert-danger'>User not found</div>";
        }
    }
}
?>

<?php include("../includes/header.php"); ?>

<style>
body {
    background: linear-gradient(135deg, #1e3c72, #2a5298);
}
.login-card {
    background: #fff;
    padding: 35px;
    border-radius: 16px;
    width: 100%;
    max-width: 380px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.2);
}
.login-title {
    font-weight: 700;
    color: #0d47a1;
}
.login-sub {
    font-size: 14px;
    color: #666;
}
.form-control {
    border-radius: 8px;
    padding: 10px;
}
.btn-login {
    border-radius: 8px;
    padding: 10px;
    font-weight: 600;
}
.divider {
    text-align: center;
    margin: 20px 0;
    font-size: 12px;
    color: #aaa;
    position: relative;
}
.divider::before,
.divider::after {
    content: "";
    height: 1px;
    width: 40%;
    background: #ddd;
    position: absolute;
    top: 50%;
}
.divider::before { left: 0; }
.divider::after { right: 0; }
</style>

<div class="container-fluid vh-100">
<div class="row h-100">

<!-- LEFT PANEL -->
<div class="col-md-6 d-none d-md-flex align-items-center justify-content-center text-white"
style="background: linear-gradient(rgba(0,51,102,0.85), rgba(0,51,102,0.85)), url('https://images.unsplash.com/photo-1521791136064-7986c2920216'); background-size: cover;">

<div class="text-center px-4">
    <h1 class="fw-bold text-warning">HIT Hostel Maintenance Portal</h1>
    <p class="lead">Submit, track and manage hostel maintenance requests.</p>
</div>

</div>

<!-- RIGHT PANEL -->
<div class="col-md-6 d-flex align-items-center justify-content-center">

<div class="login-card">

    <div class="text-center mb-3">
        <img src="../assets/images/HITlogo.png" alt="HIT Logo">
    </div>

    <h4 class="text-center login-title">Login</h4>
    <p class="text-center login-sub mb-3">Access your account</p>

    <?php echo $message; ?>

    <form method="POST">
        <div class="mb-3">
            <input type="email" name="email" class="form-control" placeholder="Email" required>
        </div>

        <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>

        <button class="btn btn-primary w-100 btn-login" name="login">
            Login
        </button>
    </form>

    <div class="divider">OR</div>

    <!-- GOOGLE BUTTON -->
    <div class="text-center">
        <div id="g_id_onload"
             data-client_id="381122014546-s7kag9e8efjic41fbe2n0c3ptpv008bq.apps.googleusercontent.com"
             data-callback="handleCredentialResponse">
        </div>

        <div class="g_id_signin"
             data-type="standard"
             data-size="large"
             data-theme="outline"
             data-text="continue_with"
             data-shape="rectangular">
        </div>
    </div>

    <p class="text-center mt-3 small">
        Don't have an account? <a href="register.php">Register</a>
    </p>

</div>
</div>

</div>
</div>

<script src="https://accounts.google.com/gsi/client" async defer></script>

<script>
function handleCredentialResponse(response) {
    fetch("google-login.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            token: response.credential
        })
    })
    .then(res => res.text())
    .then(data => {
        console.log("SERVER:", data);

        try {
            let json = JSON.parse(data);

            if (json.status === "success") {
                window.location.href = json.redirect;
            } else {
                alert(json.message || "Google login failed");
            }

        } catch (e) {
            console.error("Invalid JSON:", data);
        }
    })
    .catch(err => console.error(err));
}
</script>

<?php include("../includes/footer.php"); ?>