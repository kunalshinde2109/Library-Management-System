<?php

require_once "../config/auth.php";
require_admin();

require_once "../config/database.php";
require_once "../config/activity_logs.php";

$current_username = trim($_SESSION["username"] ?? "");

if ($current_username === "") {
    header("Location: ../login.php");
    exit();
}

$message = "";
$message_type = "";


// =========================
// Update Role / Delete User
// =========================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";
    $user_id_raw = $_POST["user_id"] ?? "";

    // Strict positive integer validation
    if (
        !is_string($user_id_raw) ||
        !ctype_digit($user_id_raw) ||
        (int)$user_id_raw <= 0
    ) {
        $message = "Invalid user selected.";
        $message_type = "error";
    } else {

        $user_id = (int)$user_id_raw;


        // =========================
        // Change Role
        // =========================

        if ($action === "change_role") {

            $new_role = $_POST["role"] ?? "";

            $allowed_roles = [
                "admin",
                "librarian",
                "student"
            ];

            if (!is_string($new_role) ||
                !in_array($new_role, $allowed_roles, true)
            ) {

                $message = "Invalid role selected.";
                $message_type = "error";

            } else {

                $stmt = $conn->prepare(
                    "SELECT id, username, role
                     FROM users
                     WHERE id = ?
                     LIMIT 1"
                );

                if (!$stmt) {

                    error_log(
                        "Manage users role SELECT prepare failed: "
                        . $conn->error
                    );

                    $message = "Something went wrong. Please try again.";
                    $message_type = "error";

                } else {

                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();

                    $result = $stmt->get_result();
                    $user = $result ? $result->fetch_assoc() : null;

                    $stmt->close();


                    if (!$user) {

                        $message = "User not found.";
                        $message_type = "error";

                    } elseif ($user["username"] === $current_username) {

                        $message = "You cannot change your own role.";
                        $message_type = "error";

                    } else {

                        $stmt = $conn->prepare(
                            "UPDATE users
                             SET role = ?
                             WHERE id = ?
                             LIMIT 1"
                        );

                        if (!$stmt) {

                            error_log(
                                "Manage users role UPDATE prepare failed: "
                                . $conn->error
                            );

                            $message = "Unable to update user role.";
                            $message_type = "error";

                        } else {

                            $stmt->bind_param(
                                "si",
                                $new_role,
                                $user_id
                            );

                            if ($stmt->execute()) {

                                $message =
                                    "User role updated successfully.";

                                $message_type = "success";


                                // Activity Log
                                $description =
                                    "Changed role of user "
                                    . $user["username"]
                                    . " from "
                                    . $user["role"]
                                    . " to "
                                    . $new_role;

                                $log_stmt = $conn->prepare(
                                    "SELECT id, username
                                     FROM users
                                     WHERE username = ?
                                     LIMIT 1"
                                );

                                if ($log_stmt) {

                                    $log_stmt->bind_param(
                                        "s",
                                        $current_username
                                    );

                                    $log_stmt->execute();

                                    $log_result =
                                        $log_stmt->get_result();

                                    $log_user =
                                        $log_result
                                            ? $log_result->fetch_assoc()
                                            : null;

                                    $log_stmt->close();


                                    if ($log_user) {

                                        log_activity(
                                            $conn,
                                            $log_user["id"],
                                            $log_user["username"],
                                            "CHANGE_ROLE",
                                            $description
                                        );
                                    }
                                }

                            } else {

                                error_log(
                                    "Manage users role UPDATE failed: "
                                    . $stmt->error
                                );

                                $message =
                                    "Unable to update user role.";

                                $message_type = "error";
                            }

                            $stmt->close();
                        }
                    }
                }
            }
        }


        // =========================
        // Delete User
        // =========================

        elseif ($action === "delete") {

            $stmt = $conn->prepare(
                "SELECT id, username, email, role
                 FROM users
                 WHERE id = ?
                 LIMIT 1"
            );

            if (!$stmt) {

                error_log(
                    "Manage users DELETE SELECT prepare failed: "
                    . $conn->error
                );

                $message =
                    "Something went wrong. Please try again.";

                $message_type = "error";

            } else {

                $stmt->bind_param("i", $user_id);
                $stmt->execute();

                $result = $stmt->get_result();
                $user = $result ? $result->fetch_assoc() : null;

                $stmt->close();


                if (!$user) {

                    $message = "User not found.";
                    $message_type = "error";

                } elseif ($user["username"] === $current_username) {

                    $message =
                        "You cannot delete your own account.";

                    $message_type = "error";

                } else {

                    $stmt = $conn->prepare(
                        "DELETE FROM users
                         WHERE id = ?
                         LIMIT 1"
                    );

                    if (!$stmt) {

                        error_log(
                            "Manage users DELETE prepare failed: "
                            . $conn->error
                        );

                        $message =
                            "Unable to delete user.";

                        $message_type = "error";

                    } else {

                        $stmt->bind_param("i", $user_id);

                        if ($stmt->execute()) {

                            if ($stmt->affected_rows === 1) {

                                $message =
                                    "User deleted successfully.";

                                $message_type = "success";


                                // Activity Log
                                $log_stmt = $conn->prepare(
                                    "SELECT id, username
                                     FROM users
                                     WHERE username = ?
                                     LIMIT 1"
                                );

                                if ($log_stmt) {

                                    $log_stmt->bind_param(
                                        "s",
                                        $current_username
                                    );

                                    $log_stmt->execute();

                                    $log_result =
                                        $log_stmt->get_result();

                                    $log_user =
                                        $log_result
                                            ? $log_result->fetch_assoc()
                                            : null;

                                    $log_stmt->close();


                                    if ($log_user) {

                                        log_activity(
                                            $conn,
                                            $log_user["id"],
                                            $log_user["username"],
                                            "DELETE_USER",
                                            "Deleted user: "
                                            . $user["username"]
                                            . " ("
                                            . $user["email"]
                                            . ")"
                                        );
                                    }
                                }

                            } else {

                                $message =
                                    "User was not deleted.";

                                $message_type = "error";
                            }

                        } else {

                            error_log(
                                "Manage users DELETE execute failed: "
                                . $stmt->error
                            );

                            $message =
                                "Unable to delete user.";

                            $message_type = "error";
                        }

                        $stmt->close();
                    }
                }
            }
        }

        else {

            $message = "Invalid action.";
            $message_type = "error";
        }
    }
}


