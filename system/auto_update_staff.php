<?php
include("../config/database.php");

/* Find requests that finished (1 hour passed) */

$requests = $conn->query("
SELECT * FROM requests
WHERE status='in_progress'
AND available_time <= NOW() - INTERVAL 1 HOUR
");

while($row = $requests->fetch_assoc()){

    $staff_id = $row['assigned_staff'];

    if($staff_id){

        // Free the staff
        $conn->query("
            UPDATE staff
            SET status='free'
            WHERE id='$staff_id'
        ");

    }

}
?>