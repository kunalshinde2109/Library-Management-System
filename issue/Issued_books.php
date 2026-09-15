<?php

require_once "../config/auth.php";
require_librarian();

require_once "../config/database.php";


// ==================================================
// SEARCH
// ==================================================

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);

    if (strlen($search) > 100) {
        $search = substr($search, 0, 100);
    }
}


// ==================================================
// FILTER
// ==================================================

$filter = "all";

if (isset($_GET["filter"])) {
    $requested_filter = $_GET["filter"];

    $allowed_filters = [
        "all",
        "issued",
        "returned",
        "overdue"
    ];

    if (in_array($requested_filter, $allowed_filters, true)) {
        $filter = $requested_filter;
    }
}


// ==================================================
// PAGINATION
// ==================================================

$limit = 10;

$page = 1;

if (
    isset($_GET["page"]) &&
    ctype_digit((string) $_GET["page"])
) {
    $page = (int) $_GET["page"];
}

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $limit;


// ==================================================
// WHERE CONDITIONS
// ==================================================

$conditions = [];
$params = [];
$types = "";


// ==================================================
// SEARCH CONDITION
// ==================================================

if ($search !== "") {

    $search_value = "%" . $search . "%";

    $conditions[] = "(
        books.book_name LIKE ?
        OR students.name LIKE ?
        OR CAST(issued_books.id AS CHAR) LIKE ?
        OR CAST(issued_books.issue_date AS CHAR) LIKE ?
        OR CAST(issued_books.due_date AS CHAR) LIKE ?
        OR CAST(issued_books.return_date AS CHAR) LIKE ?
    )";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "ssssss";
}


// ==================================================
// FILTER CONDITION
// ==================================================

if ($filter === "issued") {

    $conditions[] = "
        issued_books.return_date IS NULL
    ";

} elseif ($filter === "returned") {

    $conditions[] = "
        issued_books.return_date IS NOT NULL
    ";

} elseif ($filter === "overdue") {

    $conditions[] = "
        issued_books.return_date IS NULL
        AND issued_books.due_date IS NOT NULL
        AND issued_books.due_date < CURDATE()
    ";
}


// ==================================================
// WHERE SQL
// ==================================================

$where_sql = "";

if (count($conditions) > 0) {
    $where_sql = " WHERE " . implode(" AND ", $conditions);
}


// ==================================================
// COUNT TOTAL RECORDS
// ==================================================

$count_sql = "
    SELECT COUNT(*) AS total
    FROM issued_books
    LEFT JOIN books
        ON issued_books.book_id = books.id
    LEFT JOIN students
        ON issued_books.student_id = students.id
    $where_sql
";

$count_stmt = $conn->prepare($count_sql);

$total_records = 0;

if (!$count_stmt) {

    error_log(
        "Issued books count prepare failed: " . $conn->error
    );

    die(
        "Unable to load issued books. Please try again later."
    );
}


if ($types !== "") {

    $count_stmt->bind_param(
        $types,
        ...$params
    );
}


if (!$count_stmt->execute()) {

    error_log(
        "Issued books count execute failed: "
        . $count_stmt->error
    );

    $count_stmt->close();

    die(
        "Unable to load issued books. Please try again later."
    );
}


$count_result = $count_stmt->get_result();

if ($count_result) {

    $count_row = $count_result->fetch_assoc();

    if ($count_row) {
        $total_records = (int) $count_row["total"];
    }
}

$count_stmt->close();


// ==================================================
// TOTAL PAGES
// ==================================================

$total_pages = ($total_records > 0)
    ? (int) ceil($total_records / $limit)
    : 1;


// ==================================================
// PREVENT INVALID PAGE
// ==================================================

if ($page > $total_pages) {
    $page = $total_pages;

    $offset = ($page - 1) * $limit;
}


// ==================================================
// MAIN QUERY
// ==================================================

$sql = "
    SELECT

        issued_books.id,
        issued_books.book_id,
        issued_books.student_id,
        issued_books.issue_date,
        issued_books.due_date,
        issued_books.return_date,
        issued_books.fine,

        books.book_name,

        students.name AS student_name

    FROM issued_books

    LEFT JOIN books
        ON issued_books.book_id = books.id

    LEFT JOIN students
        ON issued_books.student_id = students.id

    $where_sql

    ORDER BY issued_books.id DESC

    LIMIT ?
    OFFSET ?
";


$stmt = $conn->prepare($sql);

$result = false;

if (!$stmt) {

    error_log(
        "Issued books prepare failed: " . $conn->error
    );

    die(
        "Unable to load issued books. Please try again later."
    );
}


// ==================================================
// BIND MAIN QUERY PARAMETERS
// ==================================================

if ($types !== "") {

    $main_types = $types . "ii";

    $main_params = $params;

    $main_params[] = $limit;
    $main_params[] = $offset;

    $stmt->bind_param(
        $main_types,
        ...$main_params
    );

} else {

    $stmt->bind_param(
        "ii",
        $limit,
        $offset
    );
}


