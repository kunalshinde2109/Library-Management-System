<?php
session_start();

require_once "config/database.php";
require_once "config/auth.php";
require_once "config/notifications.php";
require_once "config/auto_notifications.php";

require_login();

$username = $_SESSION["username"];
$role = $_SESSION["role"];

$profile_photo = "images/profile.jpg";

/* =========================
   GET CURRENT USER
========================= */

$stmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE username = ?
    LIMIT 1
");

$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

$user_id = $user["id"];

/* =========================
   NOTIFICATIONS
========================= */

generate_auto_notifications($conn);

$notification_count = get_notification_count(
    $conn,
    $user_id
);

/* =========================
   DASHBOARD STATS
========================= */

function get_count($conn, $sql)
{
    $result = $conn->query($sql);

    if (!$result) {
        error_log("Dashboard query failed: " . $conn->error);
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int) ($row["total"] ?? 0);
}

$total_books = get_count(
    $conn,
    "SELECT COUNT(*) AS total FROM books"
);

$total_students = get_count(
    $conn,
    "SELECT COUNT(*) AS total FROM students"
);

$current_issued = get_count(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM issued_books
    WHERE return_date IS NULL
    "
);

$returned_books = get_count(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM issued_books
    WHERE return_date IS NOT NULL
    "
);

$available_copies = get_count(
    $conn,
    "
    SELECT COALESCE(SUM(available_copies), 0) AS total
    FROM books
    "
);

$overdue_books = get_count(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM issued_books
    WHERE return_date IS NULL
    AND due_date < CURDATE()
    "
);

/* =========================
   CALCULATIONS
========================= */

$total_copies = $available_copies + $current_issued;

if ($total_copies > 0) {
    $available_percent =
        round(($available_copies / $total_copies) * 100);

    $issued_percent =
        round(($current_issued / $total_copies) * 100);
} else {
    $available_percent = 0;
    $issued_percent = 0;
}

$total_history = $returned_books + $current_issued;

if ($total_history > 0) {
    $returned_percent =
        round(($returned_books / $total_history) * 100);
} else {
    $returned_percent = 0;
}

if ($current_issued > 0) {
    $overdue_percent =
        round(($overdue_books / $current_issued) * 100);
} else {
    $overdue_percent = 0;
}

$student_progress = min($total_students * 10, 100);
$book_progress = min($total_books * 10, 100);

/* =========================
   RECENT ISSUED BOOKS
========================= */

$recent_books = [];

