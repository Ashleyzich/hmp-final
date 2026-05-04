<?php
/**
 * Insert an in-portal notification.
 *
 * @param mysqli $conn       Database connection
 * @param int    $to_user_id Recipient user ID
 * @param string $message    Notification text
 * @return bool
 */
function createNotification($conn, $to_user_id, $message) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, status, created_at) VALUES (?, ?, 'unread', NOW())");
    if (!$stmt) return false;
    $stmt->bind_param("is", $to_user_id, $message);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}
?>