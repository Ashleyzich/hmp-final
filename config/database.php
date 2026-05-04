<?php
// database.php
// This file connects the PHP application to the MySQL database

$servername = "localhost";   // server name
$username = "root";          // default XAMPP username
$password = "";              // default password is empty
$dbname = "hostel-maintenance";  // database we created earlier


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);


// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// If connection works
// echo "Database connected successfully";

?>