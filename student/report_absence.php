<?php
session_start();
include("../config/database.php");
include_once("../includes/notifications.php");

$request_id = $_GET['id'];

// Reset the request to pending and clear the technician
$conn->query("
    UPDATE requests
    SET status='pending',
        assigned_staff=NULL,
        technician_arrived=0
    WHERE id='$request_id'
");

// Alert supervisor flag (existing logic)
$conn->query("
    UPDATE requests
    SET alert_supervisor = 1
    WHERE id='$request_id'
");

// Send notification to the supervisor
// Fetch the supervisor's user ID (assuming there's at least one supervisor)
$supervisor = $conn->query("SELECT id FROM users WHERE role='supervisor' LIMIT 1")->fetch_assoc();
if ($supervisor) {
    $supervisor_id = $supervisor['id'];
    $msg = "A student reported that the technician did not arrive for request #$request_id. Immediate attention required.";
    createNotification($conn, $supervisor_id, $msg);
}

header("Location: view_requests.php");
exit();
?>