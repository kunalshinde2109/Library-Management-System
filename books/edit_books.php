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


$message = "";


// =========================
// GET BOOK
// =========================

$stmt = $conn->prepare(
    "SELECT id, book_name, author, category
     FROM books
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {

    error_log(
        "Edit book select prepare failed: "
        . $conn->error
    );

    die("Unable to load book. Please try again later.");
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

$book = $result->fetch_assoc();

$stmt->close();


if (!$book) {

    die("Book not found.");
}


// =========================
// UPDATE BOOK
// =========================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $book_name = trim($_POST["book_name"] ?? "");
    $author = trim($_POST["author"] ?? "");
    $category = trim($_POST["category"] ?? "");


    // =========================
    // INPUT VALIDATION
    // =========================

    if (
        $book_name === "" ||
        $author === "" ||
        $category === ""
    ) {

        $message = "Please fill all fields.";

    } elseif (strlen($book_name) > 255) {

        $message = "Book name is too long.";

    } elseif (strlen($author) > 255) {

        $message = "Author name is too long.";

    } elseif (strlen($category) > 100) {

        $message = "Category name is too long.";

    } else {


        // =========================
        // UPDATE DATABASE
        // =========================

        $stmt = $conn->prepare(
            "UPDATE books
             SET book_name = ?,
                 author = ?,
                 category = ?
             WHERE id = ?"
        );

        if (!$stmt) {

            error_log(
                "Edit book update prepare failed: "
                . $conn->error
            );

            $message =
                "Something went wrong. Please try again.";

        } else {

            $stmt->bind_param(
                "sssi",
                $book_name,
                $author,
                $category,
                $id
            );


            if ($stmt->execute()) {


                // =========================
                // GET LOGGED-IN USER
                // =========================

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


                            // =========================
                            // ACTIVITY LOG
                            // =========================

                            log_activity(
                                $conn,
                                $user["id"],
                                $user["username"],
                                "EDIT_BOOK",
                                "Updated book: " . $book_name
                            );
                        }

                        $user_stmt->close();
                    }
                }


                // =========================
                // REDIRECT
                // =========================

                header("Location: view_books.php");
                exit();

            } else {

                error_log(
                    "Edit book update failed: "
                    . $stmt->error
                );

                $message =
                    "Unable to update book. Please try again.";
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

    <title>Edit Book - Library Management System</title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

    <style>

        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .edit-book-container {
            width: 90%;
            max-width: 600px;
            margin: 60px auto;
            padding: 35px;
            background: rgba(255, 255, 255, 0.97);
            border-radius: 15px;
            box-shadow:
                0 10px 30px
                rgba(0, 0, 0, 0.30);
            box-sizing: border-box;
        }

        .edit-book-container h2 {
            text-align: center;
            color: #222;
            font-size: 30px;
            margin-bottom: 30px;
        }

        .edit-book-form {
            display: flex;
            flex-direction: column;
        }

        .edit-book-form label {
            color: #222;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
            margin-top: 15px;
        }

        .edit-book-form input {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            border: 1px solid #bbb;
            border-radius: 7px;
            box-sizing: border-box;
            outline: none;
            background: #fff;
            color: #222;
        }

        .edit-book-form input:focus {
            border-color: #1976d2;
            box-shadow:
                0 0 5px
                rgba(25, 118, 210, 0.25);
        }

        .edit-book-form button {
            margin-top: 25px;
            padding: 13px;
            background: #1976d2;
            color: #ffffff;
            border: none;
            border-radius: 7px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .edit-book-form button:hover {
            background: #125ca8;
        }

        .error-message {
            text-align: center;
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .back-books {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #1976d2;
            font-weight: bold;
            text-decoration: none;
        }

        .back-books:hover {
            text-decoration: underline;
        }

    </style>

</head>

<body>

<div class="edit-book-container">

    <h2>✏️ Edit Book</h2>


    <?php if ($message !== ""): ?>

        <div class="error-message">

            <?php
            echo htmlspecialchars(
                $message,
                ENT_QUOTES,
                "UTF-8"
            );
            ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        class="edit-book-form"
    >

        <label for="book_name">
            Book Name
        </label>

        <input
            type="text"
            id="book_name"
            name="book_name"
            value="<?php
                echo htmlspecialchars(
                    $book["book_name"],
                    ENT_QUOTES,
                    "UTF-8"
                );
            ?>"
            maxlength="255"
            required
        >


        <label for="author">
            Author
        </label>

        <input
            type="text"
            id="author"
            name="author"
            value="<?php
                echo htmlspecialchars(
                    $book["author"],
                    ENT_QUOTES,
                    "UTF-8"
                );
            ?>"
            maxlength="255"
            required
        >


        <label for="category">
            Category
        </label>

        <input
            type="text"
            id="category"
            name="category"
            value="<?php
                echo htmlspecialchars(
                    $book["category"],
                    ENT_QUOTES,
                    "UTF-8"
                );
            ?>"
            maxlength="100"
            required
        >


        <button type="submit">
            ✏️ Update Book
        </button>

    </form>


    <a
        href="view_books.php"
        class="back-books"
    >
        ⬅ Back to Books
    </a>

</div>

</body>

</html>