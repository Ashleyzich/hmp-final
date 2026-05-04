<?php
// variables passed from page
// $title and $icon
?>

<div class="topbar">

<div class="d-flex justify-content-between align-items-center">

<div>

<h3 class="mb-1 fw-bold text-white">
<i class="bi bi-<?php echo $icon; ?> text-warning"></i>
<?php echo $title; ?>
</h3>

<p class="mb-0 text-white">
Welcome back,
<strong class="text-warning"><?php echo $_SESSION['name']; ?></strong>
</p>

</div>

<i class="bi bi-<?php echo $icon; ?> display-5 text-warning"></i>

</div>

</div>