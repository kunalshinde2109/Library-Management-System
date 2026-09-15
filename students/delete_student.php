<?php

require_once "../config/auth.php";
require_librarian();

require_once "../config/database.php";
require_once "../config/activity_logs.php";

$message = "";
$message_type = "";


// =========================
// Validate Student ID
// =========================

$id = $_GET["id"] ?? "";

if (
    $id === "" ||
    !ctype_digit((string)$id) ||
    (int)$id <= 0
) {
    $message = "Invalid student ID.";
    $message_type = "error";
} else {

    $id = (int)$id;


    // =========================
    // Get Student Information
    // =========================

    $stmt = $conn->prepare(
        "SELECT name
         FROM students
         WHERE id = ?
         LIMIT 1"
    );

    if (!$stmt) {

        error_log(
            "Delete student SELECT prepare failed: " .
            $conn->error
        );

        $message =
            "Something went wrong. Please try again later.";

        $message_type = "error";

    } else {

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();

        if (!$result || $result->num_rows !== 1) {

            $message = "Student not found.";
            $message_type = "error";

        } else {

            $student = $result->fetch_assoc();
            $student_name = $student["name"];


            // =========================
            // Delete Student
            // =========================

            $delete_stmt = $conn->prepare(
                "DELETE FROM students
                 WHERE id = ?"
            );

            if (!$delete_stmt) {

                error_log(
                    "Delete student DELETE prepare failed: " .
                    $conn->error
                );

                $message =
                    "Unable to delete student. Please try again.";

                $message_type = "error";

            } else {

                $delete_stmt->bind_param("i", $id);


                if ($delete_stmt->execute()) {

                    if ($delete_stmt->affected_rows === 1) {


                        // =========================
                        // Get Logged-in User
                        // =========================

                        $username =
                            $_SESSION["username"] ?? "";

                        if ($username !== "") {

                            $user_stmt = $conn->prepare(
                                "SELECT id, username
                                 FROM users
                                 WHERE username = ?
                                 LIMIT 1"
                            );

                            if ($user_stmt) {

                                $user_stmt->bind_param(
                                    "s",
                                    $username
                                );

                                $user_stmt->execute();

                                $user_result =
                                    $user_stmt->get_result();


                                if (
                                    $user_result &&
                                    $user_result->num_rows === 1
                                ) {

                                    $user =
                                        $user_result->fetch_assoc();


                                    // =========================
                                    // Activity Log
                                    // =========================

                                    log_activity(
                                        $conn,
                                        $user["id"],
                                        $user["username"],
                                        "DELETE_STUDENT",
                                        "Deleted student: " .
                                        $student_name
                                    );
                                }

                                $user_stmt->close();
                            }
                        }


                        $message =
                            "Student deleted successfully!";

                        $message_type = "success";

                    } else {

                        $message = "Student not found.";
                        $message_type = "error";
                    }

                } else {

                    error_log(
                        "Delete student execution failed: " .
                        $delete_stmt->error
                    );

                    $message =
                        "Unable to delete student. Please try again.";

                    $message_type = "error";
                }

                $delete_stmt->close();
            }
        }

        $stmt->close();
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
        Delete Student - Library Management System
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>

<body>

<div class="page-container">

    <div class="page-header">

        <div>

            <h1>
                Library Management System
            </h1>

            <p>
                Students Management
            </p>

        </div>

        <a
            href="../logout.php"
            class="logout-btn"
        >
            Logout
        </a>

    </div>


    <div class="content-card">

        <div class="content-title">

            <div>

                <h2>
                    Delete Student
                </h2>

                <p>
                    Student record operation
                </p>

            </div>

        </div>


        <div class="form-message <?php
            echo htmlspecialchars(
                $message_type,
                ENT_QUOTES,
                "UTF-8"
            );
        ?>">

            <?php
            echo htmlspecialchars(
                $message,
                ENT_QUOTES,
                "UTF-8"
            );
            ?>

        </div>


        <div class="form-actions">

            <a
                href="view_student.php"
                class="add-btn"
            >
                👨‍🎓 Back to Students
            </a>

        </div>

    </div>

</div>

</body>

</html>