<?php

session_start();

require_once "config/database.php";
require_once "config/auth.php";
require_once "config/notifications.php";

require_login();

$username = $_SESSION["username"];

/* Get logged-in user ID */
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

$user_id = $user["id"];

/* Mark notification as read */
if (isset($_GET["read"])) {

    $notification_id = (int) $_GET["read"];

    mark_notification_read(
        $conn,
        $notification_id,
        $user_id
    );

    header("Location: notifications.php");
    exit;
}

/* Get all notifications */
$stmt = $conn->prepare("
    SELECT *
    FROM notifications
    WHERE user_id = ?
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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifications - Library Management System</title>

    <link rel="stylesheet" href="css/style.css">

    <style>

        .notification-page {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 25px;
        }

        .notification-header {
            margin-bottom: 25px;
        }

        .notification-header span {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #64748b;
        }

        .notification-header h1 {
            margin: 8px 0;
            color: #0f172a;
        }

        .notification-header p {
            color: #64748b;
        }

        .notification-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .notification-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.08);
            border-left: 5px solid #3b82f6;
        }

        .notification-card.unread {
            background: #eff6ff;
        }

        .notification-content {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notification-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: #dbeafe;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .notification-message {
            font-weight: 600;
            color: #1e293b;
        }

        .notification-time {
            margin-top: 5px;
            font-size: 13px;
            color: #64748b;
        }

        .read-button {
            text-decoration: none;
            background: #2563eb;
            color: white;
            padding: 9px 15px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
        }

        .read-button:hover {
            background: #1d4ed8;
        }

        .empty-notifications {
            background: white;
            padding: 50px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.08);
        }

        .empty-notifications div {
            font-size: 45px;
            margin-bottom: 10px;
        }

        .empty-notifications h2 {
            color: #1e293b;
        }

        .empty-notifications p {
            color: #64748b;
        }

    </style>

</head>

<body>

<div class="notification-page">

    <div class="notification-header">

        <span>LIBRARY MANAGEMENT SYSTEM</span>

        <h1>🔔 Notifications</h1>

        <p>
            Stay updated with your library activities.
        </p>

    </div>


    <div class="notification-list">

        <?php if (count($notifications) > 0): ?>

            <?php foreach ($notifications as $notification): ?>

                <div class="notification-card
                    <?= $notification["is_read"] == 0 ? "unread" : "" ?>">

                    <div class="notification-content">

                        <div class="notification-icon">
                            🔔
                        </div>

                        <div>

                            <div class="notification-message">
                                <?= htmlspecialchars($notification["message"]) ?>
                            </div>

                            <div class="notification-time">
                                <?= htmlspecialchars($notification["created_at"]) ?>
                            </div>

                        </div>

                    </div>


                    <?php if ($notification["is_read"] == 0): ?>

                        <a
                            href="notifications.php?read=<?= $notification["id"] ?>"
                            class="read-button"
                        >
                            Mark as Read
                        </a>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty-notifications">

                <div>🔔</div>

                <h2>No Notifications</h2>

                <p>
                    You don't have any notifications right now.
                </p>

            </div>

        <?php endif; ?>

    </div>

</div>

</body>

</html>