<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'supervisor'){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

if(isset($_GET['id'])){

    $user_id = $_GET['id'];

    // delete staff record first (if exists)
    $conn->query("DELETE FROM staff WHERE user_id='$user_id'");

    // delete user
    $conn->query("DELETE FROM users WHERE id='$user_id'");

}

header("Location: manage_users.php");
exit();
?>