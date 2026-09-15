<?php

require_once "../config/auth.php";
require_librarian();

require_once "../config/database.php";
require_once "../config/activity_logs.php";

$message = "";
$message_type = "";


// =========================
// Get Books
// =========================

$books = $conn->query(
    "SELECT id, book_name, author
     FROM books
     ORDER BY book_name ASC"
);

if (!$books) {

    error_log(
        "Issue book - books query failed: " .
        $conn->error
    );

    die(
        "Unable to load books. Please try again later."
    );
}


// =========================
// Get Students
// =========================

$students = $conn->query(
    "SELECT id, name, course
     FROM students
     ORDER BY name ASC"
);

if (!$students) {

    error_log(
        "Issue book - students query failed: " .
        $conn->error
    );

    die(
        "Unable to load students. Please try again later."
    );
}


// =========================
// Issue Book
// =========================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $book_id = $_POST["book_id"] ?? "";
    $student_id = $_POST["student_id"] ?? "";
    $issue_date = trim($_POST["issue_date"] ?? "");
    $due_date = trim($_POST["due_date"] ?? "");


    // =========================
    // Validate IDs
    // =========================

    if (
        $book_id === "" ||
        !ctype_digit((string)$book_id) ||
        (int)$book_id <= 0
    ) {

        $message = "Please select a valid book.";
        $message_type = "error";

    } elseif (
        $student_id === "" ||
        !ctype_digit((string)$student_id) ||
        (int)$student_id <= 0
    ) {

        $message = "Please select a valid student.";
        $message_type = "error";

    } elseif ($issue_date === "" || $due_date === "") {

        $message = "Please select issue date and due date.";
        $message_type = "error";

    } elseif (
        !preg_match(
            "/^\d{4}-\d{2}-\d{2}$/",
            $issue_date
        ) ||
        !preg_match(
            "/^\d{4}-\d{2}-\d{2}$/",
            $due_date
        )
    ) {

        $message = "Invalid date format.";
        $message_type = "error";

    } else {

        $book_id = (int)$book_id;
        $student_id = (int)$student_id;


        // =========================
        // Validate Actual Dates
        // =========================

        $issue_date_obj =
            DateTime::createFromFormat(
                "Y-m-d",
                $issue_date
            );

        $due_date_obj =
            DateTime::createFromFormat(
                "Y-m-d",
                $due_date
            );

        if (
            !$issue_date_obj ||
            $issue_date_obj->format("Y-m-d")
                !== $issue_date ||
            !$due_date_obj ||
            $due_date_obj->format("Y-m-d")
                !== $due_date
        ) {

            $message = "Please enter valid dates.";
            $message_type = "error";

        } elseif ($due_date < $issue_date) {

            $message =
                "Due date cannot be before issue date.";

            $message_type = "error";

        } else {


            // =========================
            // Check Book Exists
            // =========================

            $book_stmt = $conn->prepare(
                "SELECT id, book_name
                 FROM books
                 WHERE id = ?
                 LIMIT 1"
            );

            if (!$book_stmt) {

                error_log(
                    "Issue book - book SELECT prepare failed: " .
                    $conn->error
                );

                $message =
                    "Something went wrong. Please try again.";

                $message_type = "error";

            } else {

                $book_stmt->bind_param(
                    "i",
                    $book_id
                );

                $book_stmt->execute();

                $book_result =
                    $book_stmt->get_result();

                $book = null;

                if (
                    $book_result &&
                    $book_result->num_rows === 1
                ) {
                    $book =
                        $book_result->fetch_assoc();
                }

                $book_stmt->close();


                if (!$book) {

                    $message = "Selected book not found.";
                    $message_type = "error";

                } else {


                    // =========================
                    // Check Student Exists
                    // =========================

                    $student_stmt = $conn->prepare(
                        "SELECT id, name
                         FROM students
                         WHERE id = ?
                         LIMIT 1"
                    );

                    if (!$student_stmt) {

                        error_log(
                            "Issue book - student SELECT prepare failed: " .
                            $conn->error
                        );

                        $message =
                            "Something went wrong. Please try again.";

                        $message_type = "error";

                    } else {

                        $student_stmt->bind_param(
                            "i",
                            $student_id
                        );

                        $student_stmt->execute();

                        $student_result =
                            $student_stmt->get_result();

                        $student = null;

                        if (
                            $student_result &&
                            $student_result->num_rows === 1
                        ) {
                            $student =
                                $student_result->fetch_assoc();
                        }

                        $student_stmt->close();


                        if (!$student) {

                            $message =
                                "Selected student not found.";

                            $message_type = "error";

                        } else {


                            // =========================
                            // Check Book Availability
                            // =========================

                            $availability_stmt =
                                $conn->prepare(
                                    "SELECT id
                                     FROM issued_books
                                     WHERE book_id = ?
                                       AND return_date IS NULL
                                     LIMIT 1"
                                );

                            if (!$availability_stmt) {

                                error_log(
                                    "Issue book - availability prepare failed: " .
                                    $conn->error
                                );

                                $message =
                                    "Something went wrong. Please try again.";

                                $message_type = "error";

                            } else {

                                $availability_stmt->bind_param(
                                    "i",
                                    $book_id
                                );

                                $availability_stmt->execute();

                                $availability_result =
                                    $availability_stmt->get_result();

                                $already_issued =
                                    $availability_result &&
                                    $availability_result->num_rows > 0;

                                $availability_stmt->close();


                                if ($already_issued) {

                                    $message =
                                        "This book is currently issued to another student.";

                                    $message_type = "error";

                                } else {


                                    // =========================
                                    // Insert Issued Book
                                    // =========================

                                    $stmt = $conn->prepare(
                                        "INSERT INTO issued_books
                                        (book_id, student_id, issue_date, due_date)
                                        VALUES (?, ?, ?, ?)"
                                    );

                                    if (!$stmt) {

                                        error_log(
                                            "Issue book INSERT prepare failed: " .
                                            $conn->error
                                        );

                                        $message =
                                            "Unable to issue book. Please try again.";

                                        $message_type = "error";

                                    } else {

                                        $stmt->bind_param(
                                            "iiss",
                                            $book_id,
                                            $student_id,
                                            $issue_date,
                                            $due_date
                                        );


                                        if ($stmt->execute()) {


                                            // =========================
                                            // Get Logged-in User
                                            // =========================

                                            $username =
                                                $_SESSION["username"]
                                                ?? "";

                                            if ($username !== "") {

                                                $user_stmt =
                                                    $conn->prepare(
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
                                                            "ISSUE_BOOK",
                                                            "Issued book: " .
                                                            $book["book_name"] .
                                                            " to student: " .
                                                            $student["name"] .
                                                            " | Issue Date: " .
                                                            $issue_date .
                                                            " | Due Date: " .
                                                            $due_date
                                                        );
                                                    }

                                                    $user_stmt->close();
                                                }
                                            }


                                            $message =
                                                "Book issued successfully!";

                                            $message_type = "success";

                                        } else {

                                            error_log(
                                                "Issue book INSERT failed: " .
                                                $stmt->error
                                            );

                                            $message =
                                                "Unable to issue book. Please try again.";

                                            $message_type = "error";
                                        }

                                        $stmt->close();
                                    }
                                }
                            }
                        }
                    }
                }
            }
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
        Issue Book - Library Management System
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

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
                Issue Book
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
                    Issue Book
                </h2>

                <p>
                    Issue a book to a registered student
                </p>

            </div>

        </div>


        <!-- MESSAGE -->

        <?php if ($message !== "") { ?>

            <div
                class="<?php
                    echo $message_type === "success"
                        ? "success-message"
                        : "error-message";
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


        <!-- FORM -->

        <form
            method="POST"
            class="library-form"
        >


            <!-- BOOK -->

            <label>
                Select Book
            </label>

            <select
                name="book_id"
                required
            >

                <option value="">
                    -- Select Book --
                </option>


                <?php if ($books->num_rows > 0) { ?>

                    <?php while ($book_option = $books->fetch_assoc()) { ?>

                        <option
                            value="<?php
                                echo (int)$book_option["id"];
                            ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                $book_option["book_name"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>

                            -

                            <?php
                            echo htmlspecialchars(
                                $book_option["author"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>

                        </option>

                    <?php } ?>

                <?php } ?>

            </select>


            <!-- STUDENT -->

            <label>
                Select Student
            </label>

            <select
                name="student_id"
                required
            >

                <option value="">
                    -- Select Student --
                </option>


                <?php if ($students->num_rows > 0) { ?>

                    <?php while ($student_option = $students->fetch_assoc()) { ?>

                        <option
                            value="<?php
                                echo (int)$student_option["id"];
                            ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                $student_option["name"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>

                            -

                            <?php
                            echo htmlspecialchars(
                                $student_option["course"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>

                        </option>

                    <?php } ?>

                <?php } ?>

            </select>


            <!-- ISSUE DATE -->

            <label>
                Issue Date
            </label>

            <input
                type="date"
                name="issue_date"
                value="<?php echo date("Y-m-d"); ?>"
                required
            >


            <!-- DUE DATE -->

            <label>
                Due Date
            </label>

            <input
                type="date"
                name="due_date"
                required
            >


            <!-- SUBMIT -->

            <button
                type="submit"
                class="submit-btn"
            >
                📚 Issue Book
            </button>

        </form>


        <!-- LINKS -->

        <div class="page-links">

            <a
                class="add-btn"
                href="issued_books.php"
            >
                View Issued Books
            </a>


            <a
                class="back-btn"
                href="../dashboard.php"
            >
                ← Back to Dashboard
            </a>

        </div>

    </div>

</div>

</body>

</html>