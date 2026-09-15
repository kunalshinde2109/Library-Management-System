<?php

function log_activity($conn, $user_id, $username, $action, $description)
{
    $stmt = $conn->prepare("
        INSERT INTO activity_logs
        (user_id, username, action, description)
        VALUES (?, ?, ?, ?)
    ");

    if (!$stmt) {
        return;
    }

    $stmt->bind_param(
        "isss",
        $user_id,
        $username,
        $action,
        $description
    );

    $stmt->execute();

    $stmt->close();
}

?>