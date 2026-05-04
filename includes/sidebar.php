<?php
$role = $_SESSION['role'];

// Count unread notifications for the current user
$unread_sql = "SELECT COUNT(*) AS total FROM notifications WHERE user_id='{$_SESSION['user_id']}' AND status='unread'";
$unread_result = $conn->query($unread_sql);
$unread_count = ($unread_result && $unread_result->num_rows > 0) ? $unread_result->fetch_assoc()['total'] : 0;
?>

<div class="d-flex">

<!-- Sidebar -->
<div class="text-white p-3" style="width:250px; min-height:100vh; background-color:#003366;">

<h4 class="text-center text-warning fw-bold">
HIT Maintenance
</h4>

<hr class="bg-light">

<ul class="nav flex-column">

<?php if($role == "student"){ ?>

    <li class="nav-item">
        <a class="nav-link text-white" href="/hostel-maintenance-portal/student/dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link text-white" href="/hostel-maintenance-portal/student/create_request.php">
            <i class="bi bi-tools"></i> Submit Request
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link text-white" href="/hostel-maintenance-portal/student/view_requests.php">
            <i class="bi bi-list-check"></i> View Requests
        </a>
    </li>

<?php } ?>


<?php if($role == "staff"){ ?>

    <li class="nav-item">
        <a class="nav-link text-white" href="/hostel-maintenance-portal/staff/dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link text-white" href="/hostel-maintenance-portal/staff/dashboard.php">
            <i class="bi bi-tools"></i> Assigned Tasks
        </a>
    </li>

<?php } ?>


<?php if($role == "supervisor"){ ?>

    <li class="nav-item">
        <a class="nav-link text-white" href="/hostel-maintenance-portal/supervisor/dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link text-white" href="/hostel-maintenance-portal/supervisor/manage_staff.php">
            <i class="bi bi-people"></i> Manage Technicians
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link text-white" href="/hostel-maintenance-portal/supervisor/view_requests.php">
            <i class="bi bi-list-check"></i> View Requests
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link text-white" href="/hostel-maintenance-portal/supervisor/manage_users.php">
            <i class="bi bi-people"></i> Manage Users
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link text-white" href="/hostel-maintenance-portal/supervisor/reports.php">
            <i class="bi bi-bar-chart"></i> Reports
        </a>
    </li>

<?php } ?>


<!-- Notifications (all roles) -->
<li class="nav-item">
    <a class="nav-link text-white" href="/hostel-maintenance-portal/notifications.php">
        <i class="bi bi-bell"></i> Notifications
        <?php if ($unread_count > 0): ?>
            <span class="badge bg-danger ms-1"><?php echo $unread_count; ?></span>
        <?php endif; ?>
    </a>
</li>


<!-- Logout -->
<li class="nav-item mt-4">
    <a class="nav-link text-danger" href="/hostel-maintenance-portal/auth/logout.php">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</li>

</ul>

</div>

<!-- Main Content -->
<div class="flex-grow-1 p-4">