<?php
/**
 * Tries to find a free staff of the required specialization.
 * If found, assigns them, sets request to 'in_progress', and returns 'assigned'.
 * If no free staff, returns 'unavailable'.
 */
function assignTechnician($conn, $issue_type, $request_id) {
    // Map issue_type_id to specialization
    switch ($issue_type) {
        case 1:
            $specialization = "plumber";
            break;
        case 2:
            $specialization = "electrician";
            break;
        case 3:
            $specialization = "carpenter";
            break;
        default:
            return 'unavailable';
    }

    // Find one free staff of the required trade (free as set by supervisor)
    $sql = "SELECT staff.id
            FROM staff
            WHERE staff.specialization = '$specialization'
              AND staff.status = 'free'
            LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $staff = $result->fetch_assoc();
        $staff_id = $staff['id'];

        // Assign the request
        $conn->query("UPDATE requests 
                      SET assigned_staff = '$staff_id',
                          status = 'in_progress'
                      WHERE id = '$request_id'");

        // Mark staff as occupied
        $conn->query("UPDATE staff SET status = 'occupied' WHERE id = '$staff_id'");

        return 'assigned';
    }

    return 'unavailable';
}
?>