<?php
// system/assignment_checker.php
// Self-contained script that runs on every page load.
// Assigns technicians to pending requests past their preferred time,
// and sends in-portal notifications to students.

// Include the notification helper (defines createNotification)
include_once(__DIR__ . "/../includes/notifications.php");

(function () {
    // ── Own database connection ──────────────────────────
    $servername = "localhost";
    $username   = "root";
    $password   = "";
    $dbname     = "hostel-maintenance";

    $checker_conn = new mysqli($servername, $username, $password, $dbname);
    if ($checker_conn->connect_error) {
        return;
    }

    // ── Fetch pending requests that are due ───────────────
    $sql = "SELECT id, issue_type_id, student_id FROM requests 
            WHERE status = 'pending' AND available_time <= NOW()";
    $result = $checker_conn->query($sql);
    if (!$result || $result->num_rows === 0) {
        $checker_conn->close();
        return;
    }

    while ($row = $result->fetch_assoc()) {
        $request_id = $row['id'];
        $issue_type = $row['issue_type_id'];
        $student_id = $row['student_id'];

        // Determine required specialization
        switch ($issue_type) {
            case 1: $specialization = 'plumber';    break;
            case 2: $specialization = 'electrician'; break;
            case 3: $specialization = 'carpenter';   break;
            default:
                continue 2;
        }

        // Find a free staff of the required trade
        $staff_sql = "SELECT id FROM staff 
                      WHERE specialization = '$specialization' 
                        AND status = 'free' 
                      LIMIT 1";
        $staff_result = $checker_conn->query($staff_sql);

        if ($staff_result && $staff_result->num_rows > 0) {
            // ── Technician found → assign ─────────────────
            $staff = $staff_result->fetch_assoc();
            $staff_id = $staff['id'];

            $checker_conn->query("UPDATE requests 
                                  SET assigned_staff = '$staff_id', 
                                      status = 'in_progress' 
                                  WHERE id = '$request_id'");
            $checker_conn->query("UPDATE staff SET status = 'occupied' WHERE id = '$staff_id'");

            // Fetch staff details (name + phone)
            $staff_info = $checker_conn->query("
                SELECT u.name, s.phone
                FROM staff s
                JOIN users u ON s.user_id = u.id
                WHERE s.id = '$staff_id'
            ")->fetch_assoc();

            $staff_name  = $staff_info['name']  ?? 'Technician';
            $staff_phone = $staff_info['phone'] ?? 'N/A';

            // Notify the student
            $message = "Technician $staff_name (phone: $staff_phone) has been assigned to your request #$request_id.";
            createNotification($checker_conn, $student_id, $message);

        } else {
            // ── No free technician → conflict ─────────────
            $checker_conn->query("UPDATE requests SET status = 'conflict' WHERE id = '$request_id'");

            // Notify the student
            $message = "No technician is available at your preferred time for request #$request_id. Please reschedule.";
            createNotification($checker_conn, $student_id, $message);
        }
    }

    $checker_conn->close();
})();
?>