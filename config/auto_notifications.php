<?php

function generate_auto_notifications($conn)
{
    /*
     * फक्त issued_books + students + users वापरत आहोत.
     * Book table ची गरज नाही.
     */

    $sql = "
        SELECT
            ib.id,
            ib.due_date,
            u.id AS user_id
        FROM issued_books ib
        INNER JOIN students s
            ON ib.student_id = s.id
        INNER JOIN users u
            ON s.email = u.email
        WHERE ib.return_date IS NULL
          AND ib.due_date IS NOT NULL
    ";

    $result = $conn->query($sql);

    if (!$result) {
        return;
    }

    while ($row = $result->fetch_assoc()) {

        $user_id = (int) $row["user_id"];
        $due_date = $row["due_date"];

        /*
         * TODAY
         */
        if ($due_date == date("Y-m-d")) {

            $message = "Your issued book is due today. Please return the book today.";

            create_auto_notification(
                $conn,
                $user_id,
                $message,
                "due"
            );
        }


        /*
         * TOMORROW
         */
        $tomorrow = date(
            "Y-m-d",
            strtotime("+1 day")
        );

        if ($due_date == $tomorrow) {

            $formatted_date = date(
                "d-m-Y",
                strtotime($due_date)
            );

            $message = "Reminder: Your issued book is due tomorrow ($formatted_date).";

            create_auto_notification(
                $conn,
                $user_id,
                $message,
                "reminder"
            );
        }


        /*
         * OVERDUE
         */
        if ($due_date < date("Y-m-d")) {

            $days_overdue = floor(
                (strtotime(date("Y-m-d")) - strtotime($due_date))
                / 86400
            );

            $message = "Your issued book is overdue by $days_overdue day(s). Please return it as soon as possible.";

            create_auto_notification(
                $conn,
                $user_id,
                $message,
                "overdue"
            );
        }
    }
}


/*
 * Notification create function
 */
function create_auto_notification($conn, $user_id, $message, $type)
{
    /*
     * आज same notification आधीच आहे का?
     */
    $stmt = $conn->prepare("
        SELECT id
        FROM notifications
        WHERE user_id = ?
          AND message = ?
          AND type = ?
          AND DATE(created_at) = CURDATE()
        LIMIT 1
    ");

    if (!$stmt) {
        return;
    }

    $stmt->bind_param(
        "iss",
        $user_id,
        $message,
        $type
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $exists = $result->fetch_assoc();

    $stmt->close();


    if ($exists) {
        return;
    }


    /*
     * Create notification
     */
    $stmt = $conn->prepare("
        INSERT INTO notifications
        (user_id, message, type)
        VALUES (?, ?, ?)
    ");

    if (!$stmt) {
        return;
    }

    $stmt->bind_param(
        "iss",
        $user_id,
        $message,
        $type
    );

    $stmt->execute();

    $stmt->close();
}

?>