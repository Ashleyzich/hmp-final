<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");
include_once("../includes/notifications.php");

$request_id = $_GET['id'] ?? null;
$student_id = $_SESSION['user_id'];

// Fetch request details and verify ownership
$request = $conn->query("
    SELECT requests.*, issue_types.issue_name, staff.id AS staff_id, staff.user_id AS staff_user_id
    FROM requests
    JOIN issue_types ON requests.issue_type_id = issue_types.id
    LEFT JOIN staff ON requests.assigned_staff = staff.id
    WHERE requests.id = '$request_id' AND requests.student_id = '$student_id'
")->fetch_assoc();

if (!$request) {
    header("Location: view_requests.php?msg=invalid");
    exit();
}

// Check if rating already exists
$already = $conn->query("SELECT id FROM ratings WHERE request_id = '$request_id'");
if ($already->num_rows > 0) {
    header("Location: view_requests.php?msg=already_rated");
    exit();
}

$staff_id = $request['staff_id'];
$staff_user_id = $request['staff_user_id']; // user ID of the technician

$message = "";
if (isset($_POST['submit_rating'])) {
    $rating = (int) $_POST['rating'];

    if ($rating < 1 || $rating > 10) {
        $message = "<div class='alert alert-danger'>Please select a rating between 1 and 10.</div>";
    } else {
        // Insert rating
        $conn->query("INSERT INTO ratings (request_id, staff_id, rating)
                      VALUES ('$request_id', '$staff_id', '$rating')");

        // Notify the staff member
        if ($staff_user_id) {
            $staff_msg = "You received a rating of $rating/10 for request #$request_id.";
            createNotification($conn, $staff_user_id, $staff_msg);
        }

        header("Location: view_requests.php?msg=rated");
        exit();
    }
}
?>

<?php include("../includes/header.php"); ?>
<?php include("../includes/sidebar.php"); ?>

<div class="container-fluid">

    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-1 fw-bold text-white">
                    <i class="bi bi-star-fill text-warning"></i> Rate Technician
                </h3>
                <p class="mb-0 text-white">
                    Your feedback helps us improve our service.
                </p>
            </div>
            <i class="bi bi-star-half display-5 text-warning"></i>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow border-0">
                <div class="card-body p-4">

                    <h5 class="mb-3">
                        <i class="bi bi-tools text-primary"></i>
                        <?php echo htmlspecialchars($request['issue_name']); ?> – Room <?php echo htmlspecialchars($request['room']); ?>
                    </h5>
                    <p class="text-muted"><?php echo htmlspecialchars($request['description']); ?></p>

                    <?php echo $message; ?>

                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Your Rating (1 = poor, 10 = excellent)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-warning text-dark"><i class="bi bi-star-fill"></i></span>
                                <input type="number" name="rating" class="form-control form-control-lg text-center"
                                       min="1" max="10" value="10" required
                                       style="max-width: 120px; font-size: 1.8rem; font-weight: bold;">
                            </div>
                        </div>

                        <button type="submit" name="submit_rating" class="btn btn-warning btn-lg w-100">
                            <i class="bi bi-send-check"></i> Submit Rating
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>

</div>

<?php include("../includes/sidebar_end.php"); ?>
<?php include("../includes/footer.php"); ?>