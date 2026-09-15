<?php

require_once "../config/auth.php";
require_librarian();

require_once "../config/database.php";
require_once "../config/activity_logs.php";


// =========================
// CHECK BOOK ID
// =========================

if (!isset($_GET["id"]) || !ctype_digit($_GET["id"])) {

    header("Location: view_books.php");
    exit();
}

$id = (int) $_GET["id"];

if ($id <= 0) {

    header("Location: view_books.php");
    exit();
}


// =========================
// GET BOOK INFORMATION
// =========================

$stmt = $conn->prepare(
    "SELECT id, book_name
     FROM books
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {

    error_log(
        "Delete book select prepare failed: "
        . $conn->error
    );

    header("Location: view_books.php");
    exit();
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

$book = $result->fetch_assoc();

$stmt->close();


if (!$book) {

    header("Location: view_books.php");
    exit();
}


// =========================
// DELETE BOOK
// =========================

$stmt = $conn->prepare(
    "DELETE FROM books
     WHERE id = ?"
);

if (!$stmt) {

    error_log(
        "Delete book prepare failed: "
        . $conn->error
    );

    header("Location: view_books.php");
    exit();
}

$stmt->bind_param("i", $id);


if ($stmt->execute()) {

    $deleted = $stmt->affected_rows;

    $stmt->close();


    // =========================
    // ACTIVITY LOG
    // =========================

    if ($deleted === 1) {

        $username = $_SESSION["username"] ?? "";

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


                    log_activity(
                        $conn,
                        $user["id"],
                        $user["username"],
                        "DELETE_BOOK",
                        "Deleted book: "
                        . $book["book_name"]
                    );
                }

                $user_stmt->close();
            }
        }
    }


    // =========================
    // REDIRECT
    // =========================

    header("Location: view_books.php");
    exit();

}


// =========================
// DELETE ERROR
// =========================

error_log(
    "Delete book execute failed: "
    . $stmt->error
);

$stmt->close();

header("Location: view_books.php");
exit();

?>