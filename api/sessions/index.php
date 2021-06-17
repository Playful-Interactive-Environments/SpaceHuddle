<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require "../vendor/autoload.php";

use PieLab\GAB\Controllers\AbstractController;
use PieLab\GAB\Controllers\SessionController;

$session = SessionController::getInstance();

// Send CORS preflight response
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    $session->preflight();
} elseif (AbstractController::isRestCall("GET")) {
    $result = $session->readAll();
    echo $result;
} else {
    echo "Call " . $_SERVER["REQUEST_METHOD"] . " is not yet implemented";
    http_response_code(501);
}
