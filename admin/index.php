<?php

require_once "../config/auth.php";
require_admin();

$username = $_SESSION["username"] ?? "";

if (!is_string($username) || trim($username) === "") {
    header("Location: ../login.php");
    exit();
}

$username = trim($username);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Panel - Library Management System</title>

    <link rel="stylesheet" href="../css/style.css">

    <style>

        .admin-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 30px;
        }

        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }

        .admin-header h1 {
            margin: 0 0 8px;
            color: #06233f;
        }

        .admin-header p {
            margin: 0;
            color: #55708c;
        }

        .admin-badge {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 16px;
            border-radius: 20px;
            background: #ffe8a3;
            color: #8a5a00;
            font-weight: 700;
        }

        .admin-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }

        .admin-card h2 {
            color: #06233f;
            margin-top: 0;
        }

        .admin-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-top: 25px;
        }

        .admin-action {
            display: block;
            padding: 22px;
            border-radius: 15px;
            background: #f5f8fc;
            text-decoration: none;
            color: #06233f;
            border: 1px solid #e0e8f0;
            transition: 0.3s;
        }

        .admin-action:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        .admin-action .icon {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .admin-action strong {
            display: block;
            font-size: 17px;
            margin-bottom: 5px;
        }

        .admin-action span {
            font-size: 13px;
            color: #60758a;
        }

        .back-link {
            display: inline-block;
            margin-top: 25px;
            text-decoration: none;
            font-weight: 600;
            color: #0066ff;
        }

        .logout-link {
            float: right;
            background: #ff1744;
            color: white;
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
        }

        @media (max-width: 600px) {

            .admin-container {
                padding: 15px;
            }

            .logout-link {
                float: none;
                display: inline-block;
                margin-top: 15px;
            }

        }

    </style>

</head>

<body>

<div class="admin-container">

    <div class="admin-header">

        <a
            href="../logout.php"
            class="logout-link"
        >
            Logout
        </a>

        <h1>
            👑 Admin Panel
        </h1>

        <p>
            Welcome,
            <strong>
                <?php
                echo htmlspecialchars(
                    $username,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </strong>
        </p>

        <div class="admin-badge">
            🔐 ADMIN ACCESS
        </div>

    </div>


    <div class="admin-card">

        <h2>
            Administration
        </h2>

        <p>
            Manage users and control library system access.
        </p>

        <div class="admin-actions">

            <a
                href="users.php"
                class="admin-action"
            >

                <div class="icon">
                    👥
                </div>

                <strong>
                    Manage Users
                </strong>

                <span>
                    View and manage system users and roles.
                </span>

            </a>


            <a
                href="../dashboard.php"
                class="admin-action"
            >

                <div class="icon">
                    🏠
                </div>

                <strong>
                    Dashboard
                </strong>

                <span>
                    Go back to library dashboard.
                </span>

            </a>


            <a
                href="../reports/index.php"
                class="admin-action"
            >

                <div class="icon">
                    📊
                </div>

                <strong>
                    Reports
                </strong>

                <span>
                    View library reports and analytics.
                </span>

            </a>

        </div>


        <a
            href="../dashboard.php"
            class="back-link"
        >
            ← Back to Dashboard
        </a>

    </div>

</div>

</body>

</html>