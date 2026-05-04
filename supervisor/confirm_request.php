<?php
session_start();
include("../config/database.php");

$request_id = $_GET['id'];

$conn->query("
UPDATE requests
SET technician_arrived = 1
WHERE id='$request_id'
");

header("Location: view_requests.php");
?>