if ($role === "student") {

    $stmt = $conn->prepare("
        SELECT
            ib.id,
            b.book_name,
            s.name AS student_name,
            ib.issue_date,
            ib.due_date,
            ib.return_date
        FROM issued_books ib

        INNER JOIN books b
            ON ib.book_id = b.id

        INNER JOIN students s
            ON ib.student_id = s.id

        INNER JOIN users u
            ON s.email = u.email

        WHERE u.id = ?

        ORDER BY ib.id DESC

        LIMIT 5
    ");

    $stmt->bind_param("i", $user_id);

} else {

    $stmt = $conn->prepare("
        SELECT
            ib.id,
            b.book_name,
            s.name AS student_name,
            ib.issue_date,
            ib.due_date,
            ib.return_date
        FROM issued_books ib

        INNER JOIN books b
            ON ib.book_id = b.id

        INNER JOIN students s
            ON ib.student_id = s.id

        ORDER BY ib.id DESC

        LIMIT 5
    ");
}

if ($stmt) {

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $recent_books[] = $row;
    }

    $stmt->close();
}

/* =========================
   AVATAR LETTER
========================= */

$avatar_letter = strtoupper(
    substr($username, 0, 1)
);

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
        Dashboard - Library Management System
    </title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <style>

        /* =========================
           PROFILE PHOTO
        ========================= */

        .profile-avatar {

            width: 42px;
            height: 42px;

            border-radius: 50%;

            overflow: hidden;

            border: 2px solid rgba(255,255,255,0.8);

            box-shadow:
                0 5px 15px rgba(15,23,42,0.15);

            flex-shrink: 0;

            background: #e2e8f0;
        }

        .profile-avatar img {

            width: 100%;
            height: 100%;

            object-fit: cover;

            display: block;
        }

        /* =========================
           TOP WELCOME PANEL
        ========================= */

        .dashboard-welcome {

            position: relative;

            overflow: hidden;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 25px;

            margin-top: 5px;
            margin-bottom: 28px;

            padding: 28px 32px;

            min-height: 190px;

            border-radius: 22px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #4338ca
                );

            color: white;

            box-shadow:
                0 18px 40px rgba(37,99,235,0.22);
        }

        .welcome-profile {

            display: flex;

            align-items: center;

            gap: 25px;

            position: relative;

            z-index: 2;
        }

        .welcome-profile-photo {

            width: 125px;
            height: 125px;

            border-radius: 50%;

            overflow: hidden;

            flex-shrink: 0;

            border: 5px solid rgba(255,255,255,0.9);

            box-shadow:
                0 12px 30px rgba(15,23,42,0.25);

            background: #e2e8f0;
        }

        .welcome-profile-photo img {

            width: 100%;
            height: 100%;

            object-fit: cover;

            display: block;
        }

        .welcome-profile-info h2 {

            margin: 0 0 8px;

            font-size: 28px;

            font-weight: 800;

            letter-spacing: -0.5px;
        }

        .welcome-profile-info p {

            margin: 0 0 13px;

            max-width: 600px;

            font-size: 14px;

            line-height: 1.7;

            color: rgba(255,255,255,0.88);
        }

        .profile-role {

            display: inline-flex;

            align-items: center;

            padding: 7px 13px;

            border-radius: 20px;

            background: rgba(255,255,255,0.16);

            border: 1px solid rgba(255,255,255,0.25);

            font-size: 11px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.7px;
        }

        .welcome-book {

            position: relative;

            z-index: 2;

            font-size: 90px;

            line-height: 1;

            opacity: 0.95;

            filter:
                drop-shadow(
                    0 12px 20px rgba(0,0,0,0.18)
                );
        }

        .dashboard-welcome::before {

            content: "";

            position: absolute;

            width: 250px;
            height: 250px;

            right: -70px;
            top: -100px;

            border-radius: 50%;

            background:
                rgba(255,255,255,0.08);
        }

        .dashboard-welcome::after {

            content: "";

            position: absolute;

            width: 180px;
            height: 180px;

            left: 38%;

            bottom: -120px;

            border-radius: 50%;

            background:
                rgba(255,255,255,0.06);
        }

        /* =========================
           PREMIUM STATS
        ========================= */

        .stat-card {

            position: relative;

            overflow: hidden;

            border:
                1px solid rgba(226,232,240,0.8);

            backdrop-filter: blur(8px);

            transition: 0.25s ease;
        }

        .stat-card::after {

            content: "";

            position: absolute;

            width: 90px;
            height: 90px;

            right: -35px;
            top: -35px;

            border-radius: 50%;

            background:
                rgba(37,99,235,0.06);
        }

        .stat-card:hover {

            transform:
                translateY(-4px);
        }

        .stat-card strong {

            font-size: 28px;

            font-weight: 800;
        }

        .stat-card span {

            font-weight: 600;
        }

        /* =========================
           QUICK ACCESS
        ========================= */

        .quick-card {

            position: relative;

            overflow: hidden;

            border:
                1px solid rgba(226,232,240,0.8);

            backdrop-filter: blur(8px);
        }

        .quick-card::before {

            content: "";

            position: absolute;

            left: 0;
            top: 0;
            bottom: 0;

            width: 4px;

            background:
                linear-gradient(
                    180deg,
                    #087cff,
                    #4338ca
                );

            opacity: 0;

            transition: 0.25s ease;
        }

        .quick-card:hover::before {

            opacity: 1;
        }

        .quick-icon {

            transition: 0.25s ease;
        }

        .quick-card:hover .quick-icon {

            transform:
                scale(1.08)
                rotate(-3deg);
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {

            box-shadow:
                8px 0 30px rgba(15,23,42,0.12);
        }

        .nav-item {

            position: relative;
        }

        .nav-item.active::before {

            content: "";

            position: absolute;

            left: 0;

            top: 10px;

            bottom: 10px;

            width: 3px;

            background: white;

            border-radius: 5px;
        }

        /* =========================
           NOTIFICATION
        ========================= */

        .notification-button {

            border:
                1px solid rgba(226,232,240,0.8);

            transition: 0.25s ease;
        }

        .notification-button:hover {

            box-shadow:
                0 10px 25px rgba(15,23,42,0.14);
        }

        /* =========================
           RECENT SECTION
        ========================= */

        .recent-section {

            margin-top: 32px;

            margin-bottom: 30px;
        }

        .recent-heading {

            display: flex;

            align-items: flex-end;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 15px;
        }

        .recent-label {

            display: block;

            margin-bottom: 5px;

            font-size: 10px;

            font-weight: 800;

            letter-spacing: 1.2px;

            color: #64748b;
        }

        .recent-heading h2 {

            margin: 0;

            font-size: 22px;

            font-weight: 800;

            color: #0f172a;
        }

        .view-all-btn {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 9px 15px;

            border-radius: 10px;

            background: #eff6ff;

            color: #2563eb;

            text-decoration: none;

            font-size: 12px;

            font-weight: 700;

            transition: 0.25s ease;
        }

        .view-all-btn:hover {

            background: #2563eb;

            color: white;

            transform:
                translateY(-2px);

            box-shadow:
                0 8px 18px rgba(37,99,235,0.22);
        }

        .recent-card {

            background:
                rgba(255,255,255,0.94);

            border:
                1px solid rgba(226,232,240,0.85);

            border-radius: 18px;

            padding: 5px;

            box-shadow:
                0 12px 35px rgba(15,23,42,0.08);

            overflow-x: auto;
        }

        .recent-table {

            width: 100%;

            min-width: 780px;

            border-collapse: separate;

            border-spacing: 0;
        }

        .recent-table th {

            padding: 15px 16px;

            text-align: left;

            background: #f8fafc;

            color: #64748b;

            font-size: 11px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.5px;

            border-bottom:
                1px solid #e2e8f0;
        }

        .recent-table th:first-child {

            border-radius:
                13px 0 0 0;
        }

        .recent-table th:last-child {

            border-radius:
                0 13px 0 0;
        }

        .recent-table td {

            padding: 15px 16px;

            font-size: 13px;

            color: #334155;

            border-bottom:
                1px solid #f1f5f9;

            transition: 0.2s ease;
        }

        .recent-table tbody tr {

            transition: 0.2s ease;
        }

        .recent-table tbody tr:hover {

            background: #f8fafc;
        }

        .recent-table tbody tr:last-child td {

            border-bottom: none;
        }

        .recent-book {

            display: flex;

            align-items: center;

            gap: 11px;
        }

        .recent-book-icon {

            width: 38px;
            height: 38px;

            flex-shrink: 0;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 11px;

            background: #eff6ff;

            font-size: 18px;
        }

        .recent-book strong {

            display: block;

            max-width: 190px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

            color: #0f172a;

            font-size: 13px;
        }

        .recent-book small {

            display: block;

            margin-top: 3px;

            color: #94a3b8;

            font-size: 10px;
        }

        .recent-student {

            display: flex;

            align-items: center;

            gap: 9px;
        }

        .student-mini-icon {

            width: 30px;
            height: 30px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #f1f5f9;

            font-size: 14px;
        }

        .date-text {

            color: #475569;

            font-size: 12px;

            font-weight: 600;
        }

        .status-badge {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 7px 11px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 800;
        }

        .status-dot {

            width: 6px;
            height: 6px;

            border-radius: 50%;

            background: currentColor;
        }

        .status-issued {

            background: #dbeafe;

            color: #2563eb;
        }

        .status-returned {

            background: #dcfce7;

            color: #15803d;
        }

        .status-overdue {

            background: #fee2e2;

            color: #dc2626;
        }

        .empty-recent {

            min-height: 180px;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            gap: 5px;

            text-align: center;

            color: #64748b;
        }

        .empty-icon {

            font-size: 35px;

            margin-bottom: 5px;
        }

        .empty-recent strong {

            color: #334155;

            font-size: 14px;
        }

        .empty-recent span {

            font-size: 12px;
        }

        /* =========================
           MOBILE MENU
        ========================= */

        .mobile-menu-btn {

            display: none;

            position: fixed;

            top: 15px;
            left: 15px;

            z-index: 2000;

            width: 45px;
            height: 45px;

            border: none;

            border-radius: 12px;

            background: #2563eb;

            color: white;

            font-size: 22px;

            cursor: pointer;

            box-shadow:
                0 8px 20px rgba(37,99,235,0.3);
        }

        .sidebar-overlay {

            display: none;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .sidebar {

                width: 220px;
            }

            .dashboard-main {

                margin-left: 220px;
            }

            .stats-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }

            .quick-grid {

                grid-template-columns:
                    1fr;
            }
        }

        @media (max-width: 700px) {

            .mobile-menu-btn {

                display: flex;

                align-items: center;

                justify-content: center;
            }

            .sidebar {

                position: fixed;

                left: -270px;

                top: 0;

                width: 250px;

                height: 100vh;

                z-index: 1500;

                transition:
                    left 0.3s ease;
            }

            .sidebar.mobile-open {

                left: 0;
            }

            .sidebar-overlay {

                display: block;

                position: fixed;

                inset: 0;

                background:
                    rgba(15,23,42,0.45);

                z-index: 1400;

                opacity: 0;

                pointer-events: none;

                transition:
                    opacity 0.3s ease;
            }

            .sidebar-overlay.active {

                opacity: 1;

                pointer-events: auto;
            }

            .dashboard-main {

                margin-left: 0;

                width: 100%;

                padding-top: 70px;
            }

            .dashboard-topbar {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;
            }

            .stats-grid {

                grid-template-columns:
                    1fr;
            }

            .dashboard-welcome {

                padding: 24px 20px;

                min-height: auto;
            }

            .welcome-profile {

                gap: 18px;
            }

            .welcome-profile-photo {

                width: 90px;
                height: 90px;
            }

            .welcome-profile-info h2 {

                font-size: 22px;
            }

            .welcome-profile-info p {

                font-size: 12px;
            }

            .welcome-book {

                display: none;
            }

            .recent-card {

                overflow-x: auto;
            }
        }

        @media (max-width: 450px) {

            .dashboard-main {

                padding-left: 15px;

                padding-right: 15px;
            }

            .dashboard-welcome {

                padding: 20px 16px;

                border-radius: 18px;
            }

            .welcome-profile-photo {

                width: 75px;
                height: 75px;

                border-width: 4px;
            }

            .welcome-profile-info h2 {

                font-size: 19px;
            }

            .welcome-profile-info p {

                font-size: 11px;
            }

            .recent-heading h2 {

                font-size: 18px;
            }
        }

    </style>

