<?php

// =========================
// SESSION SECURITY
// =========================

session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax"
]);

session_start();

require_once "config/database.php";
require_once "config/activity_logs.php";

$message = "";


// =========================
// LOGIN
// =========================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";


    // =========================
    // INPUT VALIDATION
    // =========================

    if ($username === "" || $password === "") {

        $message = "Please enter username and password.";

    } elseif (strlen($username) > 100) {

        $message = "Invalid username or password.";

    } elseif (strlen($password) > 255) {

        $message = "Invalid username or password.";

    } else {

        // =========================
        // GET USER
        // =========================

        $stmt = $conn->prepare(
            "SELECT id, username, password, role
             FROM users
             WHERE username = ?
             LIMIT 1"
        );


        if (!$stmt) {

            $message = "Something went wrong. Please try again.";

        } else {

            $stmt->bind_param(
                "s",
                $username
            );

            $stmt->execute();

            $result = $stmt->get_result();


            // =========================
            // CHECK USER
            // =========================

            if ($result && $result->num_rows === 1) {

                $user = $result->fetch_assoc();


                // =========================
                // CHECK PASSWORD
                // =========================

                if (
                    password_verify(
                        $password,
                        $user["password"]
                    )
                ) {

                    // =========================
                    // SESSION ID REGENERATION
                    // =========================

                    session_regenerate_id(true);


                    // =========================
                    // STORE SESSION DATA
                    // =========================

                    $_SESSION["username"] = $user["username"];
                    $_SESSION["role"] = $user["role"];


                    // =========================
                    // ACTIVITY LOG
                    // =========================

                    log_activity(
                        $conn,
                        $user["id"],
                        $user["username"],
                        "LOGIN",
                        "User logged into the system"
                    );


                    // =========================
                    // ROLE REDIRECT
                    // =========================

                    if ($user["role"] === "admin") {

                        header(
                            "Location: admin/index.php"
                        );

                        exit();

                    } elseif (
                        $user["role"] === "librarian"
                    ) {

                        header(
                            "Location: dashboard.php"
                        );

                        exit();

                    } else {

                        header(
                            "Location: dashboard.php"
                        );

                        exit();
                    }

                } else {

                    $message =
                        "Invalid username or password.";
                }

            } else {

                $message =
                    "Invalid username or password.";
            }


            $stmt->close();
        }
    }
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
        Login - Library Management System
    </title>

    <link
        rel="stylesheet"
        href="css/login.css"
    >

</head>

<body>

<div class="login-container">

    <div class="login-card">

        <div class="library-logo">
            📚
        </div>

        <div class="welcome-text">
            <span>WELCOME BACK</span>
        </div>

        <h1>
            Library Management
        </h1>

        <h2>
            Sign In
        </h2>

        <p class="login-subtitle">
            Access your library dashboard
        </p>


        <?php if ($message !== "") { ?>

            <div class="error-message">

                <span class="error-icon">
                    !
                </span>

                <?php
                echo htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>

            </div>

        <?php } ?>


        <form method="POST">

            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <div class="input-wrapper">

                    <span class="input-icon">
                        👤
                    </span>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter your username"
                        autocomplete="username"
                        maxlength="100"
                        required
                    >

                </div>

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <div class="input-wrapper">

                    <span class="input-icon">
                        🔒
                    </span>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        maxlength="255"
                        required
                    >

                </div>

            </div>


            <button
                type="submit"
                class="login-btn"
            >

                <span>
                    Sign In
                </span>

                <span class="arrow">
                    →
                </span>

            </button>

        </form>


        <div class="register-link">

            <span>
                New to the library?
            </span>

            <a href="register.php">
                Create Account
            </a>

        </div>


        <div class="login-footer">

            <span>
                📖
            </span>

            <span>
                Your knowledge, your library.
            </span>

        </div>

    </div>

</div>

</body>

</html>