<?php

require_once "../config/auth.php";
require_librarian();

require_once "../config/database.php";


// =========================
// SEARCH
// =========================

$search = trim($_GET["search"] ?? "");


// =========================
// GET STUDENTS
// =========================

if ($search !== "") {

    // Limit search input length
    if (strlen($search) > 100) {
        $search = substr($search, 0, 100);
    }

    $searchTerm = "%" . $search . "%";

    $stmt = $conn->prepare(
        "SELECT id, name, email, phone, course
         FROM students
         WHERE name LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
            OR course LIKE ?
         ORDER BY id DESC"
    );

    if (!$stmt) {

        error_log(
            "View students search prepare failed: " .
            $conn->error
        );

        die(
            "Unable to load students. Please try again later."
        );
    }

    $stmt->bind_param(
        "ssss",
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm
    );

    if (!$stmt->execute()) {

        error_log(
            "View students search execute failed: " .
            $stmt->error
        );

        $stmt->close();

        die(
            "Unable to load students. Please try again later."
        );
    }

    $result = $stmt->get_result();

} else {

    $result = $conn->query(
        "SELECT id, name, email, phone, course
         FROM students
         ORDER BY id DESC"
    );

    if (!$result) {

        error_log(
            "View students query failed: " .
            $conn->error
        );

        die(
            "Unable to load students. Please try again later."
        );
    }
}


// =========================
// TOTAL STUDENTS
// =========================

$countResult = $conn->query(
    "SELECT COUNT(*) AS total
     FROM students"
);

$totalStudents = 0;

if ($countResult) {

    $countRow = $countResult->fetch_assoc();

    $totalStudents = (int)$countRow["total"];

} else {

    error_log(
        "Student count query failed: " .
        $conn->error
    );
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
        Students - Library Management System
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


    <!-- MAIN CONTENT -->

    <div class="content-card">


        <!-- TITLE -->

        <div class="content-title">

            <div>

                <h2>
                    Registered Students
                </h2>

                <p>
                    Manage all students in the library
                </p>

            </div>


            <a
                href="add_student.php"
                class="add-btn"
            >
                + Add New Student
            </a>

        </div>


        <!-- TOTAL STUDENTS -->

        <div class="books-summary">

            <div class="books-count">

                <span class="books-count-icon">
                    👨‍🎓
                </span>


                <div>

                    <span>
                        Total Students
                    </span>

                    <strong>
                        <?php
                        echo $totalStudents;
                        ?>
                    </strong>

                </div>

            </div>

        </div>


        <!-- SEARCH -->

        <form
            method="GET"
            class="book-search-form"
        >

            <div class="search-box">

                <span class="search-icon">
                    🔎
                </span>


                <input
                    type="text"
                    name="search"
                    value="<?php
                        echo htmlspecialchars(
                            $search,
                            ENT_QUOTES,
                            "UTF-8"
                        );
                    ?>"
                    placeholder="Search by name, email, phone or course..."
                    maxlength="100"
                >

            </div>


            <button
                type="submit"
                class="search-btn"
            >
                Search
            </button>


            <?php if ($search !== "") { ?>

                <a
                    href="view_student.php"
                    class="clear-search-btn"
                >
                    Clear
                </a>

            <?php } ?>

        </form>


        <!-- SEARCH RESULT MESSAGE -->

        <?php if ($search !== "") { ?>

            <div class="search-result-message">

                Search results for:

                <strong>
                    "<?php
                    echo htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>"
                </strong>

            </div>

        <?php } ?>


        <!-- STUDENTS TABLE -->

        <div class="table-wrapper">

            <table class="data-table">

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Name</th>

                        <th>Email</th>

                        <th>Phone</th>

                        <th>Course</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                <?php if ($result && $result->num_rows > 0) { ?>


                    <?php while ($student = $result->fetch_assoc()) { ?>

                        <tr>


                            <!-- ID -->

                            <td>

                                <?php
                                echo (int)$student["id"];
                                ?>

                            </td>


                            <!-- NAME -->

                            <td>

                                <strong class="book-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $student["name"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                </strong>

                            </td>


                            <!-- EMAIL -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $student["email"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>

                            </td>


                            <!-- PHONE -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $student["phone"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>

                            </td>


                            <!-- COURSE -->

                            <td>

                                <span class="category-badge">

                                    <?php
                                    echo htmlspecialchars(
                                        $student["course"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- ACTION -->

                            <td class="action-buttons">


                                <!-- PROFILE -->

                                <a
                                    class="profile-btn"
                                    href="student_profile.php?id=<?php
                                        echo (int)$student["id"];
                                    ?>"
                                >
                                    👤 Profile
                                </a>


                                <!-- EDIT -->

                                <a
                                    class="edit-btn"
                                    href="edit_student.php?id=<?php
                                        echo (int)$student["id"];
                                    ?>"
                                >
                                    ✏️ Edit
                                </a>


                                <!-- DELETE -->

                                <a
                                    class="delete-btn"
                                    href="delete_student.php?id=<?php
                                        echo (int)$student["id"];
                                    ?>"
                                    onclick="return confirm('Are you sure you want to delete this student?');"
                                >
                                    🗑️ Delete
                                </a>


                            </td>

                        </tr>

                    <?php } ?>


                <?php } else { ?>


                    <!-- NO STUDENTS -->

                    <tr>

                        <td
                            colspan="6"
                            class="no-books"
                        >

                            <div class="empty-books">

                                <div class="empty-icon">
                                    👨‍🎓
                                </div>

                                <h3>
                                    No Students Found
                                </h3>

                                <p>

                                    <?php

                                    if ($search !== "") {

                                        echo "Try searching with a different keyword.";

                                    } else {

                                        echo "There are no students registered yet.";

                                    }

                                    ?>

                                </p>

                            </div>

                        </td>

                    </tr>


                <?php } ?>


                </tbody>

            </table>

        </div>


        <!-- BACK BUTTON -->

        <a
            href="../dashboard.php"
            class="back-btn"
        >
            ← Back to Dashboard
        </a>


    </div>

</div>

</body>

</html>

<?php

// Close prepared search statement
if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    $stmt->close();
}

?>