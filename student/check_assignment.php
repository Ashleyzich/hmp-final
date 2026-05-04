<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    http_response_code(403);
    exit('Unauthorized');
}

include("../config/database.php");
include("../includes/assign_staff.php");

$student_id = $_SESSION['user_id'];

// Select all pending requests from this student that are past the preferred time
$sql = "SELECT id, issue_type_id FROM requests 
        WHERE student_id = '$student_id' 
          AND status = 'pending' 
          AND available_time <= NOW()";
$result = $conn->query($sql);

$updated = [];

while ($row = $result->fetch_assoc()) {
    $request_id = $row['id'];
    $issue_type = $row['issue_type_id'];

    $assign_result = assignTechnician($conn, $issue_type, $request_id);

    if ($assign_result == 'unavailable') {
        // No free technician → mark as conflict so student can reschedule
        $conn->query("UPDATE requests SET status = 'conflict' WHERE id = '$request_id'");
        $updated[] = ['id' => $request_id, 'new_status' => 'conflict'];
    } else {
        $updated[] = ['id' => $request_id, 'new_status' => 'in_progress'];
    }
}

header('Content-Type: application/json');
echo json_encode($updated);
?>