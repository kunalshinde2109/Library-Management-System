<?php

require_once "config/database.php";

$message = "";
$message_type = "";


// =========================
// REGISTER
// =========================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";


    // =========================
    // INPUT VALIDATION
    // =========================

    if (
        $username === "" ||
        $email === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {

        $message = "Please fill in all fields.";
        $message_type = "error";

    } elseif (strlen($username) < 3 || strlen($username) > 100) {

        $message = "Username must be between 3 and 100 characters.";
        $message_type = "error";

    } elseif (strlen($email) > 255) {

        $message = "Email address is too long.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";
        $message_type = "error";

    } elseif (strlen($password) > 255) {

        $message = "Password is too long.";
        $message_type = "error";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";
        $message_type = "error";

    } else {


        // =========================
        // CHECK EXISTING USER
        // =========================

        $check_stmt = $conn->prepare(
            "SELECT id
             FROM users
             WHERE username = ? OR email = ?
             LIMIT 1"
        );


        if (!$check_stmt) {

            $message =
                "Something went wrong. Please try again.";

            $message_type = "error";

        } else {

            $check_stmt->bind_param(
                "ss",
                $username,
                $email
            );

            $check_stmt->execute();

            $check_result =
                $check_stmt->get_result();


            if (
                $check_result &&
                $check_result->num_rows > 0
            ) {

                $message =
                    "Username or email already exists.";

                $message_type = "error";

            } else {


                // =========================
                // SECURE PASSWORD HASH
                // =========================

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                if ($hashed_password === false) {

                    $message =
                        "Registration failed. Please try again.";

                    $message_type = "error";

                } else {


                    // =========================
                    // INSERT USER
                    // =========================

                    $stmt = $conn->prepare(
                        "INSERT INTO users
                        (username, email, password)
                        VALUES (?, ?, ?)"
                    );


                    if (!$stmt) {

                        $message =
                            "Registration failed. Please try again.";

                        $message_type = "error";

                    } else {

                        $stmt->bind_param(
                            "sss",
                            $username,
                            $email,
                            $hashed_password
                        );


                        if ($stmt->execute()) {

                            $message =
                                "Registration successful! You can now login.";

                            $message_type = "success";

                        } else {

                            // Handle duplicate entry
                            if ($conn->errno === 1062) {

                                $message =
                                    "Username or email already exists.";

                            } else {

                                $message =
                                    "Registration failed. Please try again.";
                            }

                            $message_type = "error";
                        }


                        $stmt->close();
                    }
                }
            }


            $check_stmt->close();
        }
    }
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
        Register - Library Management System
    </title>

    <link
        rel="stylesheet"
        href="css/register.css"
    >

</head>

<body>

    <div class="register-container">

        <div class="register-card">

            <h1>
                Library Management System
            </h1>

            <h2>
                Create Account
            </h2>

            <p class="register-subtitle">
                Register to access the library system
            </p>


            <?php if ($message !== "") { ?>

                <div
                    class="<?php
                        echo $message_type === "success"
                            ? "success-message"
                            : "error-message";
                    ?>"
                >

                    <?php
                    echo htmlspecialchars(
                        $message,
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>

                </div>

            <?php } ?>


            <form method="POST">

                <div class="form-group">

                    <label for="username">
                        Username
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter username"
                        autocomplete="username"
                        minlength="3"
                        maxlength="100"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter email"
                        autocomplete="email"
                        maxlength="255"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimum 6 characters"
                        autocomplete="new-password"
                        minlength="6"
                        maxlength="255"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm your password"
                        autocomplete="new-password"
                        minlength="6"
                        maxlength="255"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="register-btn"
                >
                    Create Account
                </button>

            </form>


            <div class="login-link">

                <span>
                    Already have an account?
                </span>

                <a href="login.php">
                    Login
                </a>

            </div>

        </div>

    </div>

</body>

</html>