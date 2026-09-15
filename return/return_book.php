<?php

require_once "../config/auth.php";
require_librarian();

require_once "../config/database.php";
require_once "../config/activity_logs.php";

$message = "";
$message_type = "";


// =========================
// Fine Rate
// =========================

$fine_per_day = 10;


// =========================
// Return Book
// =========================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id = $_POST["id"] ?? "";
    $return_date = trim($_POST["return_date"] ?? "");


    // =========================
    // VALIDATION
    // =========================

    if (
        $id === ""
        || !ctype_digit((string) $id)
        || (int) $id <= 0
    ) {

        $message = "Please enter a valid Issued Book ID.";
        $message_type = "error";

    } elseif ($return_date === "") {

        $message = "Please select Return Date.";
        $message_type = "error";

    } else {

        $id = (int) $id;


        // =========================
        // RETURN DATE FORMAT
        // =========================

        $date_object = DateTime::createFromFormat(
            "Y-m-d",
            $return_date
        );

        $date_errors = DateTime::getLastErrors();

        $valid_date = (
            $date_object !== false
            &&
            (
                $date_errors === false
                ||
                (
                    $date_errors["warning_count"] === 0
                    &&
                    $date_errors["error_count"] === 0
                )
            )
            &&
            $date_object->format("Y-m-d") === $return_date
        );


        if (!$valid_date) {

            $message = "Please enter a valid Return Date.";
            $message_type = "error";

        } else {

            // =========================
            // START TRANSACTION
            // =========================

            $conn->begin_transaction();

            try {

                // =========================
                // GET ISSUED BOOK
                // =========================

                $stmt = $conn->prepare("
                    SELECT
                        ib.id,
                        ib.book_id,
                        ib.student_id,
                        ib.issue_date,
                        ib.due_date,
                        ib.return_date,
                        b.book_name,
                        s.name AS student_name
                    FROM issued_books ib
                    INNER JOIN books b
                        ON ib.book_id = b.id
                    INNER JOIN students s
                        ON ib.student_id = s.id
                    WHERE ib.id = ?
                    LIMIT 1
                    FOR UPDATE
                ");


                if (!$stmt) {

                    throw new Exception(
                        "Unable to process return."
                    );
                }


                $stmt->bind_param(
                    "i",
                    $id
                );


                if (!$stmt->execute()) {

                    $stmt->close();

                    throw new Exception(
                        "Unable to process return."
                    );
                }


                $result = $stmt->get_result();


                if (!$result || $result->num_rows !== 1) {

                    $stmt->close();

                    throw new Exception(
                        "Issued Book ID not found."
                    );
                }


                $book = $result->fetch_assoc();

                $stmt->close();


                // =========================
                // ALREADY RETURNED
                // =========================

                if (!empty($book["return_date"])) {

                    throw new Exception(
                        "This book has already been returned."
                    );
                }


                // =========================
                // ISSUE DATE VALIDATION
                // =========================

                if (
                    !empty($book["issue_date"])
                    &&
                    $return_date < $book["issue_date"]
                ) {

                    throw new Exception(
                        "Return date cannot be before issue date."
                    );
                }


                // =========================
                // DUE DATE VALIDATION
                // =========================

                if (!empty($book["due_date"])) {

                    $due_date_object = DateTime::createFromFormat(
                        "Y-m-d",
                        $book["due_date"]
                    );

                    if (
                        $due_date_object === false
                        ||
                        $due_date_object->format("Y-m-d")
                            !== $book["due_date"]
                    ) {

                        throw new Exception(
                            "Invalid due date in the record."
                        );
                    }
                }


                // =========================
                // CALCULATE FINE
                // =========================

                $fine = 0;


                if (!empty($book["due_date"])) {

                    $dueDateObj = new DateTime(
                        $book["due_date"]
                    );

                    $returnDateObj = new DateTime(
                        $return_date
                    );


                    if ($returnDateObj > $dueDateObj) {

                        $lateDays = $dueDateObj->diff(
                            $returnDateObj
                        )->days;

                        $fine = $lateDays * $fine_per_day;
                    }
                }


                // =========================
                // UPDATE ISSUED BOOK
                // =========================

                $updateStmt = $conn->prepare("
                    UPDATE issued_books
                    SET
                        return_date = ?,
                        fine = ?
                    WHERE id = ?
                    AND return_date IS NULL
                ");


                if (!$updateStmt) {

                    throw new Exception(
                        "Unable to update return record."
                    );
                }


                $updateStmt->bind_param(
                    "sdi",
                    $return_date,
                    $fine,
                    $id
                );


                if (!$updateStmt->execute()) {

                    $updateStmt->close();

                    throw new Exception(
                        "Unable to update return record."
                    );
                }


                if ($updateStmt->affected_rows !== 1) {

                    $updateStmt->close();

                    throw new Exception(
                        "Book return could not be completed."
                    );
                }


                $updateStmt->close();


                // =========================
                // INCREASE AVAILABLE COPIES
                // =========================

                $bookId = (int) $book["book_id"];


                $copyStmt = $conn->prepare("
                    UPDATE books
                    SET available_copies =
                        available_copies + 1
                    WHERE id = ?
                ");


                if (!$copyStmt) {

                    throw new Exception(
                        "Unable to update available copies."
                    );
                }


                $copyStmt->bind_param(
                    "i",
                    $bookId
                );


                if (!$copyStmt->execute()) {

                    $copyStmt->close();

                    throw new Exception(
                        "Unable to update available copies."
                    );
                }


                if ($copyStmt->affected_rows !== 1) {

                    $copyStmt->close();

                    throw new Exception(
                        "Book record not found."
                    );
                }


                $copyStmt->close();


                // =========================
                // COMMIT
                // =========================

                if (!$conn->commit()) {

                    throw new Exception(
                        "Unable to complete book return."
                    );
                }


                // =========================
                // GET LOGGED-IN USER
                // =========================

                $username = $_SESSION["username"] ?? "";


                if ($username !== "") {

                    $userStmt = $conn->prepare("
                        SELECT id, username
                        FROM users
                        WHERE username = ?
                        LIMIT 1
                    ");


                    if ($userStmt) {

                        $userStmt->bind_param(
                            "s",
                            $username
                        );

                        $userStmt->execute();

                        $userResult =
                            $userStmt->get_result();

                        $user =
                            $userResult
                            ? $userResult->fetch_assoc()
                            : null;

                        $userStmt->close();


                        // =========================
                        // ACTIVITY LOG
                        // =========================

                        if ($user) {

                            $description =
                                "Returned book: "
                                . $book["book_name"]
                                . " from student: "
                                . $book["student_name"]
                                . " | Return Date: "
                                . $return_date
                                . " | Fine: ₹"
                                . number_format(
                                    $fine,
                                    2
                                );


                            log_activity(
                                $conn,
                                (int) $user["id"],
                                $user["username"],
                                "RETURN_BOOK",
                                $description
                            );
                        }
                    }
                }


                // =========================
                // SUCCESS MESSAGE
                // =========================

                if ($fine > 0) {

                    $message =
                        "Book returned successfully! Fine: ₹"
                        . number_format(
                            $fine,
                            2
                        );

                } else {

                    $message =
                        "Book returned successfully! No fine.";
                }


                $message_type = "success";


            } catch (Exception $e) {

                // =========================
                // ROLLBACK
                // =========================

                $conn->rollback();

                error_log(
                    "Return book error: "
                    . $e->getMessage()
                );

                $message = $e->getMessage();
                $message_type = "error";
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
        Return Book - Library Management System
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>


<body>

<div class="page-container">


    <!-- =========================
         HEADER
         ========================= -->

    <div class="page-header">

        <div>

            <h1>
                Library Management System
            </h1>

            <p>
                Return Book
            </p>

        </div>


        <a
            class="logout-btn"
            href="../logout.php"
        >
            Logout
        </a>

    </div>



    <!-- =========================
         CONTENT
         ========================= -->

    <div class="content-card">


        <div class="content-title">

            <div>

                <h2>
                    Return Book
                </h2>

                <p>
                    Return an issued book to the library
                </p>

            </div>

        </div>



        <!-- =========================
             MESSAGE
             ========================= -->

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



        <!-- =========================
             RETURN FORM
             ========================= -->

        <form
            method="POST"
            class="library-form"
        >


            <!-- ISSUED BOOK ID -->

            <div class="form-group">

                <label for="id">
                    Issued Book ID
                </label>

                <input
                    type="number"
                    id="id"
                    name="id"
                    placeholder="Enter Issued Book ID"
                    min="1"
                    required
                >

            </div>



            <!-- RETURN DATE -->

            <div class="form-group">

                <label for="return_date">
                    Return Date
                </label>

                <input
                    type="date"
                    id="return_date"
                    name="return_date"
                    value="<?php echo date("Y-m-d"); ?>"
                    required
                >

            </div>



            <!-- BUTTON -->

            <div class="form-actions">

                <button
                    type="submit"
                    class="submit-btn"
                >
                    ↩️ Return Book
                </button>


                <a
                    href="../dashboard.php"
                    class="cancel-btn"
                >
                    Cancel
                </a>

            </div>


        </form>



        <!-- =========================
             LINKS
             ========================= -->

        <div class="page-links">


            <a
                class="add-btn"
                href="../issue/issued_books.php"
            >
                📚 View Issued Books
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