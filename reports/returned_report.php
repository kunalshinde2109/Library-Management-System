<?php

require_once "../config/auth.php";
require_librarian();

require_once "../config/database.php";


$search = "";

if (isset($_GET["search"])) {

    $search = trim($_GET["search"]);

    if (strlen($search) > 100) {
        $search = substr($search, 0, 100);
    }
}


// =========================
// MAIN QUERY
// =========================

$sql = "
    SELECT
        issued_books.id,
        books.book_name,
        students.name AS student_name,
        issued_books.issue_date,
        issued_books.due_date,
        issued_books.return_date,
        issued_books.fine
    FROM issued_books
    LEFT JOIN books
        ON issued_books.book_id = books.id
    LEFT JOIN students
        ON issued_books.student_id = students.id
    WHERE issued_books.return_date IS NOT NULL
";


if ($search !== "") {

    $sql .= "
        AND (
            CAST(issued_books.id AS CHAR) LIKE ?
            OR books.book_name LIKE ?
            OR students.name LIKE ?
            OR CAST(issued_books.issue_date AS CHAR) LIKE ?
            OR CAST(issued_books.due_date AS CHAR) LIKE ?
            OR CAST(issued_books.return_date AS CHAR) LIKE ?
        )
    ";
}


$sql .= "
    ORDER BY issued_books.id DESC
";


// =========================
// PREPARE
// =========================

$stmt = $conn->prepare($sql);

if (!$stmt) {

    error_log(
        "Returned report prepare failed: "
        . $conn->error
    );

    die(
        "Unable to load returned books report. Please try again later."
    );
}


// =========================
// BIND SEARCH
// =========================

if ($search !== "") {

    $search_value = "%" . $search . "%";

    $stmt->bind_param(
        "ssssss",
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value
    );
}


// =========================
// EXECUTE
// =========================

if (!$stmt->execute()) {

    error_log(
        "Returned report execute failed: "
        . $stmt->error
    );

    $stmt->close();

    die(
        "Unable to load returned books report. Please try again later."
    );
}


$result = $stmt->get_result();

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
        Returned Books Report
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >


    <style>

        body {
            margin: 0;
            font-family: Arial, sans-serif;

            background:
                linear-gradient(
                    rgba(255,255,255,0.72),
                    rgba(255,255,255,0.72)
                ),
                url("../images/library-bg.png");

            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }


        .report-container {
            width: 92%;
            max-width: 1250px;
            margin: 30px auto;
        }


        .report-header {
            background: rgba(255,255,255,0.94);

            padding: 22px 28px;

            border-radius: 18px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-bottom: 20px;
        }


        .report-header h1 {
            margin: 0;
            color: #0b1f3a;
            font-size: 28px;
        }


        .report-header p {
            margin: 6px 0 0;
            color: #64748b;
        }


        .logout-btn {
            background: #ff1744;

            color: white;

            text-decoration: none;

            padding: 11px 22px;

            border-radius: 10px;

            font-weight: bold;
        }


        .report-card {
            background: rgba(255,255,255,0.95);

            padding: 25px;

            border-radius: 18px;

            box-shadow:
                0 8px 25px rgba(0,0,0,0.08);
        }


        .report-title {
            color: #0b1f3a;

            margin-top: 0;
            margin-bottom: 18px;
        }


        .search-form {
            display: flex;

            gap: 10px;

            margin-bottom: 22px;
        }


        .search-form input {
            flex: 1;

            padding: 13px 15px;

            border: 1px solid #d1d5db;

            border-radius: 10px;

            font-size: 15px;
        }


        .search-btn {
            background: #0866ff;

            color: white;

            border: none;

            padding: 12px 22px;

            border-radius: 10px;

            font-weight: bold;

            cursor: pointer;
        }


        .clear-btn {
            background: #64748b;

            color: white;

            text-decoration: none;

            padding: 12px 20px;

            border-radius: 10px;

            font-weight: bold;
        }


        .print-btn {
            background: #16a34a;

            color: white;

            border: none;

            padding: 12px 22px;

            border-radius: 10px;

            font-weight: bold;

            cursor: pointer;

            margin-bottom: 18px;
        }


        .table-wrapper {
            overflow-x: auto;
        }


        .report-table {
            width: 100%;

            border-collapse: collapse;

            min-width: 900px;
        }


        .report-table th {
            background: #0b1f3a;

            color: white;

            padding: 13px;

            text-align: left;
        }


        .report-table td {
            padding: 12px;

            border-bottom:
                1px solid #e5e7eb;
        }


        .report-table tr:hover {
            background: #f8fafc;
        }


        .returned-badge {
            display: inline-block;

            padding: 6px 12px;

            border-radius: 20px;

            background: #dcfce7;

            color: #15803d;

            font-size: 12px;

            font-weight: bold;
        }


        .bottom-links {
            margin-top: 22px;

            display: flex;

            gap: 15px;
        }


        .bottom-links a {
            text-decoration: none;

            font-weight: bold;

            color: #0866ff;
        }


        @media print {

            body {
                background: white;
            }


            .logout-btn,
            .search-form,
            .print-btn,
            .bottom-links {
                display: none !important;
            }


            .report-container {
                width: 100%;
                margin: 0;
            }


            .report-header,
            .report-card {
                box-shadow: none;
                background: white;
            }

        }

    </style>

