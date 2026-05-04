<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'supervisor') {
    header("Location: ../auth/login.php");
    exit();
}

// 🔧 Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../config/database.php");
require_once __DIR__ . '/../vendor/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/* ===============================
   FILTER PARAMETERS
================================*/
$period = $_GET['period'] ?? 'all';
switch ($period) {
    case 'daily':
        $date_condition = "DATE(requests.created_at) = CURDATE()";
        $rating_date_condition = "DATE(ratings.created_at) = CURDATE()";
        $period_label = "Today";
        break;
    case 'weekly':
        $date_condition = "YEARWEEK(requests.created_at, 1) = YEARWEEK(CURDATE(), 1)";
        $rating_date_condition = "YEARWEEK(ratings.created_at, 1) = YEARWEEK(CURDATE(), 1)";
        $period_label = "This Week";
        break;
    case 'monthly':
        $date_condition = "MONTH(requests.created_at) = MONTH(CURDATE()) AND YEAR(requests.created_at) = YEAR(CURDATE())";
        $rating_date_condition = "MONTH(ratings.created_at) = MONTH(CURDATE()) AND YEAR(ratings.created_at) = YEAR(CURDATE())";
        $period_label = "This Month";
        break;
    case 'all':
    default:
        $date_condition = "1";
        $rating_date_condition = "1";
        $period_label = "All Time";
        break;
}

$status = $_GET['status'] ?? 'all';
switch ($status) {
    case 'pending':
    case 'in_progress':
    case 'completed':
    case 'conflict':
        $status_condition = "requests.status = '$status'";
        $status_label = ucfirst($status);
        break;
    case 'all':
    default:
        $status_condition = "1";
        $status_label = "All Statuses";
        break;
}

$issue = $_GET['issue'] ?? 'all';
$valid_issues = ['all', 'plumbing', 'electrical', 'furniture'];
if (!in_array($issue, $valid_issues)) {
    $issue = 'all';
}
if ($issue == 'all') {
    $issue_condition = "1";
    $issue_label = "All Issues";
} else {
    switch ($issue) {
        case 'plumbing':   $issue_id = 1; break;
        case 'electrical': $issue_id = 2; break;
        case 'furniture':  $issue_id = 3; break;
    }
    $issue_condition = "requests.issue_type_id = $issue_id";
    $issue_label = ucfirst($issue);
}

$detail_condition = "$date_condition AND $status_condition AND $issue_condition";

/* ===============================
   FETCH REPORT DATA
================================*/
$detail_total     = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE $detail_condition")->fetch_assoc()['total'];
$detail_pending   = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE requests.status = 'pending'    AND $detail_condition")->fetch_assoc()['total'];
$detail_progress  = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE requests.status = 'in_progress' AND $detail_condition")->fetch_assoc()['total'];
$detail_completed = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE requests.status = 'completed'  AND $detail_condition")->fetch_assoc()['total'];
$detail_conflict  = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE requests.status = 'conflict'   AND $detail_condition")->fetch_assoc()['total'];

$plumbing   = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE issue_type_id = 1 AND $date_condition AND $status_condition")->fetch_assoc()['total'];
$electrical = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE issue_type_id = 2 AND $date_condition AND $status_condition")->fetch_assoc()['total'];
$furniture  = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE issue_type_id = 3 AND $date_condition AND $status_condition")->fetch_assoc()['total'];

