<?php

// Start session only if it is not already started
if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        "httponly" => true,
        "secure" => !empty($_SERVER["HTTPS"])
            && $_SERVER["HTTPS"] !== "off",
        "samesite" => "Lax"
    ]);

    session_start();
}

require_once "config/database.php";
require_once "config/activity_logs.php";


// =========================
// LOGOUT ACTIVITY
// =========================

if (
    isset($_SESSION["username"]) &&
    is_string($_SESSION["username"]) &&
    trim($_SESSION["username"]) !== ""
) {

    $username = trim($_SESSION["username"]);

    $stmt = $conn->prepare(
        "SELECT id, username
         FROM users
         WHERE username = ?
         LIMIT 1"
    );

    if ($stmt) {

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {

            $user = $result->fetch_assoc();

            log_activity(
                $conn,
                $user["id"],
                $user["username"],
                "LOGOUT",
                "User logged out of the system"
            );
        }

        $stmt->close();
    }
}


// =========================
// CLEAR SESSION DATA
// =========================

$_SESSION = [];


// =========================
// DELETE SESSION COOKIE
// =========================

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}


// =========================
// DESTROY SESSION
// =========================

session_destroy();


// =========================
// REDIRECT TO LOGIN
// =========================

header("Location: login.php");
exit();

?>