</head>


<body>


<div class="report-container">


    <!-- HEADER -->

    <div class="report-header">

        <div>

            <h1>
                📚 Library Management System
            </h1>

            <p>
                Returned Books Report
            </p>

        </div>


        <a
            href="../logout.php"
            class="logout-btn"
        >
            Logout
        </a>

    </div>



    <!-- REPORT CARD -->

    <div class="report-card">


        <h2 class="report-title">
            ✅ Returned Books Report
        </h2>



        <!-- SEARCH -->

        <form
            method="GET"
            class="search-form"
        >

            <input
                type="text"
                name="search"
                placeholder="Search by book, student, ID or date..."
                value="<?php

                echo htmlspecialchars(
                    $search,
                    ENT_QUOTES,
                    "UTF-8"
                );

                ?>"
                maxlength="100"
            >


            <button
                type="submit"
                class="search-btn"
            >
                🔍 Search
            </button>


            <?php if ($search !== ""): ?>

                <a
                    href="returned_report.php"
                    class="clear-btn"
                >
                    Clear
                </a>

            <?php endif; ?>

        </form>



        <!-- PRINT -->

        <button
            type="button"
            onclick="window.print()"
            class="print-btn"
        >
            🖨️ Print Report
        </button>



        <!-- TABLE -->

        <div class="table-wrapper">


            <table class="report-table">


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


                <?php if (
                    $result &&
                    $result->num_rows > 0
                ): ?>


                    <?php while (
                        $row = $result->fetch_assoc()
                    ): ?>


                        <tr>


                            <!-- ID -->

                            <td>

                                <?php

                                echo (int) $row["id"];

                                ?>

                            </td>



                            <!-- BOOK -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $row["book_name"]
                                        ?? "Unknown",
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                                ?>

                            </td>



                            <!-- STUDENT -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $row["student_name"]
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
                                    $row["issue_date"]
                                        ?? "-",
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
                                        $row["due_date"]
                                    )
                                ) {

                                    echo htmlspecialchars(
                                        $row["due_date"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );

                                } else {

                                    echo "-";

                                }

                                ?>

                            </td>



                            <!-- RETURN DATE -->

                            <td>

                                <?php

                                if (
                                    !empty(
                                        $row["return_date"]
                                    )
                                ) {

                                    echo htmlspecialchars(
                                        $row["return_date"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );

                                } else {

                                    echo "-";

                                }

                                ?>

                            </td>



                            <!-- FINE -->

                            <td>

                                ₹<?php

                                echo number_format(
                                    (float) (
                                        $row["fine"] ?? 0
                                    ),
                                    2
                                );

                                ?>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <span
                                    class="returned-badge"
                                >
                                    Returned
                                </span>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="8"
                            style="
                                text-align:center;
                                padding:30px;
                            "
                        >

                            No returned books found.

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>



        <!-- BOTTOM LINKS -->

        <div class="bottom-links">


            <a href="index.php">
                ← Back to Reports
            </a>


            <a href="../dashboard.php">
                🏠 Dashboard
            </a>


        </div>


    </div>


</div>


</body>

</html>


<?php

$stmt->close();

?>