<?php

require_once "../config/auth.php";
require_librarian();

require_once "../config/database.php";


// =========================
// HELPER FUNCTION
// =========================

function get_report_count($conn, $sql)
{
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log(
            "Report query prepare failed: " . $conn->error
        );

        return 0;
    }

    if (!$stmt->execute()) {
        error_log(
            "Report query execute failed: " . $stmt->error
        );

        $stmt->close();

        return 0;
    }

    $result = $stmt->get_result();

    if (!$result) {
        $stmt->close();

        return 0;
    }

    $row = $result->fetch_assoc();

    $stmt->close();

    if (!$row) {
        return 0;
    }

    return $row["total"] ?? 0;
}


// =========================
// REPORT COUNTS
// =========================


// Total Books

$total_books = (int) get_report_count(
    $conn,
    "SELECT COUNT(*) AS total FROM books"
);


// Total Students

$total_students = (int) get_report_count(
    $conn,
    "SELECT COUNT(*) AS total FROM students"
);


// Total Issued Books

$total_issued = (int) get_report_count(
    $conn,
    "SELECT COUNT(*) AS total FROM issued_books"
);


// Currently Issued

$current_issued = (int) get_report_count(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM issued_books
    WHERE return_date IS NULL
    "
);


// Returned Books

$returned_books = (int) get_report_count(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM issued_books
    WHERE return_date IS NOT NULL
    "
);


// Overdue Books

$overdue_books = (int) get_report_count(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM issued_books
    WHERE return_date IS NULL
    AND due_date IS NOT NULL
    AND due_date < CURDATE()
    "
);


// =========================
// TOTAL FINE
// =========================

$fine_stmt = $conn->prepare(
    "
    SELECT COALESCE(SUM(fine), 0) AS total
    FROM issued_books
    "
);

$total_fine = 0;

if (!$fine_stmt) {

    error_log(
        "Fine report prepare failed: " . $conn->error
    );

} elseif (!$fine_stmt->execute()) {

    error_log(
        "Fine report execute failed: "
        . $fine_stmt->error
    );

} else {

    $fine_result = $fine_stmt->get_result();

    if ($fine_result) {

        $fine_row = $fine_result->fetch_assoc();

        if ($fine_row) {
            $total_fine = (float) (
                $fine_row["total"] ?? 0
            );
        }
    }
}

if ($fine_stmt) {
    $fine_stmt->close();
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
        Reports - Library Management System
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
                Reports & Analytics
            </p>

        </div>


        <a
            href="../logout.php"
            class="logout-btn"
        >
            Logout
        </a>

    </div>



    <!-- =========================
         REPORT DASHBOARD
         ========================= -->

    <div class="content-card">


        <div class="content-title">

            <div>

                <h2>
                    Reports Dashboard
                </h2>

                <p>
                    View library statistics and reports
                </p>

            </div>

        </div>



        <!-- =========================
             STATISTICS
             ========================= -->

        <div class="stats-grid">


            <!-- Total Books -->

            <div class="stat-card">

                <h3>
                    <?php echo $total_books; ?>
                </h3>

                <p>
                    📚 Total Books
                </p>

            </div>



            <!-- Total Students -->

            <div class="stat-card">

                <h3>
                    <?php echo $total_students; ?>
                </h3>

                <p>
                    👨‍🎓 Total Students
                </p>

            </div>



            <!-- Total Issued -->

            <div class="stat-card">

                <h3>
                    <?php echo $total_issued; ?>
                </h3>

                <p>
                    📖 Total Issues
                </p>

            </div>



            <!-- Currently Issued -->

            <div class="stat-card">

                <h3>
                    <?php echo $current_issued; ?>
                </h3>

                <p>
                    📕 Currently Issued
                </p>

            </div>



            <!-- Returned -->

            <div class="stat-card">

                <h3>
                    <?php echo $returned_books; ?>
                </h3>

                <p>
                    ✅ Returned Books
                </p>

            </div>



            <!-- Overdue -->

            <div class="stat-card">

                <h3>
                    <?php echo $overdue_books; ?>
                </h3>

                <p>
                    ⏰ Overdue Books
                </p>

            </div>



            <!-- Fine -->

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
                    💰 Total Fine
                </p>

            </div>


        </div>


    </div>



    <!-- =========================
         REPORT LINKS
         ========================= -->

    <div class="content-card">


        <div class="content-title">

            <div>

                <h2>
                    Available Reports
                </h2>

                <p>
                    Open detailed library reports
                </p>

            </div>

        </div>


        <div class="page-links">


            <a
                href="issued_report.php"
                class="add-btn"
            >
                📚 Issued Books Report
            </a>


            <a
                href="returned_report.php"
                class="add-btn"
            >
                ✅ Returned Books Report
            </a>


            <a
                href="overdue_report.php"
                class="add-btn"
            >
                ⏰ Overdue Books Report
            </a>


            <a
                href="fine_report.php"
                class="add-btn"
            >
                💰 Fine Report
            </a>


        </div>


    </div>



    <!-- =========================
         BACK BUTTON
         ========================= -->

    <a
        href="../dashboard.php"
        class="back-btn"
    >
        ← Back to Dashboard
    </a>


</div>

</body>

</html>