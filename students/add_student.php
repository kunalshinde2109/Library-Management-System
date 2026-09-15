<?php

require_once "../config/auth.php";
require_librarian();

require_once "../config/database.php";
require_once "../config/activity_logs.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $course = trim($_POST["course"] ?? "");


    // =========================
    // INPUT VALIDATION
    // =========================

    if ($name === "" || $email === "") {

        $message = "Please fill all required fields.";
        $message_type = "error";

    } elseif (strlen($name) > 255) {

        $message = "Student name is too long.";
        $message_type = "error";

    } elseif (
        strlen($email) > 255 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } elseif (strlen($phone) > 20) {

        $message = "Phone number is too long.";
        $message_type = "error";

    } elseif (strlen($course) > 100) {

        $message = "Course name is too long.";
        $message_type = "error";

    } else {


        // =========================
        // INSERT STUDENT
        // =========================

        $stmt = $conn->prepare(
            "INSERT INTO students
            (name, email, phone, course)
            VALUES (?, ?, ?, ?)"
        );

        if (!$stmt) {

            error_log(
                "Add student prepare failed: "
                . $conn->error
            );

            $message =
                "Something went wrong. Please try again.";

            $message_type = "error";

        } else {

            $stmt->bind_param(
                "ssss",
                $name,
                $email,
                $phone,
                $course
            );


            if ($stmt->execute()) {

                $message =
                    "Student added successfully!";

                $message_type = "success";


                // =========================
                // GET LOGGED-IN USER
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
                            // ACTIVITY LOG
                            // =========================

                            log_activity(
                                $conn,
                                $user["id"],
                                $user["username"],
                                "ADD_STUDENT",
                                "Added student: " . $name
                            );
                        }

                        $user_stmt->close();
                    }
                }

            } else {

                error_log(
                    "Add student execute failed: "
                    . $stmt->error
                );

                $message =
                    "Unable to add student. Please try again.";

                $message_type = "error";
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
        Add Student - Library Management System
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
                    Add New Student
                </h2>

                <p>
                    Enter the details of the new student.
                </p>

            </div>

        </div>


        <?php if ($message !== "") { ?>

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

        <?php } ?>


        <form
            method="POST"
            class="student-form"
        >

            <div class="form-group">

                <label>
                    Name
                </label>

                <input
                    type="text"
                    name="name"
                    placeholder="Enter student name"
                    maxlength="255"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter email address"
                    maxlength="255"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Phone
                </label>

                <input
                    type="text"
                    name="phone"
                    placeholder="Enter phone number"
                    maxlength="20"
                >

            </div>


            <div class="form-group">

                <label>
                    Course
                </label>

                <input
                    type="text"
                    name="course"
                    placeholder="e.g. BCA, BBA, BCS"
                    maxlength="100"
                >

            </div>


            <div class="form-actions">

                <button
                    type="submit"
                    class="add-btn"
                >
                    👨‍🎓 Add Student
                </button>

                <a
                    href="view_student.php"
                    class="cancel-btn"
                >
                    Cancel
                </a>

            </div>

        </form>


        <a
            href="view_student.php"
            class="back-btn"
        >
            ← Back to Students
        </a>

    </div>

</div>

</body>

</html>