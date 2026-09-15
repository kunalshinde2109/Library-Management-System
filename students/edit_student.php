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
    header("Location: view_student.php");
    exit();
}

$id = (int)$id;


// =========================
// Get Student
// =========================

$stmt = $conn->prepare(
    "SELECT id, name, email, phone, course
     FROM students
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {

    error_log(
        "Edit student SELECT prepare failed: " .
        $conn->error
    );

    die("Unable to load student. Please try again later.");
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if (!$result || $result->num_rows !== 1) {

    $stmt->close();

    header("Location: view_student.php");
    exit();
}

$student = $result->fetch_assoc();

$stmt->close();


// =========================
// Update Student
// =========================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $course = trim($_POST["course"] ?? "");


    // =========================
    // Input Validation
    // =========================

    if ($name === "" || $email === "") {

        $message = "Name and Email are required.";
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
        // Update Query
        // =========================

        $stmt = $conn->prepare(
            "UPDATE students
             SET name = ?,
                 email = ?,
                 phone = ?,
                 course = ?
             WHERE id = ?"
        );

        if (!$stmt) {

            error_log(
                "Edit student UPDATE prepare failed: " .
                $conn->error
            );

            $message =
                "Something went wrong. Please try again.";

            $message_type = "error";

        } else {

            $stmt->bind_param(
                "ssssi",
                $name,
                $email,
                $phone,
                $course,
                $id
            );


            if ($stmt->execute()) {

                $message =
                    "Student updated successfully!";

                $message_type = "success";


                // Update displayed values
                $student["name"] = $name;
                $student["email"] = $email;
                $student["phone"] = $phone;
                $student["course"] = $course;


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
                                "EDIT_STUDENT",
                                "Updated student: " . $name
                            );
                        }

                        $user_stmt->close();
                    }
                }

            } else {

                error_log(
                    "Edit student UPDATE failed: " .
                    $stmt->error
                );

                $message =
                    "Unable to update student. Please try again.";

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
        Edit Student - Library Management System
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>

<body>

<div class="page-container">


    <!-- PAGE HEADER -->

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


    <!-- EDIT STUDENT -->

    <div class="content-card">

        <div class="content-title">

            <div>

                <h2>
                    Edit Student
                </h2>

                <p>
                    Update the details of this student.
                </p>

            </div>

        </div>


        <!-- Student ID -->

        <div class="student-id">

            Student ID #

            <strong>
                <?php
                echo (int)$student["id"];
                ?>
            </strong>

        </div>


        <!-- Message -->

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


        <!-- Form -->

        <form
            method="POST"
            class="student-form"
        >


            <!-- Name -->

            <div class="form-group">

                <label>
                    Name
                </label>

                <input
                    type="text"
                    name="name"
                    value="<?php
                        echo htmlspecialchars(
                            $student["name"],
                            ENT_QUOTES,
                            "UTF-8"
                        );
                    ?>"
                    placeholder="Enter student name"
                    maxlength="255"
                    required
                >

            </div>


            <!-- Email -->

            <div class="form-group">

                <label>
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    value="<?php
                        echo htmlspecialchars(
                            $student["email"],
                            ENT_QUOTES,
                            "UTF-8"
                        );
                    ?>"
                    placeholder="Enter email address"
                    maxlength="255"
                    required
                >

            </div>


            <!-- Phone -->

            <div class="form-group">

                <label>
                    Phone
                </label>

                <input
                    type="text"
                    name="phone"
                    value="<?php
                        echo htmlspecialchars(
                            $student["phone"],
                            ENT_QUOTES,
                            "UTF-8"
                        );
                    ?>"
                    placeholder="Enter phone number"
                    maxlength="20"
                >

            </div>


            <!-- Course -->

            <div class="form-group">

                <label>
                    Course
                </label>

                <input
                    type="text"
                    name="course"
                    value="<?php
                        echo htmlspecialchars(
                            $student["course"],
                            ENT_QUOTES,
                            "UTF-8"
                        );
                    ?>"
                    placeholder="e.g. BCA, BBA, BCS"
                    maxlength="100"
                >

            </div>


            <!-- Buttons -->

            <div class="form-actions">

                <button
                    type="submit"
                    class="add-btn"
                >
                    ✏️ Update Student
                </button>

                <a
                    href="view_student.php"
                    class="cancel-btn"
                >
                    Cancel
                </a>

            </div>

        </form>


        <!-- Back -->

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