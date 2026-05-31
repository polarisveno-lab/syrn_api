<?php

session_start();

header('Content-Type: application/json');

/* Clear Session Variables */
$_SESSION = [];

/* Destroy Session */
session_destroy();

/* Return JSON Response */
http_response_code(200);

echo json_encode([
    "status" => "success",
    "message" => "Logged out successfully"
]);

?>