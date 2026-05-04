<?php
session_start();

if($_SESSION['role'] != 'supervisor'){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

$users = $conn->query("SELECT * FROM users");
?>

<?php include("../includes/header.php"); ?>
<?php include("../includes/sidebar.php"); ?>

<div class="container-fluid">

<div class="page-header">
<h3><i class="bi bi-people text-warning"></i> Manage Users</h3>
<p>View and delete system users</p>
</div>

<div class="card shadow border-0">

<div class="card-body">

<table class="table table-hover">

<thead class="table-dark">
<tr>
<th>Name</th>
<th>Email</th>
<th>Role</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php while($row = $users->fetch_assoc()){ ?>

<tr>

<td><?php echo $row['name']; ?></td>
<td><?php echo $row['email']; ?></td>
<td><?php echo ucfirst($row['role']); ?></td>

<td>

<a href="delete_user.php?id=<?php echo $row['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this user?')">
Delete
</a>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

<?php include("../includes/sidebar_end.php"); ?>
<?php include("../includes/footer.php"); ?>