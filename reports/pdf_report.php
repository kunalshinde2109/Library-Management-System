<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: ../login.php");
    exit();
}

include "../config/database.php";

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
    LEFT JOIN books ON issued_books.book_id = books.id
    LEFT JOIN students ON issued_books.student_id = students.id
    ORDER BY issued_books.id DESC
";

$result = $conn->query($sql);

$total_fine = 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Library Report - PDF</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 30px;
            background: #f1f5f9;
            color: #0f172a;
        }

        .report {
            background: white;
            max-width: 1100px;
            margin: auto;
            padding: 30px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #0b1f3a;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .header p {
            margin: 7px 0 0;
            color: #64748b;
        }

        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .summary strong {
            font-size: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th {
            background: #0b1f3a;
            color: white;
            padding: 10px;
            text-align: left;
        }

        td {
            padding: 9px;
            border: 1px solid #e2e8f0;
        }

        .status {
            font-weight: bold;
        }

        .returned {
            color: #15803d;
        }

        .issued {
            color: #1d4ed8;
        }

        .overdue {
            color: #dc2626;
        }

        .print-section {
            text-align: center;
            margin-top: 25px;
        }

        .print-btn {
            background: #16a34a;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .back-btn {
            background: #64748b;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 8px;
            margin-left: 10px;
            display: inline-block;
        }

        .footer {
            text-align: center;
            margin-top: 25px;
            font-size: 12px;
            color: #64748b;
        }

        @media print {

            body {
                background: white;
                padding: 0;
            }

            .report {
                max-width: 100%;
                padding: 10px;
            }

            .print-section {
                display: none;
            }

            .footer {
                margin-top: 15px;
            }

            table {
                font-size: 11px;
            }

            th {
                background: #0b1f3a !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

        }

    </style>

</head>

<body>

<div class="report">

    <div class="header">

        <h1>📚 Library Management System</h1>

        <p>Library Issued & Returned Books Report</p>

    </div>


    <?php

    if ($result && $result->num_rows > 0) {

        $result->data_seek(0);

        while ($row = $result->fetch_assoc()) {

            $total_fine += (float)($row["fine"] ?? 0);

        }

        $result->data_seek(0);
    }

    ?>


    <div class="summary">

        <div>
            <strong>Total Records:</strong>
            <?php echo $result ? $result->num_rows : 0; ?>
        </div>

        <div>
            <strong>Total Fine:</strong>
            ₹<?php echo number_format($total_fine, 2); ?>
        </div>

    </div>


    <table>

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

        <?php if ($result && $result->num_rows > 0): ?>

            <?php while ($row = $result->fetch_assoc()): ?>

                <?php

                if (!empty($row["return_date"])) {

                    $status = "Returned";
                    $status_class = "returned";

                } elseif (
                    !empty($row["due_date"]) &&
                    $row["due_date"] < date("Y-m-d")
                ) {

                    $status = "Overdue";
                    $status_class = "overdue";

                } else {

                    $status = "Issued";
                    $status_class = "issued";

                }

                ?>

                <tr>

                    <td>
                        <?php echo $row["id"]; ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $row["book_name"] ?? "Unknown"
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $row["student_name"] ?? "Unknown"
                        );
                        ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row["issue_date"]); ?>
                    </td>

                    <td>
                        <?php
                        echo !empty($row["due_date"])
                            ? htmlspecialchars($row["due_date"])
                            : "-";
                        ?>
                    </td>

                    <td>
                        <?php
                        echo !empty($row["return_date"])
                            ? htmlspecialchars($row["return_date"])
                            : "-";
                        ?>
                    </td>

                    <td>
                        ₹<?php
                        echo number_format(
                            (float)($row["fine"] ?? 0),
                            2
                        );
                        ?>
                    </td>

                    <td class="status <?php echo $status_class; ?>">
                        <?php echo $status; ?>
                    </td>

                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>

                <td colspan="8" style="text-align:center;">
                    No records found.
                </td>

            </tr>

        <?php endif; ?>

        </tbody>

    </table>


    <div class="print-section">

        <button
            onclick="window.print()"
            class="print-btn"
        >
            📄 Export / Save as PDF
        </button>

        <a
            href="index.php"
            class="back-btn"
        >
            ← Back to Reports
        </a>

    </div>


    <div class="footer">

        Generated from Library Management System

    </div>

</div>

</body>

</html>