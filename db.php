<?php

header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$password = "";
$database = "syrn_db";

/* Create MySQLi Connection */
$conn = new mysqli($host, $user, $password, $database);

/* Check Connection */
if ($conn->connect_error) {

    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed",
        "error" => $conn->connect_error
    ]);

    exit();
}

/* Set UTF-8 */
$conn->set_charset("utf8mb4");

?>