<?php
session_start();
include("../config/database.php");

if(isset($_POST['request_id'])){

    $request_id = $_POST['request_id'];
    $new_time = $_POST['new_time'];

    // Get current request to compare old time
    $request = $conn->query("SELECT * FROM requests WHERE id='$request_id'")->fetch_assoc();
    $old_time = $request['available_time'];

    // VALIDATION: time must be different
    if($new_time == $old_time){
        echo "<div class='alert alert-danger'>Please choose a different time from your previous selection.</div>";
        exit();
    }

    // Update time and set status back to pending
    $conn->query("UPDATE requests 
                  SET available_time='$new_time',
                      status='pending',
                      assigned_staff=NULL
                  WHERE id='$request_id'");

    header("Location: view_requests.php?msg=time_updated");
    exit();
}
?>