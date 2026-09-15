<?php

function create_notification($conn, $user_id, $message, $type = "info")
{
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, message, type)
        VALUES (?, ?, ?)
    ");

    $stmt->bind_param("iss", $user_id, $message, $type);
    $stmt->execute();
    $stmt->close();
}


function get_unread_notifications($conn, $user_id)
{
    $stmt = $conn->prepare("
        SELECT *
        FROM notifications
        WHERE user_id = ? AND is_read = 0
        ORDER BY created_at DESC
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    $notifications = [];

    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    $stmt->close();

    return $notifications;
}


function get_notification_count($conn, $user_id)
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM notifications
        WHERE user_id = ? AND is_read = 0
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return (int) $row["total"];
}


function mark_notification_read($conn, $notification_id, $user_id)
{
    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE id = ? AND user_id = ?
    ");

    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();

    $stmt->close();
}

?>