// ==================================================
// EXECUTE MAIN QUERY
// ==================================================

if (!$stmt->execute()) {

    error_log(
        "Issued books execute failed: " . $stmt->error
    );

    $stmt->close();

    die(
        "Unable to load issued books. Please try again later."
    );
}


$result = $stmt->get_result();


// ==================================================
// HELPER FOR PAGINATION URL
// ==================================================

function pageUrl($pageNumber, $search, $filter)
{
    return "?search="
        . urlencode($search)
        . "&filter="
        . urlencode($filter)
        . "&page="
        . (int) $pageNumber;
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
        Issued Books - Library Management System
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
                Issued Books Management
            </p>

        </div>


        <a
            href="../logout.php"
            class="logout-btn"
        >
            Logout
        </a>

    </div>


    <!-- CONTENT -->

    <div class="content-card">


        <!-- TITLE -->

        <div class="content-title">

            <div>

                <h2>
                    Issued Books
                </h2>

                <p>
                    View all books issued to students
                </p>

            </div>


            <a
                href="issue_book.php"
                class="add-btn"
            >
                + Issue New Book
            </a>

        </div>


        <!-- SEARCH -->

        <form
            method="GET"
            class="library-form"
            style="margin-bottom: 20px;"
        >

            <label>
                Search Issued Books
            </label>


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
                placeholder="Search by book, student, ID or date..."
                maxlength="100"
            >


            <input
                type="hidden"
                name="filter"
                value="<?php
                echo htmlspecialchars(
                    $filter,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>"
            >


            <button
                type="submit"
                class="submit-btn"
            >
                🔍 Search
            </button>


            <?php if ($search !== "" || $filter !== "all") { ?>

                <a
                    href="issued_books.php"
                    class="back-btn"
                    style="display: inline-block; margin-top: 10px;"
                >
                    Clear All
                </a>

            <?php } ?>

        </form>


        <!-- FILTER -->

        <form
            method="GET"
            style="margin-bottom: 20px;"
        >

            <input
                type="hidden"
                name="search"
                value="<?php
                echo htmlspecialchars(
                    $search,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>"
            >


            <label>
                Filter Books
            </label>


            <select
                name="filter"
                onchange="this.form.submit()"
                style="
                    width: 100%;
                    padding: 12px;
                    margin-top: 8px;
                    border: 1px solid #cbd5e1;
                    border-radius: 8px;
                    font-size: 15px;
                "
            >

                <option
                    value="all"
                    <?php
                    echo ($filter === "all")
                        ? "selected"
                        : "";
                    ?>
                >
                    All Books
                </option>


                <option
                    value="issued"
                    <?php
                    echo ($filter === "issued")
                        ? "selected"
                        : "";
                    ?>
                >
                    📚 Issued
                </option>


                <option
                    value="overdue"
                    <?php
                    echo ($filter === "overdue")
                        ? "selected"
                        : "";
                    ?>
                >
                    ⏰ Overdue
                </option>


                <option
                    value="returned"
                    <?php
                    echo ($filter === "returned")
                        ? "selected"
                        : "";
                    ?>
                >
                    ✅ Returned
                </option>

            </select>

        </form>


        <!-- ACTIVE SEARCH / FILTER -->

        <?php if ($search !== "" || $filter !== "all") { ?>

            <p style="margin-bottom: 15px;">

                <?php if ($search !== "") { ?>

                    Search:

                    <strong>
                        "<?php
                        echo htmlspecialchars(
                            $search,
                            ENT_QUOTES,
                            "UTF-8"
                        );
                        ?>"
                    </strong>

                <?php } ?>


                <?php if ($filter === "issued") { ?>

                    | Filter:
                    <strong>Issued</strong>

                <?php } elseif ($filter === "overdue") { ?>

                    | Filter:
                    <strong>Overdue</strong>

                <?php } elseif ($filter === "returned") { ?>

                    | Filter:
                    <strong>Returned</strong>

                <?php } ?>

            </p>

        <?php } ?>


        <!-- TABLE -->

        <div class="table-wrapper">

            <table class="data-table">


                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Book</th>

                        <th>Student</th>

                        <th>Issue Date</th>

                        <th>Due Date</th>

                        <th>Return Date</th>

                        <th>Fine</th>

                        <th>Status</th>

                    </tr>

                </thead>


                <tbody>


                <?php if ($result && $result->num_rows > 0) { ?>


                    <?php while ($issue = $result->fetch_assoc()) { ?>


                        <tr>


                            <!-- ID -->

                            <td>

                                <?php
                                echo (int) $issue["id"];
                                ?>

                            </td>


                            <!-- BOOK -->

                            <td>

                                <strong class="book-name">

                                    <?php

                                    if (
                                        !empty(
                                            $issue["book_name"]
                                        )
                                    ) {

                                        echo htmlspecialchars(
                                            $issue["book_name"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );

                                    } else {

                                        echo "Book ID: "
                                            . (int) $issue["book_id"];

                                    }

                                    ?>

                                </strong>

                            </td>


                            <!-- STUDENT -->

                            <td>

                                <?php

                                if (
                                    !empty(
                                        $issue["student_name"]
                                    )
                                ) {

                                    echo htmlspecialchars(
                                        $issue["student_name"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );

                                } else {

                                    echo "Student ID: "
                                        . (int) $issue["student_id"];

                                }

                                ?>

                            </td>


                            <!-- ISSUE DATE -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $issue["issue_date"] ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                                ?>

                            </td>


                            <!-- DUE DATE -->

                            <td>

                                <?php

                                if (
                                    !empty(
                                        $issue["due_date"]
                                    )
                                ) {

                                    echo htmlspecialchars(
                                        $issue["due_date"],
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

                                if (
                                    !empty(
                                        $issue["return_date"]
                                    )
                                ) {

                                    echo htmlspecialchars(
                                        $issue["return_date"],
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

                                <?php

                                $fine = (float)
                                    ($issue["fine"] ?? 0);

                                ?>

                                <?php if ($fine > 0) { ?>

                                    <strong>
                                        ₹<?php
                                        echo number_format(
                                            $fine,
                                            2
                                        );
                                        ?>
                                    </strong>

                                <?php } else { ?>

                                    ₹0.00

                                <?php } ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <?php

                                if (
                                    !empty(
                                        $issue["return_date"]
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

                                    !empty(
                                        $issue["due_date"]
                                    )
                                    &&
                                    $issue["due_date"]
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

                                <?php } ?>

                            </td>


                        </tr>


                    <?php } ?>


                <?php } else { ?>


                    <!-- NO RESULTS -->

                    <tr>

                        <td
                            colspan="8"
                            class="no-books"
                        >

                            <div class="empty-books">

                                <div class="empty-icon">
                                    🔍
                                </div>


                                <h3>
                                    No Results Found
                                </h3>


                                <p>

                                    <?php if ($search !== "") { ?>

                                        No issued books match
                                        "<?php
                                        echo htmlspecialchars(
                                            $search,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>".

                                    <?php } elseif (
                                        $filter === "issued"
                                    ) { ?>

                                        No currently issued books.

                                    <?php } elseif (
                                        $filter === "overdue"
                                    ) { ?>

                                        No overdue books.

                                    <?php } elseif (
                                        $filter === "returned"
                                    ) { ?>

                                        No returned books.

                                    <?php } else { ?>

                                        No books have been issued yet.

                                    <?php } ?>

                                </p>

                            </div>

                        </td>

                    </tr>


                <?php } ?>


                </tbody>

            </table>

        </div>


        <!-- PAGINATION -->

        <?php if ($total_records > 0) { ?>

            <div
                style="
                    margin-top: 25px;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    gap: 8px;
                    flex-wrap: wrap;
                "
            >


                <!-- PREVIOUS -->

                <?php if ($page > 1) { ?>

                    <a
                        href="<?php
                        echo pageUrl(
                            $page - 1,
                            $search,
                            $filter
                        );
                        ?>"
                        class="back-btn"
                    >
                        ← Previous
                    </a>

                <?php } ?>


                <!-- PAGE NUMBERS -->

                <?php for (
                    $i = 1;
                    $i <= $total_pages;
                    $i++
                ) { ?>


                    <a
                        href="<?php
                        echo pageUrl(
                            $i,
                            $search,
                            $filter
                        );
                        ?>"
                        style="
                            padding: 10px 15px;
                            border-radius: 8px;
                            text-decoration: none;
                            border: 1px solid #cbd5e1;
                            <?php

                            echo ($i === $page)
                                ? "background:#2563eb;color:white;"
                                : "background:white;color:#1e293b;";

                            ?>
                        "
                    >

                        <?php echo $i; ?>

                    </a>


                <?php } ?>


                <!-- NEXT -->

                <?php if ($page < $total_pages) { ?>

                    <a
                        href="<?php
                        echo pageUrl(
                            $page + 1,
                            $search,
                            $filter
                        );
                        ?>"
                        class="add-btn"
                    >
                        Next →
                    </a>

                <?php } ?>


            </div>


            <!-- PAGINATION INFO -->

            <p
                style="
                    text-align: center;
                    margin-top: 12px;
                "
            >

                Showing

                <strong>
                    <?php
                    echo min(
                        $offset + 1,
                        $total_records
                    );
                    ?>
                </strong>

                -

                <strong>
                    <?php
                    echo min(
                        $offset + $limit,
                        $total_records
                    );
                    ?>
                </strong>

                of

                <strong>
                    <?php echo $total_records; ?>
                </strong>

                records

            </p>

        <?php } ?>


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

$stmt->close();

?>