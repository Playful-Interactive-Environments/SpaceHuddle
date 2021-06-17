<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require "../vendor/autoload.php";

use PieLab\GAB\Controllers\AbstractController;
use PieLab\GAB\Controllers\VotingController;

$voting = VotingController::getInstance();

// Send CORS preflight response
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    $voting->preflight();
} elseif (AbstractController::isRestCall("GET")) {
    $result = $voting->get();
    echo $result;
} elseif (AbstractController::isRestCall("PUT")) {
    $result = $voting->update();
    echo $result;
} elseif (AbstractController::isRestCall("DELETE")) {
    $result = $voting->delete();
    echo $result;
} else {
    echo "Call " . $_SERVER["REQUEST_METHOD"] . " is not yet implemented";
    http_response_code(501);
}
