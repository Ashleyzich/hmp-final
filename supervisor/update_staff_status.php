<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'supervisor'){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");
include("../includes/assign_staff.php");

$id = $_GET['id'];
$status = $_GET['status'];

// Update staff status
$conn->query("UPDATE staff SET status='$status' WHERE id='$id'");

// If the staff is now free, attempt to assign them to any waiting requests
if ($status == 'free') {
    // Find all pending requests that are past their preferred time
    $requests = $conn->query("SELECT id, issue_type_id FROM requests WHERE status='pending' AND available_time <= NOW()");
    while ($row = $requests->fetch_assoc()) {
        assignTechnician($conn, $row['issue_type_id'], $row['id']);
    }
}

header("Location: manage_staff.php");
exit();
?>