$tech_workload = $conn->query("
    SELECT users.name, COUNT(requests.id) AS total_jobs
    FROM staff
    JOIN users ON staff.user_id = users.id
    LEFT JOIN requests ON staff.id = requests.assigned_staff AND $date_condition AND $status_condition AND $issue_condition
    GROUP BY staff.id
");

$perf_sql = "
    SELECT 
        users.name,
        staff.specialization,
        COUNT(ratings.id) AS total_ratings,
        ROUND(AVG(ratings.rating), 2) AS avg_rating
    FROM staff
    JOIN users ON staff.user_id = users.id
    LEFT JOIN ratings ON staff.id = ratings.staff_id AND $rating_date_condition
    GROUP BY staff.id, users.name, staff.specialization
    ORDER BY avg_rating DESC
";
$perf_result = $conn->query($perf_sql);
$overall_sum = 0;
$overall_count = 0;

$detail_requests = $conn->query("
    SELECT requests.id, users.name AS student_name, requests.hostel, requests.room,
           requests.available_time, requests.status
    FROM requests
    JOIN users ON requests.student_id = users.id
    WHERE $detail_condition
    ORDER BY requests.created_at DESC
    LIMIT 100
");

/* ===============================
   BUILD HTML FOR THE PDF
================================*/
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>HIT Maintenance Report</title>
    <style>
        body { font-family: "DejaVu Sans", Arial, sans-serif; color: #333; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #003366; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #003366; margin: 0; font-size: 24px; }
        .header p { color: #666; margin: 5px 0 0; }
        .filter-info { margin-bottom: 15px; font-size: 14px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px; }
        th { background-color: #003366; color: white; padding: 8px; text-align: left; }
        td { border: 1px solid #ccc; padding: 6px 8px; }
        .summary-table td { text-align: center; font-weight: bold; font-size: 16px; }
        .summary-table th { text-align: center; }
        h3 { color: #003366; margin-top: 25px; margin-bottom: 10px; }
        .overall { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Harare Institute of Technology</h1>
        <p>Hostel Maintenance Report</p>
    </div>

    <div class="filter-info">
        <strong>Filters:</strong> Period: ' . $period_label . ' | Status: ' . $status_label . ' | Issue Type: ' . $issue_label . '
    </div>

    <!-- Summary Statistics -->
    <h3>Summary Statistics</h3>
    <table class="summary-table">
        <tr>
            <th>Total</th>
            <th>Pending</th>
            <th>In Progress</th>
            <th>Completed</th>
            <th>Conflict</th>
        </tr>
        <tr>
            <td>' . $detail_total . '</td>
            <td>' . $detail_pending . '</td>
            <td>' . $detail_progress . '</td>
            <td>' . $detail_completed . '</td>
            <td>' . $detail_conflict . '</td>
        </tr>
    </table>

    <!-- Issue Type Breakdown -->
    <h3>Requests by Issue Type</h3>
    <table>
        <tr><th>Issue Type</th><th>Count</th></tr>
        <tr><td>Plumbing</td><td>' . $plumbing . '</td></tr>
        <tr><td>Electrical</td><td>' . $electrical . '</td></tr>
        <tr><td>Furniture</td><td>' . $furniture . '</td></tr>
    </table>

    <!-- Technician Workload -->
    <h3>Technician Workload</h3>
    <table>
        <tr><th>Technician</th><th>Total Jobs</th></tr>';

if ($tech_workload->num_rows > 0) {
    while ($row = $tech_workload->fetch_assoc()) {
        $html .= '<tr><td>' . htmlspecialchars($row['name']) . '</td><td>' . $row['total_jobs'] . '</td></tr>';
    }
} else {
    $html .= '<tr><td colspan="2">No workload data</td></tr>';
}

$html .= '</table>';

// Technician Performance
$html .= '<h3>Technician Performance (Ratings)</h3>';
$html .= '<table>
    <tr><th>Technician</th><th>Trade</th><th>Total Ratings</th><th>Average Rating</th></tr>';

if ($perf_result && $perf_result->num_rows > 0) {
    while ($row = $perf_result->fetch_assoc()) {
        $name = htmlspecialchars($row['name']);
        $trade = ucfirst($row['specialization']);
        $avg = $row['avg_rating'] ?? '—';
        $total_ratings = $row['total_ratings'];
        if ($total_ratings > 0) {
            $overall_sum += $avg * $total_ratings;
            $overall_count += $total_ratings;
        }
        $html .= '<tr>
            <td>' . $name . '</td>
            <td>' . $trade . '</td>
            <td>' . $total_ratings . '</td>
            <td>' . (($avg !== '—') ? $avg . ' / 10' : 'No ratings') . '</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="4">No performance data</td></tr>';
}
$html .= '</table>';

if ($overall_count > 0) {
    $overall_avg = round($overall_sum / $overall_count, 2);
    $html .= '<p class="overall">Overall Average Rating: ' . $overall_avg . ' / 10 (from ' . $overall_count . ' rating' . ($overall_count > 1 ? 's' : '') . ')</p>';
}

// Detailed Request List
$html .= '<h3>Request Details (' . $issue_label . ')</h3>
<table>
    <tr><th>ID</th><th>Student</th><th>Hostel</th><th>Room</th><th>Available Time</th><th>Status</th></tr>';

if ($detail_requests && $detail_requests->num_rows > 0) {
    while ($r = $detail_requests->fetch_assoc()) {
        $html .= '<tr>
            <td>#' . $r['id'] . '</td>
            <td>' . htmlspecialchars($r['student_name']) . '</td>
            <td>' . htmlspecialchars($r['hostel']) . '</td>
            <td>' . htmlspecialchars($r['room']) . '</td>
            <td>' . date('d M Y H:i', strtotime($r['available_time'])) . '</td>
            <td>' . ucfirst($r['status']) . '</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="6">No requests found</td></tr>';
}
$html .= '</table>';

$html .= '</body></html>';

/* ===============================
   GENERATE PDF
================================*/
$options = new Options();
$options->set('isRemoteEnabled', false);  // adjust if remote images needed
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output the PDF as a download
$dompdf->stream("HIT_Maintenance_Report.pdf", ["Attachment" => 1]);
exit();