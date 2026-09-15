<?php

require_once "../config/auth.php";
require_admin();

require_once "../config/database.php";


// =========================
// Get Activity Logs
// =========================

$result = $conn->query("
    SELECT
        id,
        username,
        action,
        description,
        created_at
    FROM activity_logs
    ORDER BY created_at DESC
");

if (!$result) {

    error_log(
        "Activity logs query failed: " . $conn->error
    );

    die(
        "Unable to load activity logs. Please try again later."
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Activity Logs - Library Management System
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

    <style>

        .logs-table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        .logs-table th {
            background: #1f2937;
            color: white;
            padding: 14px;
            text-align: left;
        }

        .logs-table td {
            padding: 13px;
            border-bottom: 1px solid #ddd;
            color: #222;
        }

        .logs-table tr:hover {
            background: #f5f5f5;
        }

        .action-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            background: #e5e7eb;
            color: #111827;
        }

        .empty-logs {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 10px;
            color: #555;
            margin-top: 20px;
        }

    </style>

</head>

<body>

<div class="page-container">


    <!-- HEADER -->

    <div class="page-header">

        <div>

            <h1>
                Library Management System
            </h1>

            <p>
                Activity Logs
            </p>

        </div>

        <a
            class="logout-btn"
            href="../logout.php"
        >
            Logout
        </a>

    </div>


    <!-- CONTENT -->

    <div class="content-card">

        <div class="content-title">

            <div>

                <h2>
                    Activity Logs
                </h2>

                <p>
                    View all activities performed in the system
                </p>

            </div>

        </div>


        <!-- LOG TABLE -->

        <?php if ($result->num_rows > 0) { ?>

            <div class="logs-table-container">

                <table class="logs-table">

                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Username
                            </th>

                            <th>
                                Action
                            </th>

                            <th>
                                Description
                            </th>

                            <th>
                                Date & Time
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php while ($log = $result->fetch_assoc()) { ?>

                        <tr>

                            <td>
                                <?php
                                echo (int)$log["id"];
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $log["username"] ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>
                            </td>


                            <td>

                                <span class="action-badge">

                                    <?php
                                    echo htmlspecialchars(
                                        $log["action"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $log["description"] ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $log["created_at"] ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>
                            </td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        <?php } else { ?>

            <div class="empty-logs">
                No activity logs found.
            </div>

        <?php } ?>


        <!-- LINKS -->

        <div class="page-links">

            <a
                class="back-btn"
                href="index.php"
            >
                ← Back to Admin Panel
            </a>

            <a
                class="back-btn"
                href="../dashboard.php"
            >
                ← Dashboard
            </a>

        </div>

    </div>

</div>

</body>

</html>