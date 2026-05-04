<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'student'){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

$student_id = $_SESSION['user_id'];

$sql = "SELECT requests.*, issue_types.issue_name
        FROM requests
        JOIN issue_types ON requests.issue_type_id = issue_types.id
        WHERE student_id='$student_id'
        ORDER BY created_at DESC";

$result = $conn->query($sql);
?>

<?php include("../includes/header.php"); ?>
<?php include("../includes/sidebar.php"); ?>

<div class="container-fluid">

<!-- PAGE HEADER -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-1 fw-bold text-white">
                <i class="bi bi-list-check text-warning"></i>
                My Maintenance Requests
            </h3>
            <p class="mb-0 text-white">
                Track the status of your maintenance requests.
            </p>
        </div>
        <i class="bi bi-tools display-5 text-warning"></i>
    </div>
</div>

<!-- REQUESTS TABLE -->
<div class="card shadow border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="requestsTable">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Issue Type</th>
                        <th>Description</th>
                        <th>Hostel</th>
                        <th>Room</th>
                        <th>Status</th>
                        <th>Available Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
<?php
if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $current_time = date("Y-m-d H:i:s");
?>
                    <tr data-request-id="<?php echo $row['id']; ?>">
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['issue_name']; ?></td>
                        <td><?php echo $row['description']; ?></td>
                        <td><?php echo $row['hostel']; ?></td>
                        <td><?php echo $row['room']; ?></td>
                        <td>
                            <?php
                            if($row['status'] == "pending"){
                                echo "<span class='badge bg-warning'>Pending</span>";
                            } elseif($row['status'] == "in_progress"){
                                echo "<span class='badge bg-primary'>In Progress</span>";
                            } elseif($row['status'] == "completed"){
                                echo "<span class='badge bg-success'>Completed</span>";
                            } elseif($row['status'] == "conflict"){
                                echo "<span class='badge bg-danger'>Conflict</span>";
                            }
                            ?>
                        </td>
                        <td><?php echo $row['available_time']; ?></td>
                        <td class="action-cell">
<?php
// --- CONFLICT: allow student to change time
if($row['status'] == "conflict"){
?>
                            <form method="POST" action="reschedule_time.php" class="d-inline">
                                <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                <div class="input-group input-group-sm">
                                    <input type="datetime-local" name="new_time" class="form-control" required>
                                    <button type="submit" class="btn btn-warning btn-sm">Reschedule</button>
                                </div>
                            </form>
<?php
}
// --- IN PROGRESS: show yes/no after 1 hour
elseif($row['status'] == "in_progress" && strtotime($current_time) >= strtotime($row['available_time'].' +1 hour')){
?>
                            <a href="repair_yes.php?id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm mb-1">Yes, Completed</a>
                            <a href="report_absence.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm">No (Did Not Come)</a>
<?php
} else {
    echo "<span class='text-muted'>-</span>";
}
?>
                        </td>
                    </tr>
<?php
    }
} else {
    echo "<tr><td colspan='8' class='text-center'>No requests submitted yet</td></tr>";
}
?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-4">
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>

</div>

<?php include("../includes/sidebar_end.php"); ?>
<?php include("../includes/footer.php"); ?>

<!-- Automatic assignment polling -->
<script>
function checkAssignments() {
    fetch('check_assignment.php')
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                // If any request status changed (assigned or conflict), reload to show latest state
                location.reload();
            }
        })
        .catch(err => console.error('Polling error:', err));
}

// Poll every 30 seconds
setInterval(checkAssignments, 30000);
// Run once shortly after page load
setTimeout(checkAssignments, 2000);
</script>