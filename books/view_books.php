<?php

require_once "../config/auth.php";
require_role(["admin", "librarian", "student"]);

require_once "../config/database.php";

$role = $_SESSION["role"] ?? "student";


// =========================
// GET ALL BOOKS
// =========================

$result = $conn->query(
    "SELECT id, book_name, author, category
     FROM books
     ORDER BY id DESC"
);

if (!$result) {

    error_log(
        "View books query failed: "
        . $conn->error
    );

    die("Unable to load books. Please try again later.");
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

    <title>View Books</title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

    <style>

        .books-container {
            background: rgba(255, 255, 255, 0.96);
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 1000px;
            margin: 40px auto;
            box-shadow:
                0 8px 25px
                rgba(0, 0, 0, 0.25);
        }

        .books-container h2 {
            text-align: center;
            color: #222;
            margin-bottom: 25px;
            font-size: 30px;
        }

        .books-table-wrapper {
            overflow-x: auto;
        }

        .books-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
        }

        .books-table th {
            background: #222;
            color: #ffffff;
            padding: 14px;
            text-align: center;
            font-size: 16px;
        }

        .books-table td {
            background: #ffffff;
            color: #222;
            padding: 13px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            font-size: 15px;
        }

        .books-table tr:hover td {
            background: #f2f2f2;
        }

        .edit-btn {
            color: #ffffff;
            background: #1976d2;
            padding: 7px 14px;
            border-radius: 5px;
            text-decoration: none;
            margin-right: 5px;
            display: inline-block;
        }

        .delete-btn {
            color: #ffffff;
            background: #d32f2f;
            padding: 7px 14px;
            border-radius: 5px;
            text-decoration: none;
            margin-left: 5px;
            display: inline-block;
        }

        .edit-btn:hover,
        .delete-btn:hover {
            opacity: 0.85;
        }

        .add-book-btn {
            display: inline-block;
            background: #2e7d32;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 20px;
        }

        .dashboard-btn {
            display: inline-block;
            background: #555;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 12px;
        }

        .add-book-btn:hover,
        .dashboard-btn:hover {
            opacity: 0.85;
        }

        .no-books {
            text-align: center;
            padding: 25px;
            color: #666;
            font-weight: bold;
        }

    </style>

</head>

<body>

<div class="books-container">

    <h2>📚 All Books</h2>

    <div class="books-table-wrapper">

        <table class="books-table">

            <tr>

                <th>Book ID</th>

                <th>Book Name</th>

                <th>Author</th>

                <th>Category</th>

                <?php if (
                    $role === "admin" ||
                    $role === "librarian"
                ): ?>

                    <th>Action</th>

                <?php endif; ?>

            </tr>


            <?php if ($result->num_rows > 0): ?>

                <?php while (
                    $row = $result->fetch_assoc()
                ): ?>

                    <tr>

                        <td>
                            <?php
                            echo (int) $row["id"];
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row["book_name"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row["author"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row["category"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>
                        </td>


                        <?php if (
                            $role === "admin" ||
                            $role === "librarian"
                        ): ?>

                            <td>

                                <a
                                    href="edit_books.php?id=<?php
                                    echo (int) $row["id"];
                                    ?>"
                                    class="edit-btn"
                                >
                                    Edit
                                </a>

                                <a
                                    href="delete_books.php?id=<?php
                                    echo (int) $row["id"];
                                    ?>"
                                    class="delete-btn"
                                    onclick="return confirm('Are you sure you want to delete this book?');"
                                >
                                    Delete
                                </a>

                            </td>

                        <?php endif; ?>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td
                        colspan="<?php
                            echo (
                                $role === "admin" ||
                                $role === "librarian"
                            ) ? "5" : "4";
                        ?>"
                        class="no-books"
                    >
                        No books found.
                    </td>

                </tr>

            <?php endif; ?>

        </table>

    </div>


    <?php if (
        $role === "admin" ||
        $role === "librarian"
    ): ?>

        <a
            href="add_book.php"
            class="add-book-btn"
        >
            ➕ Add New Book
        </a>

        <br>

    <?php endif; ?>


    <a
        href="../dashboard.php"
        class="dashboard-btn"
    >
        ⬅ Back to Dashboard
    </a>

</div>

</body>

</html>