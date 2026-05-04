<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'staff'){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

if(isset($_GET['id'])){

$request_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

/* Get staff id */

$staff = $conn->query("SELECT * FROM staff WHERE user_id='$user_id'")->fetch_assoc();
$staff_id = $staff['id'];

/* SECURITY CHECK:
   Ensure staff owns this request */

$check = $conn->query("
SELECT * FROM requests 
WHERE id='$request_id' 
AND assigned_staff='$staff_id'
");

if($check->num_rows > 0){

/* Mark request as completed */

$conn->query("
UPDATE requests 
SET status='completed'
WHERE id='$request_id'
");

/* FREE the technician */

$conn->query("
UPDATE staff 
SET status='free'
WHERE id='$staff_id'
");

header("Location: dashboard.php");
exit();

}else{

echo "Unauthorized action.";

}

}else{

echo "Invalid request.";

}
?>