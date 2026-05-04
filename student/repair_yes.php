<?php
session_start();
include("../config/database.php");

$request_id = $_GET['id'];

// 1️⃣ Get the assigned staff ID from the request
$req = $conn->query("SELECT assigned_staff FROM requests WHERE id='$request_id'")->fetch_assoc();
$staff_id = $req['assigned_staff'] ?? null;

// 2️⃣ Mark the request as completed
$conn->query("UPDATE requests 
              SET technician_arrived = 1, 
                  status = 'completed' 
              WHERE id = '$request_id'");

// 3️⃣ If a staff was assigned, free them
if ($staff_id) {
    $conn->query("UPDATE staff SET status = 'free' WHERE id = '$staff_id'");
}

// 4️⃣ Redirect to the rating page
header("Location: rate_technician.php?id=" . $request_id);
exit();
?>