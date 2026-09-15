<?php

require_once "../config/auth.php";
require_once "../config/database.php";

require_login();

$username = $_SESSION["username"] ?? "";
$role = $_SESSION["role"] ?? "student";


// =========================
// GET STUDENT ID
// =========================

$student_id = 0;


// =========================
// ADMIN / LIBRARIAN
// =========================

if ($role === "admin" || $role === "librarian") {

    $student_id = $_GET["id"] ?? "";

    if (
        $student_id === "" ||
        !ctype_digit((string)$student_id) ||
        (int)$student_id <= 0
    ) {
        die("Invalid Student ID.");
    }

    $student_id = (int)$student_id;

}


// =========================
// STUDENT
// =========================

elseif ($role === "student") {

    /*
     * Student can see only own profile.
     * Username -> users.email -> students.email
     */

    if ($username === "") {
        header("Location: ../login.php");
        exit();
    }

    $stmt = $conn->prepare(
        "SELECT students.id
         FROM users
         INNER JOIN students
             ON users.email = students.email
         WHERE users.username = ?
         LIMIT 1"
    );

    if (!$stmt) {

        error_log(
            "Student profile user lookup failed: " .
            $conn->error
        );

        die(
            "Unable to load profile. Please try again later."
        );
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $student_result = $stmt->get_result();

    if (
        !$student_result ||
        $student_result->num_rows !== 1
    ) {

        $stmt->close();

        die("Student profile not found.");
    }

    $student_data =
        $student_result->fetch_assoc();

    $student_id =
        (int)$student_data["id"];

    $stmt->close();

}


// =========================
// INVALID ROLE
// =========================

else {

    header("Location: ../dashboard.php");
    exit();
}


// =========================
// GET STUDENT DETAILS
// =========================

$stmt = $conn->prepare(
    "SELECT id, name, email, phone, course
     FROM students
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {

    error_log(
        "Student profile SELECT prepare failed: " .
        $conn->error
    );

    die(
        "Unable to load student. Please try again later."
    );
}

$stmt->bind_param("i", $student_id);
$stmt->execute();

$student_result = $stmt->get_result();

if (
    !$student_result ||
    $student_result->num_rows !== 1
) {

    $stmt->close();

    die("Student not found.");
}

$student = $student_result->fetch_assoc();

$stmt->close();


// =========================
// GET BOOK HISTORY
// =========================

$stmt = $conn->prepare(
    "SELECT
        issued_books.id,
        issued_books.issue_date,
        issued_books.due_date,
        issued_books.return_date,
        issued_books.fine,
        books.book_name,
        books.author
     FROM issued_books
     LEFT JOIN books
        ON issued_books.book_id = books.id
     WHERE issued_books.student_id = ?
     ORDER BY issued_books.id DESC"
);

if (!$stmt) {

    error_log(
        "Student book history prepare failed: " .
        $conn->error
    );

    die(
        "Unable to load book history. Please try again later."
    );
}

$stmt->bind_param("i", $student_id);
$stmt->execute();

$result = $stmt->get_result();

if (!$result) {

    error_log(
        "Student book history execute failed: " .
        $stmt->error
    );

    $stmt->close();

    die(
        "Unable to load book history. Please try again later."
    );
}


// =========================
// STORE BOOK HISTORY
// =========================

$books = [];

$total_books = 0;
$current_books = 0;
$returned_books = 0;
$total_fine = 0;

while ($row = $result->fetch_assoc()) {

    $books[] = $row;

    $total_books++;

    $total_fine +=
        (float)($row["fine"] ?? 0);

    if (!empty($row["return_date"])) {

        $returned_books++;

    } else {

        $current_books++;
    }
}

$stmt->close();

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
        Student Profile - Library Management System
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
                Student Profile
            </p>

        </div>


        <a
            href="../logout.php"
            class="logout-btn"
        >
            Logout
        </a>

    </div>


    <!-- STUDENT PROFILE -->

    <div class="content-card">

        <div class="content-title">

            <div>

                <h2>
                    👤 Student Profile
                </h2>

                <p>
                    Complete student information
                </p>

            </div>

        </div>


        <div class="student-profile">


            <!-- STUDENT ID -->

            <div class="profile-item">

                <strong>
                    Student ID
                </strong>

                <span>
                    <?php
                    echo (int)$student["id"];
                    ?>
                </span>

            </div>


            <!-- NAME -->

            <div class="profile-item">

                <strong>
                    Name
                </strong>

                <span>
                    <?php
                    echo htmlspecialchars(
                        $student["name"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>
                </span>

            </div>


            <!-- EMAIL -->

            <div class="profile-item">

                <strong>
                    Email
                </strong>

                <span>
                    <?php
                    echo htmlspecialchars(
                        $student["email"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>
                </span>

            </div>


            <!-- PHONE -->

            <div class="profile-item">

                <strong>
                    Phone
                </strong>

                <span>

                    <?php

                    if (!empty($student["phone"])) {

                        echo htmlspecialchars(
                            $student["phone"],
                            ENT_QUOTES,
                            "UTF-8"
                        );

                    } else {

                        echo "Not Available";

                    }

                    ?>

                </span>

            </div>


            <!-- COURSE -->

            <div class="profile-item">

                <strong>
                    Course
                </strong>

                <span>

                    <?php

                    if (!empty($student["course"])) {

                        echo htmlspecialchars(
                            $student["course"],
                            ENT_QUOTES,
                            "UTF-8"
                        );

                    } else {

                        echo "Not Available";

                    }

                    ?>

                </span>

            </div>

        </div>

    </div>


    <!-- BOOK STATISTICS -->

    <div class="content-card">

        <div class="content-title">

            <div>

                <h2>
                    📊 Book Statistics
                </h2>

                <p>
                    Student borrowing summary
                </p>

            </div>

        </div>


        <div class="stats-grid">


            <div class="stat-card">

                <h3>
                    <?php echo $total_books; ?>
                </h3>

                <p>
                    Total Books
                </p>

            </div>


            <div class="stat-card">

                <h3>
                    <?php echo $current_books; ?>
                </h3>

                <p>
                    Currently Issued
                </p>

            </div>


            <div class="stat-card">

                <h3>
                    <?php echo $returned_books; ?>
                </h3>

                <p>
                    Returned Books
                </p>

            </div>


            <div class="stat-card">

                <h3>
                    ₹<?php
                    echo number_format(
                        $total_fine,
                        2
                    );
                    ?>
                </h3>

                <p>
                    Total Fine
                </p>

            </div>

        </div>

    </div>


    <!-- BOOK HISTORY -->

    <div class="content-card">

        <div class="content-title">

            <div>

                <h2>
                    📚 Book History
                </h2>

                <p>
                    Books issued to this student
                </p>

            </div>

        </div>


        <div class="table-wrapper">

            <table class="data-table">

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Book</th>
                        <th>Author</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Fine</th>
                        <th>Status</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (count($books) > 0): ?>


                    <?php foreach ($books as $book): ?>

                    <tr>


                        <!-- ID -->

                        <td>
                            <?php
                            echo (int)$book["id"];
                            ?>
                        </td>


                        <!-- BOOK -->

                        <td>

                            <strong class="book-name">

                                <?php
                                echo htmlspecialchars(
                                    $book["book_name"]
                                    ?? "Unknown Book",
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>

                            </strong>

                        </td>


                        <!-- AUTHOR -->

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $book["author"]
                                ?? "Unknown",
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>

                        </td>


                        <!-- ISSUE DATE -->

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $book["issue_date"]
                                ?? "",
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>

                        </td>


                        <!-- DUE DATE -->

                        <td>

                            <?php

                            if (!empty($book["due_date"])) {

                                echo htmlspecialchars(
                                    $book["due_date"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                            } else {

                                echo "Not Set";

                            }

                            ?>

                        </td>


                        <!-- RETURN DATE -->

                        <td>

                            <?php

                            if (!empty($book["return_date"])) {

                                echo htmlspecialchars(
                                    $book["return_date"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                            } else {

                                echo "Not Returned";

                            }

                            ?>

                        </td>


                        <!-- FINE -->

                        <td>

                            ₹<?php
                            echo number_format(
                                (float)(
                                    $book["fine"] ?? 0
                                ),
                                2
                            );
                            ?>

                        </td>


                        <!-- STATUS -->

                        <td>

                            <?php

                            if (
                                !empty(
                                    $book["return_date"]
                                )
                            ) {

                            ?>

                                <span
                                    class="status-badge returned"
                                >
                                    Returned
                                </span>

                            <?php

                            } elseif (
                                !empty($book["due_date"])
                                &&
                                $book["due_date"]
                                < date("Y-m-d")
                            ) {

                            ?>

                                <span
                                    class="status-badge overdue"
                                >
                                    Overdue
                                </span>

                            <?php

                            } else {

                            ?>

                                <span
                                    class="status-badge issued"
                                >
                                    Issued
                                </span>

                            <?php

                            }

                            ?>

                        </td>

                    </tr>

                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="8"
                            class="no-books"
                        >

                            <div class="empty-books">

                                <div class="empty-icon">
                                    📚
                                </div>

                                <h3>
                                    No Book History
                                </h3>

                                <p>
                                    No books have been issued to this student yet.
                                </p>

                            </div>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>


        <!-- BUTTONS -->

        <div class="page-links">


            <?php
            if (
                $role === "admin" ||
                $role === "librarian"
            ):
            ?>

                <a
                    href="view_student.php"
                    class="back-btn"
                >
                    ← Back to Students
                </a>

            <?php endif; ?>


            <a
                href="../dashboard.php"
                class="back-btn"
            >
                ← Dashboard
            </a>


        </div>

    </div>

</div>

</body>

</html>