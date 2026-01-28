<?php
if (!defined('NOTIFICATIONS_ENGINE')) {
define('NOTIFICATIONS_ENGINE', true);

/* =====================================================
   CORE: INSERT NOTIFICATION
===================================================== */
function notifyUser(
    mysqli $conn,
    int $userId,
    string $title,
    string $message,
    string $type = 'info',
    string $link = null
) {
    if ($userId <= 0) return;

    $stmt = $conn->prepare("
        INSERT INTO notifications
            (recipient_user_id, title, message, type, link, is_read, created_at)
        VALUES
            (?, ?, ?, ?, ?, 0, NOW())
    ");
    $stmt->bind_param("issss", $userId, $title, $message, $type, $link);
    $stmt->execute();
    $stmt->close();
}

/* =====================================================
   NOTIFY ADMINS
===================================================== */
function notifyAdmins(
    mysqli $conn,
    string $title,
    string $type = 'info',
    string $link = null
) {
    $res = $conn->query("
        SELECT user_id FROM users
        WHERE role='Admin' AND status='Active'
    ");

    while ($u = $res->fetch_assoc()) {
        notifyUser($conn, (int)$u['user_id'], $title, $title, $type, $link);
    }
}

/* =====================================================
   NOTIFY PROJECT MEMBERS (🔥 THIS WAS MISSING)
===================================================== */
function notifyProjectMembers(
    mysqli $conn,
    int $activityId,
    string $message,
    string $type = 'info',
    string $link = null
) {
    $stmt = $conn->prepare("
        SELECT user_id
        FROM activity_users
        WHERE activity_id = ?
    ");
    $stmt->bind_param("i", $activityId);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($u = $res->fetch_assoc()) {
        notifyUser(
            $conn,
            (int)$u['user_id'],
            'Activity Update',
            $message,
            $type,
            $link
        );
    }
    $stmt->close();
}

/* =====================================================
   NOTIFY ACTOR ONLY
===================================================== */
function notifyActorOnly(
    mysqli $conn,
    int $actorId,
    string $message,
    string $type = 'info',
    string $link = null
) {
    notifyUser(
        $conn,
        $actorId,
        'Activity Notification',
        $message,
        $type,
        $link
    );
}

}