// =========================
// Get Users
// =========================

$users = $conn->query(
    "SELECT id, username, email, role
     FROM users
     ORDER BY id ASC"
);

if (!$users) {

    error_log(
        "Manage users list query failed: "
        . $conn->error
    );

    die(
        "Unable to load users. Please try again later."
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
        Manage Users - Library Management System
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

    <style>

        .admin-page {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .admin-header {
            background: rgba(255,255,255,0.95);
            padding: 25px 30px;
            border-radius: 20px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            box-shadow: 0 10px 30px rgba(0,0,0,0.12);

            margin-bottom: 25px;
        }

        .admin-header h1 {
            margin: 0;
            color: #062d4f;
        }

        .admin-header p {
            margin: 6px 0 0;
            color: #55708a;
        }

        .logout-btn {
            background: #ff003c;
            color: white;

            padding: 12px 22px;

            border-radius: 10px;

            text-decoration: none;

            font-weight: bold;
        }

        .users-card {
            background: rgba(255,255,255,0.96);

            padding: 30px;

            border-radius: 20px;

            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }

        .users-title {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;
        }

        .users-title h2 {
            margin: 0;

            color: #062d4f;
        }

        .user-count {
            background: #e8f1ff;

            color: #0866ff;

            padding: 8px 15px;

            border-radius: 20px;

            font-weight: bold;
        }

        .message {
            padding: 12px 16px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-weight: bold;
        }

        .message.success {
            background: #d9fbe9;

            color: #087a43;
        }

        .message.error {
            background: #ffe1e6;

            color: #c7002b;
        }

        .users-table-wrapper {
            overflow-x: auto;
        }

        .users-table {
            width: 100%;

            border-collapse: collapse;
        }

        .users-table th {
            background: #062d4f;

            color: white;

            padding: 15px;

            text-align: left;
        }

        .users-table td {
            padding: 15px;

            border-bottom: 1px solid #dce5ed;

            color: #123;
        }

        .users-table tr:hover {
            background: #f5f9fd;
        }

        .role-form {
            display: flex;

            align-items: center;

            gap: 8px;
        }

        .role-select {
            padding: 8px 12px;

            border: 1px solid #ccd8e2;

            border-radius: 8px;

            background: white;

            color: #123;

            font-weight: bold;

            cursor: pointer;
        }

        .update-btn {
            border: none;

            background: #0866ff;

            color: white;

            padding: 8px 12px;

            border-radius: 8px;

            cursor: pointer;

            font-weight: bold;
        }

        .update-btn:hover {
            background: #004dcc;
        }

        .delete-btn {
            border: none;

            background: #ff1744;

            color: white;

            padding: 8px 14px;

            border-radius: 8px;

            cursor: pointer;

            font-weight: bold;
        }

        .delete-btn:hover {
            background: #d50032;
        }

        .you-badge {
            background: #eef2f7;

            color: #52677a;

            padding: 6px 10px;

            border-radius: 15px;

            font-size: 12px;

            font-weight: bold;
        }

        .page-links {
            margin-top: 25px;

            display: flex;

            gap: 20px;
        }

        .page-links a {
            color: #0066ff;

            text-decoration: none;

            font-weight: bold;
        }

        @media (max-width: 700px) {

            .role-form {
                flex-direction: column;

                align-items: flex-start;
            }

            .admin-header {
                flex-direction: column;

                align-items: flex-start;

                gap: 20px;
            }

        }

    </style>

</head>

<body>

<div class="admin-page">


    <div class="admin-header">

        <div>

            <h1>
                👥 Manage Users
            </h1>

            <p>
                Manage system users and their roles.
            </p>

        </div>

        <a
            href="../logout.php"
            class="logout-btn"
        >
            Logout
        </a>

    </div>


    <div class="users-card">

        <div class="users-title">

            <h2>
                System Users
            </h2>

            <span class="user-count">

                <?php echo $users->num_rows; ?>

                Users

            </span>

        </div>


        <?php if ($message !== "") { ?>

            <div
                class="message <?php
                echo htmlspecialchars(
                    $message_type,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>"
            >

                <?php
                echo htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>

            </div>

        <?php } ?>


        <div class="users-table-wrapper">

            <table class="users-table">

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Username</th>

                        <th>Email</th>

                        <th>Role</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>

                <?php if ($users->num_rows > 0) { ?>

                    <?php while ($user = $users->fetch_assoc()) { ?>

                        <tr>

                            <td>
                                <?php
                                echo (int)$user["id"];
                                ?>
                            </td>


                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $user["username"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                </strong>


                                <?php
                                if (
                                    $user["username"]
                                    === $current_username
                                ) {
                                ?>

                                    <span class="you-badge">
                                        You
                                    </span>

                                <?php } ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $user["email"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                if (
                                    $user["username"]
                                    === $current_username
                                ) {
                                ?>

                                    <span class="you-badge">
                                        Current Account
                                    </span>

                                <?php } else { ?>


                                    <form
                                        method="POST"
                                        class="role-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="change_role"
                                        >

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?php
                                            echo (int)$user["id"];
                                            ?>"
                                        >


                                        <select
                                            name="role"
                                            class="role-select"
                                        >

                                            <option
                                                value="admin"
                                                <?php
                                                echo $user["role"]
                                                    === "admin"
                                                    ? "selected"
                                                    : "";
                                                ?>
                                            >
                                                Admin
                                            </option>


                                            <option
                                                value="librarian"
                                                <?php
                                                echo $user["role"]
                                                    === "librarian"
                                                    ? "selected"
                                                    : "";
                                                ?>
                                            >
                                                Librarian
                                            </option>


                                            <option
                                                value="student"
                                                <?php
                                                echo $user["role"]
                                                    === "student"
                                                    ? "selected"
                                                    : "";
                                                ?>
                                            >
                                                Student
                                            </option>

                                        </select>


                                        <button
                                            type="submit"
                                            class="update-btn"
                                        >
                                            Update
                                        </button>

                                    </form>

                                <?php } ?>

                            </td>


                            <td>

                                <?php
                                if (
                                    $user["username"]
                                    === $current_username
                                ) {
                                ?>

                                    <span class="you-badge">
                                        Current Account
                                    </span>

                                <?php } else { ?>


                                    <form
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm(
                                            'Are you sure you want to delete this user?'
                                        );"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?php
                                            echo (int)$user["id"];
                                            ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="delete-btn"
                                        >
                                            🗑 Delete
                                        </button>

                                    </form>

                                <?php } ?>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>

                        <td
                            colspan="5"
                            style="text-align:center;"
                        >
                            No users found.
                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>


        <div class="page-links">

            <a href="index.php">
                ← Back to Admin Panel
            </a>

            <a href="../dashboard.php">
                🏠 Dashboard
            </a>

        </div>

    </div>

</div>

</body>

</html>