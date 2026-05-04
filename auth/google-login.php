<?php
session_start();
include("../config/database.php");

// Get JSON data from frontend
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['token'])) {
    echo json_encode(["status" => "error", "message" => "No token"]);
    exit();
}

$token = $data['token'];

// Verify token with Google
$response = file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=" . $token);
$payload = json_decode($response);

// Check if valid
if (!$payload || !isset($payload->email)) {
    echo json_encode(["status" => "error", "message" => "Invalid token"]);
    exit();
}

// Extract user info
$email = $payload->email;
$name = $payload->name;
$google_id = $payload->sub;
$picture = $payload->picture;

// Restrict to HIT emails
if (!str_ends_with($email, "@hit.ac.zw")) {
    echo json_encode(["status" => "error", "message" => "Only @hit.ac.zw email addresses are allowed."]);
    exit();
}

// Check if user already exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {

    // Existing user
    $user = $result->fetch_assoc();

} else {

    // New user → assign default role
    if ($email == "admin@hit.ac.zw") {
        $role = "supervisor";
    } 
    elseif (str_contains($email, "staff")) {
        $role = "staff";
    } 
    elseif (str_ends_with($email, "@hit.ac.zw")) {
        $role = "student";
    } 
    else {
        $role = "student";
    }

    $stmt = $conn->prepare("INSERT INTO users (name,email,google_id,profile_pic,role) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssss", $name, $email, $google_id, $picture, $role);
    $stmt->execute();

    $user = [
        "id" => $conn->insert_id,
        "name" => $name,
        "email" => $email,
        "role" => $role
    ];
}

// Create session
$_SESSION['user_id'] = $user['id'];
$_SESSION['name'] = $user['name'];
$_SESSION['role'] = $user['role'];

// Redirect based on role
if ($user['role'] == "student") {
    $redirect = "../student/dashboard.php";
} elseif ($user['role'] == "staff") {
    $redirect = "../staff/dashboard.php";
} else {
    $redirect = "../supervisor/dashboard.php";
}

// Send response
echo json_encode([
    "status" => "success",
    "redirect" => $redirect
]);