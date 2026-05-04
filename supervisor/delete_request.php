<?php
session_start();

if($_SESSION['role'] != 'supervisor'){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

if(isset($_GET['id'])){

    $request_id = $_GET['id'];

    $conn->query("DELETE FROM requests WHERE id='$request_id'");
}

header("Location: view_requests.php");
exit();
?>