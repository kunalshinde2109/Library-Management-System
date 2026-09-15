<?php

// =========================
// SESSION START
// =========================

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        "httponly" => true,
        "secure" => !empty($_SERVER["HTTPS"])
            && $_SERVER["HTTPS"] !== "off",
        "samesite" => "Lax"
    ]);

    session_start();
}


// =========================
// CHECK LOGIN
// =========================

function require_login()
{
    if (
        !isset($_SESSION["username"]) ||
        !is_string($_SESSION["username"]) ||
        trim($_SESSION["username"]) === ""
    ) {

        // Clear invalid session
        $_SESSION = [];

        header("Location: ../login.php");
        exit();
    }
}


// =========================
// CHECK USER ROLE
// =========================

function require_role($allowed_roles = [])
{
    require_login();


    // Make sure allowed roles are an array
    if (!is_array($allowed_roles)) {

        header("Location: ../dashboard.php");
        exit();
    }


    // Get current user role
    $user_role = $_SESSION["role"] ?? "";


    // Validate role
    if (
        $user_role === "" ||
        !in_array(
            $user_role,
            $allowed_roles,
            true
        )
    ) {

        header("Location: ../dashboard.php");
        exit();
    }
}


// =========================
// ADMIN ONLY
// =========================

function require_admin()
{
    require_role([
        "admin"
    ]);
}


// =========================
// ADMIN + LIBRARIAN
// =========================

function require_librarian()
{
    require_role([
        "admin",
        "librarian"
    ]);
}

?>