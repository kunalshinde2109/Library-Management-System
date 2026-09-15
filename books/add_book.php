<?php

require_once "../config/auth.php";
require_librarian();

require_once "../config/database.php";
require_once "../config/activity_logs.php";

$message = "";
$message_type = "";

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
        $message_type = "error";

    } elseif (strlen($book_name) > 255) {

        $message = "Book name is too long.";
        $message_type = "error";

    } elseif (strlen($author) > 255) {

        $message = "Author name is too long.";
        $message_type = "error";

    } elseif (strlen($category) > 100) {

        $message = "Category name is too long.";
        $message_type = "error";

    } else {

        // =========================
        // INSERT BOOK
        // =========================

        $stmt = $conn->prepare(
            "INSERT INTO books
            (book_name, author, category)
            VALUES (?, ?, ?)"
        );

        if (!$stmt) {

            error_log(
                "Add book prepare failed: "
                . $conn->error
            );

            $message = "Something went wrong. Please try again.";
            $message_type = "error";

        } else {

            $stmt->bind_param(
                "sss",
                $book_name,
                $author,
                $category
            );


            if ($stmt->execute()) {

                $message = "Book added successfully!";
                $message_type = "success";


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
                                "ADD_BOOK",
                                "Added book: " . $book_name
                            );
                        }

                        $user_stmt->close();
                    }
                }

            } else {

                error_log(
                    "Add book execute failed: "
                    . $stmt->error
                );

                $message =
                    "Unable to add book. Please try again.";

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

    <title>Add New Book - Library Management System</title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

    <style>

        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .add-book-container {
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

        .add-book-container h2 {
            text-align: center;
            color: #222;
            font-size: 30px;
            margin-bottom: 30px;
        }

        .add-book-form {
            display: flex;
            flex-direction: column;
        }

        .add-book-form label {
            color: #222;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
            margin-top: 15px;
        }

        .add-book-form input {
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

        .add-book-form input:focus {
            border-color: #1976d2;
            box-shadow:
                0 0 5px
                rgba(25, 118, 210, 0.25);
        }

        .add-book-form button {
            margin-top: 25px;
            padding: 13px;
            background: #2e7d32;
            color: #ffffff;
            border: none;
            border-radius: 7px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .add-book-form button:hover {
            background: #1b5e20;
        }

        .success-message {
            text-align: center;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-weight: bold;
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

        .back-dashboard {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #1976d2;
            font-weight: bold;
            text-decoration: none;
        }

        .back-dashboard:hover {
            text-decoration: underline;
        }

    </style>

</head>

<body>

<div class="add-book-container">

    <h2>📚 Add New Book</h2>


    <?php if ($message !== ""): ?>

        <?php if ($message_type === "success"): ?>

            <div class="success-message">
                <?php
                echo htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </div>

        <?php else: ?>

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

    <?php endif; ?>


    <form
        method="POST"
        class="add-book-form"
    >

        <label for="book_name">
            Book Name
        </label>

        <input
            type="text"
            id="book_name"
            name="book_name"
            placeholder="Enter book name"
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
            placeholder="Enter author name"
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
            placeholder="Enter book category"
            maxlength="100"
            required
        >


        <button type="submit">
            ➕ Add Book
        </button>

    </form>


    <a
        href="../dashboard.php"
        class="back-dashboard"
    >
        ⬅ Back to Dashboard
    </a>

</div>

</body>

</html>