<?php

session_start();

header('Content-Type: application/json');

require_once "db.php";

/* Allow Only POST Requests */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        "status" => "error",
        "message" => "Only POST method allowed"
    ]);

    exit();
}

/* Get POST Data */
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

/* Validate Input */
if (empty($email) || empty($password)) {

    http_response_code(400);

    echo json_encode([
        "status" => "error",
        "message" => "Email and password are required"
    ]);

    exit();
}

/* Find User */
$sql = "
    SELECT
        id,
        first_name,
        last_name,
        email,
        password,
        role,
        failed_attempts,
        last_failed_login
    FROM users
    WHERE email = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

/* Check User Exists */
if ($result->num_rows === 0) {

    http_response_code(401);

    echo json_encode([
        "status" => "error",
        "message" => "Invalid email or password"
    ]);

    exit();
}

$user = $result->fetch_assoc();

/* Lockout Configuration */
$max_attempts = 3;
$lockout_time = 180; // seconds

/* Check Lockout */
if (
    $user['failed_attempts'] >= $max_attempts &&
    $user['last_failed_login'] !== null
) {

    $last_failed = strtotime($user['last_failed_login']);
    $current_time = time();

    $time_difference = $current_time - $last_failed;

    if ($time_difference < $lockout_time) {

        $remaining = $lockout_time - $time_difference;

        http_response_code(403);

        echo json_encode([
            "status" => "error",
            "message" => "Account locked. Try again in {$remaining} seconds"
        ]);

        exit();
    }
}

/* Verify Password */
if (password_verify($password, $user['password'])) {

    /* Reset Failed Attempts */
    $reset_sql = "
        UPDATE users
        SET failed_attempts = 0,
            last_failed_login = NULL
        WHERE id = ?
    ";

    $reset_stmt = $conn->prepare($reset_sql);
    $reset_stmt->bind_param("i", $user['id']);
    $reset_stmt->execute();

    /* Create Session */
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['role'] = $user['role'];

    http_response_code(200);

    echo json_encode([
        "status" => "success",
        "message" => "Login successful",

        "user" => [
            "id" => $user['id'],
            "first_name" => $user['first_name'],
            "last_name" => $user['last_name'],
            "email" => $user['email'],
            "role" => $user['role']
        ]
    ]);

} else {

    /* Increment Failed Attempts */
    $failed_attempts = $user['failed_attempts'] + 1;

    $update_sql = "
        UPDATE users
        SET failed_attempts = ?,
            last_failed_login = NOW()
        WHERE id = ?
    ";

    $update_stmt = $conn->prepare($update_sql);

    $update_stmt->bind_param(
        "ii",
        $failed_attempts,
        $user['id']
    );

    $update_stmt->execute();

    $attempts_left = $max_attempts - $failed_attempts;

    /* Check Lock Trigger */
    if ($failed_attempts >= $max_attempts) {

        http_response_code(403);

        echo json_encode([
            "status" => "error",
            "message" => "Account locked for 3 minutes due to multiple failed attempts"
        ]);

    } else {

        http_response_code(401);

        echo json_encode([
            "status" => "error",
            "message" => "Invalid password",
            "attempts_left" => $attempts_left
        ]);
    }
}

$stmt->close();
$conn->close();

?>