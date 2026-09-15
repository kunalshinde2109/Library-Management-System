<?php

// =========================
// DATABASE CONFIGURATION
// =========================

$host = "localhost";
$username = "root";
$password = "";
$database = "library_management";


// =========================
// CREATE DATABASE CONNECTION
// =========================

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);


// =========================
// CHECK CONNECTION
// =========================

if ($conn->connect_errno) {

    error_log(
        "Database connection failed: "
        . $conn->connect_error
    );

    die("Unable to connect to the database. Please try again later.");
}


// =========================
// SET CHARACTER SET
// =========================

if (!$conn->set_charset("utf8mb4")) {

    error_log(
        "Failed to set database charset: "
        . $conn->error
    );

    die("Database configuration error. Please try again later.");
}

?>