</head>

<body>

<!-- =========================
     MOBILE MENU BUTTON
========================= -->

<button
    type="button"
    class="mobile-menu-btn"
    id="mobileMenuBtn"
    aria-label="Open menu"
>
    ☰
</button>

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<!-- =========================
     DASHBOARD LAYOUT
========================= -->

<div class="dashboard-layout">


    <!-- =========================
         SIDEBAR
    ========================= -->

    <aside class="sidebar">

        <div class="sidebar-logo">

            <div class="profile-avatar">

                <img
                    src="<?= htmlspecialchars(
                        $profile_photo,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    alt="Profile Photo"
                >

            </div>

            <span>
                LMS
            </span>

        </div>


        <nav class="sidebar-nav">

            <a
                href="dashboard.php"
                class="nav-item active"
            >
                🏠
                <span>Dashboard</span>
            </a>


            <a
                href="books/view_books.php"
                class="nav-item"
            >
                📚
                <span>Books</span>
            </a>


            <?php if ($role !== "student"): ?>

                <a
                    href="students/view_student.php"
                    class="nav-item"
                >
                    👨‍🎓
                    <span>Students</span>
                </a>


                <a
                    href="issue/issue_book.php"
                    class="nav-item"
                >
                    📖
                    <span>Issue Book</span>
                </a>


                <a
                    href="return/return_book.php"
                    class="nav-item"
                >
                    🔄
                    <span>Return Book</span>
                </a>


                <a
                    href="reports/index.php"
                    class="nav-item"
                >
                    📊
                    <span>Reports</span>
                </a>

            <?php endif; ?>


            <?php if ($role === "admin"): ?>

                <a
                    href="admin/index.php"
                    class="nav-item"
                >
                    ⚙️
                    <span>Admin Panel</span>
                </a>

            <?php endif; ?>

        </nav>


        <div class="sidebar-bottom">

            <a
                href="notifications.php"
                class="nav-item"
            >
                🔔
                <span>Notifications</span>

                <?php if ($notification_count > 0): ?>

                    <span class="notification-count">
                        <?= $notification_count ?>
                    </span>

                <?php endif; ?>

            </a>


            <a
                href="logout.php"
                class="nav-item"
            >
                🚪
                <span>Logout</span>
            </a>

        </div>

    </aside>


    <!-- =========================
         MAIN
    ========================= -->

    <main class="dashboard-main">


        <!-- =========================
             TOPBAR
        ========================= -->

        <div class="dashboard-topbar">

            <div>

                <span class="dashboard-label">
                    LIBRARY MANAGEMENT SYSTEM
                </span>

                <h1>
                    Dashboard
                </h1>

                <p>
                    Manage your library easily and efficiently.
                </p>

            </div>


            <div class="topbar-actions">

                <a
                    href="notifications.php"
                    class="notification-button"
                >

                    🔔

                    <?php if ($notification_count > 0): ?>

                        <span class="notification-count">
                            <?= $notification_count ?>
                        </span>

                    <?php endif; ?>

                </a>


                <div class="topbar-avatar profile-avatar">

                    <img
                        src="<?= htmlspecialchars(
                            $profile_photo,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        alt="Profile Photo"
                    >

                </div>

            </div>

        </div>


        <!-- =========================
             🔥 WELCOME PANEL
             TOP OF DASHBOARD
        ========================= -->

        <section class="dashboard-welcome">

            <div class="welcome-profile">

                <div class="welcome-profile-photo">

                    <img
                        src="<?= htmlspecialchars(
                            $profile_photo,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        alt="Profile Photo"
                    >

                </div>


                <div class="welcome-profile-info">

                    <h2>

                        Welcome,

                        <?= htmlspecialchars(
                            $username,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                        👋

                    </h2>


                    <p>
                        Manage books, students, book issues,
                        returns and reports from one place.
                    </p>


                    <span class="profile-role">

                        <?= htmlspecialchars(
                            ucfirst($role),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </span>

                </div>

            </div>


            <div class="welcome-book">
                📚
            </div>

        </section>


        <!-- =========================
             STATISTICS
        ========================= -->

        <section class="stats-grid">


            <div class="stat-card">

                <div class="stat-icon">
                    📚
                </div>

                <strong>
                    <?= $total_books ?>
                </strong>

                <span>
                    Total Books
                </span>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    👨‍🎓
                </div>

                <strong>
                    <?= $total_students ?>
                </strong>

                <span>
                    Total Students
                </span>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    📖
                </div>

                <strong>
                    <?= $current_issued ?>
                </strong>

                <span>
                    Currently Issued
                </span>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    🔄
                </div>

                <strong>
                    <?= $returned_books ?>
                </strong>

                <span>
                    Returned Books
                </span>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    📦
                </div>

                <strong>
                    <?= $available_copies ?>
                </strong>

                <span>
                    Available Copies
                </span>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ⚠️
                </div>

                <strong>
                    <?= $overdue_books ?>
                </strong>

                <span>
                    Overdue Books
                </span>

            </div>

        </section>


        <!-- =========================
             QUICK ACCESS
        ========================= -->

        <section class="quick-section">

            <div class="section-heading">

                <div>

                    <span class="dashboard-label">
                        QUICK ACCESS
                    </span>

                    <h2>
                        Manage Library
                    </h2>

                </div>

            </div>


            <div class="quick-grid">


                <a
                    href="books/view_books.php"
                    class="quick-card"
                >

                    <div class="quick-icon">
                        📚
                    </div>

                    <div>

                        <strong>
                            Books
                        </strong>

                        <span>
                            View and manage books
                        </span>

                    </div>

                    <div class="quick-arrow">
                        →
                    </div>

                </a>


                <?php if ($role !== "student"): ?>

                    <a
                        href="students/view_student.php"
                        class="quick-card"
                    >

                        <div class="quick-icon">
                            👨‍🎓
                        </div>

                        <div>

                            <strong>
                                Students
                            </strong>

                            <span>
                                Manage student records
                            </span>

                        </div>

                        <div class="quick-arrow">
                            →
                        </div>

                    </a>


                    <a
                        href="issue/issue_book.php"
                        class="quick-card"
                    >

                        <div class="quick-icon">
                            📖
                        </div>

                        <div>

                            <strong>
                                Issue Book
                            </strong>

                            <span>
                                Issue books to students
                            </span>

                        </div>

                        <div class="quick-arrow">
                            →
                        </div>

                    </a>


                    <a
                        href="return/return_book.php"
                        class="quick-card"
                    >

                        <div class="quick-icon">
                            🔄
                        </div>

                        <div>

                            <strong>
                                Return Book
                            </strong>

                            <span>
                                Process returned books
                            </span>

                        </div>

                        <div class="quick-arrow">
                            →
                        </div>

                    </a>


                    <a
                        href="reports/index.php"
                        class="quick-card"
                    >

                        <div class="quick-icon">
                            📊
                        </div>

                        <div>

                            <strong>
                                Reports
                            </strong>

                            <span>
                                View library reports
                            </span>

                        </div>

                        <div class="quick-arrow">
                            →
                        </div>

                    </a>

                <?php endif; ?>


                <?php if ($role === "admin"): ?>

                    <a
                        href="admin/index.php"
                        class="quick-card"
                    >

                        <div class="quick-icon">
                            ⚙️
                        </div>

                        <div>

                            <strong>
                                Admin Panel
                            </strong>

                            <span>
                                Manage users and system
                            </span>

                        </div>

                        <div class="quick-arrow">
                            →
                        </div>

                    </a>

                <?php endif; ?>

            </div>

        </section>


        <!-- =========================
             RECENT ISSUED BOOKS
        ========================= -->

        <section class="recent-section">

            <div class="recent-heading">

                <div>

                    <span class="recent-label">
                        RECENT ACTIVITY
                    </span>

                    <h2>
                        Recent Issued Books
                    </h2>

                </div>


                <?php if ($role !== "student"): ?>

                    <a
                        href="issue/issued_books.php"
                        class="view-all-btn"
                    >
                        View All →
                    </a>

                <?php endif; ?>

            </div>


            <div class="recent-card">

                <?php if (!empty($recent_books)): ?>

                    <table class="recent-table">

                        <thead>

                            <tr>

                                <th>
                                    Book
                                </th>

                                <th>
                                    Student
                                </th>

                                <th>
                                    Issue Date
                                </th>

                                <th>
                                    Due Date
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($recent_books as $book): ?>

                                <?php

                                $status = "Issued";
                                $status_class = "status-issued";

                                if (!empty($book["return_date"])) {

                                    $status = "Returned";
                                    $status_class =
                                        "status-returned";

                                } elseif (
                                    !empty($book["due_date"]) &&
                                    $book["due_date"] < date("Y-m-d")
                                ) {

                                    $status = "Overdue";
                                    $status_class =
                                        "status-overdue";
                                }

                                ?>

                                <tr>

                                    <td>

                                        <div class="recent-book">

                                            <div class="recent-book-icon">
                                                📚
                                            </div>

                                            <div>

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $book["book_name"],
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>

                                                </strong>

                                                <small>
                                                    Issue ID:
                                                    <?= (int) $book["id"] ?>
                                                </small>

                                            </div>

                                        </div>

                                    </td>


                                    <td>

                                        <div class="recent-student">

                                            <div class="student-mini-icon">
                                                👤
                                            </div>

                                            <span>

                                                <?= htmlspecialchars(
                                                    $book["student_name"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>

                                            </span>

                                        </div>

                                    </td>


                                    <td>

                                        <span class="date-text">

                                            <?= htmlspecialchars(
                                                $book["issue_date"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span class="date-text">

                                            <?= htmlspecialchars(
                                                $book["due_date"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="status-badge <?= $status_class ?>"
                                        >

                                            <span class="status-dot"></span>

                                            <?= $status ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                <?php else: ?>

                    <div class="empty-recent">

                        <div class="empty-icon">
                            📚
                        </div>

                        <strong>
                            No issued books yet
                        </strong>

                        <span>
                            Recent book activity will appear here.
                        </span>

                    </div>

                <?php endif; ?>

            </div>

        </section>


        <!-- =========================
             WELCOME MESSAGE
        ========================= -->

        <div class="dashboard-bottom">

            <div>

                <span>
                    Library Status
                </span>

                <strong>
                    <?= $available_percent ?>%
                </strong>

                <small>
                    Books currently available
                </small>

            </div>


            <div>

                <span>
                    Issued
                </span>

                <strong>
                    <?= $issued_percent ?>%
                </strong>

                <small>
                    Copies currently issued
                </small>

            </div>


            <div>

                <span>
                    Overdue
                </span>

                <strong>
                    <?= $overdue_percent ?>%
                </strong>

                <small>
                    Active issues overdue
                </small>

            </div>

        </div>


    </main>

</div>


<!-- =========================
     MOBILE MENU JS
========================= -->

<script>

const mobileMenuBtn =
    document.getElementById("mobileMenuBtn");

const sidebar =
    document.querySelector(".sidebar");

const sidebarOverlay =
    document.getElementById("sidebarOverlay");


if (
    mobileMenuBtn &&
    sidebar &&
    sidebarOverlay
) {

    mobileMenuBtn.addEventListener(
        "click",
        function () {

            sidebar.classList.toggle(
                "mobile-open"
            );

            sidebarOverlay.classList.toggle(
                "active"
            );

        }
    );


    sidebarOverlay.addEventListener(
        "click",
        function () {

            sidebar.classList.remove(
                "mobile-open"
            );

            sidebarOverlay.classList.remove(
                "active"
            );

        }
    );


    const sidebarLinks =
        document.querySelectorAll(
            ".sidebar .nav-item"
        );


    sidebarLinks.forEach(
        function (link) {

            link.addEventListener(
                "click",
                function () {

                    sidebar.classList.remove(
                        "mobile-open"
                    );

                    sidebarOverlay.classList.remove(
                        "active"
                    );

                }
            );

        }
    );

}

</script>

</body>

</html>