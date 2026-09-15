<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: ../login.php");
    exit();
}

include "../config/database.php";

/*
|--------------------------------------------------------------------------
| Get Report Data
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| Check ZIP Extension
|--------------------------------------------------------------------------
*/

if (!class_exists("ZipArchive")) {
    die("
        <h2>ZIP Extension Not Enabled</h2>
        <p>Please enable the PHP ZIP extension in XAMPP.</p>
    ");
}


/*
|--------------------------------------------------------------------------
| Create XLSX File
|--------------------------------------------------------------------------
*/

$zip = new ZipArchive();

$tempFile = tempnam(sys_get_temp_dir(), "library_excel_");

if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Unable to create Excel file.");
}


/*
|--------------------------------------------------------------------------
| XML Escape Function
|--------------------------------------------------------------------------
*/

function xmlEscape($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_XML1 | ENT_QUOTES,
        "UTF-8"
    );
}


/*
|--------------------------------------------------------------------------
| Prepare Sheet Rows
|--------------------------------------------------------------------------
*/

$rows = [];


/* Header Row */

$rows[] = [
    "ID",
    "Book",
    "Student",
    "Issue Date",
    "Due Date",
    "Return Date",
    "Fine",
    "Status"
];


/* Data Rows */

if ($result && $result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

        if (!empty($row["return_date"])) {

            $status = "Returned";

        } elseif (
            !empty($row["due_date"]) &&
            $row["due_date"] < date("Y-m-d")
        ) {

            $status = "Overdue";

        } else {

            $status = "Issued";
        }


        $rows[] = [
            $row["id"],
            $row["book_name"] ?? "Unknown",
            $row["student_name"] ?? "Unknown",
            $row["issue_date"] ?? "",
            $row["due_date"] ?? "-",
            $row["return_date"] ?? "-",
            "₹" . number_format(
                (float)($row["fine"] ?? 0),
                2
            ),
            $status
        ];
    }
}


/*
|--------------------------------------------------------------------------
| Create Sheet XML
|--------------------------------------------------------------------------
*/

$sheetData = "";

foreach ($rows as $rowIndex => $row) {

    $excelRow = $rowIndex + 1;

    $sheetData .= '<row r="' . $excelRow . '">';

    foreach ($row as $columnIndex => $value) {

        $columnLetter = "";

        $number = $columnIndex + 1;

        while ($number > 0) {

            $remainder = ($number - 1) % 26;

            $columnLetter =
                chr(65 + $remainder) . $columnLetter;

            $number =
                intval(($number - 1) / 26);
        }

        $cellReference = $columnLetter . $excelRow;

        $escapedValue = xmlEscape($value);

        $sheetData .= '
            <c r="' . $cellReference . '" t="inlineStr">
                <is>
                    <t>' . $escapedValue . '</t>
                </is>
            </c>
        ';
    }

    $sheetData .= '</row>';
}


/*
|--------------------------------------------------------------------------
| XLSX Files
|--------------------------------------------------------------------------
*/


$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">

    <Default Extension="rels"
        ContentType="application/vnd.openxmlformats-package.relationships+xml"/>

    <Default Extension="xml"
        ContentType="application/xml"/>

    <Override PartName="/xl/workbook.xml"
        ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>

    <Override PartName="/xl/worksheets/sheet1.xml"
        ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>

</Types>';


$rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships
    xmlns="http://schemas.openxmlformats.org/package/2006/relationships">

    <Relationship
        Id="rId1"
        Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"
        Target="xl/workbook.xml"/>

</Relationships>';


$workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships
    xmlns="http://schemas.openxmlformats.org/package/2006/relationships">

    <Relationship
        Id="rId1"
        Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"
        Target="worksheets/sheet1.xml"/>

</Relationships>';


$workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook
    xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
    xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">

    <sheets>

        <sheet
            name="Library Report"
            sheetId="1"
            r:id="rId1"/>

    </sheets>

</workbook>';


$worksheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet
    xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">

    <sheetData>
        ' . $sheetData . '
    </sheetData>

</worksheet>';


/*
|--------------------------------------------------------------------------
| Add Files To XLSX
|--------------------------------------------------------------------------
*/

$zip->addFromString(
    "[Content_Types].xml",
    $contentTypes
);

$zip->addFromString(
    "_rels/.rels",
    $rootRels
);

$zip->addFromString(
    "xl/_rels/workbook.xml.rels",
    $workbookRels
);

$zip->addFromString(
    "xl/workbook.xml",
    $workbook
);

$zip->addFromString(
    "xl/worksheets/sheet1.xml",
    $worksheet
);


/*
|--------------------------------------------------------------------------
| Close ZIP
|--------------------------------------------------------------------------
*/

$zip->close();


/*
|--------------------------------------------------------------------------
| Download XLSX
|--------------------------------------------------------------------------
*/

$filename =
    "Library_Report_" .
    date("Y-m-d") .
    ".xlsx";

header(
    "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
);

header(
    'Content-Disposition: attachment; filename="' .
    $filename .
    '"'
);

header("Content-Length: " . filesize($tempFile));

header("Cache-Control: max-age=0");

readfile($tempFile);

unlink($tempFile);

exit();
?>