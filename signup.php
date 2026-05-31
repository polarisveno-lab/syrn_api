<?php

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
$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$password   = trim($_POST['password'] ?? '');
$role       = trim($_POST['role'] ?? 'user');

/* Validate Inputs */
if (
    empty($first_name) ||
    empty($last_name) ||
    empty($email) ||
    empty($password)
) {

    http_response_code(400);

    echo json_encode([
        "status" => "error",
        "message" => "All required fields must be filled"
    ]);

    exit();
}

/* Validate Role */
if ($role !== 'user' && $role !== 'admin') {
    $role = 'user';
}

/* Check Existing Email */
$check_sql = "SELECT id FROM users WHERE email = ?";
$check_stmt = $conn->prepare($check_sql);

$check_stmt->bind_param("s", $email);
$check_stmt->execute();

$result = $check_stmt->get_result();

if ($result->num_rows > 0) {

    http_response_code(409);

    echo json_encode([
        "status" => "error",
        "message" => "Email already exists"
    ]);

    exit();
}

/* Hash Password */
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

/* Insert User */
$insert_sql = "
    INSERT INTO users
    (first_name, last_name, email, password, role)
    VALUES (?, ?, ?, ?, ?)
";

$insert_stmt = $conn->prepare($insert_sql);

$insert_stmt->bind_param(
    "sssss",
    $first_name,
    $last_name,
    $email,
    $hashed_password,
    $role
);

/* Execute Insert */
if ($insert_stmt->execute()) {

    http_response_code(201);

    echo json_encode([
        "status" => "success",
        "message" => "User registered successfully"
    ]);

} else {

    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "message" => "Registration failed"
    ]);
}

$insert_stmt->close();
$check_stmt->close();
$conn->close();

?>