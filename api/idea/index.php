<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require "../vendor/autoload.php";

use PieLab\GAB\Controllers\AbstractController;
use PieLab\GAB\Controllers\IdeaController;

$idea = IdeaController::getInstance();

if (AbstractController::isRestCall("GET")) {
    $result = $idea->read();
    echo $result;
} elseif (AbstractController::isRestCall("PUT")) {
    $result = $idea->update();
    echo $result;
} elseif (AbstractController::isRestCall("DELETE")) {
    $result = $idea->delete();
    echo $result;
} else {
    echo "Call " . $_SERVER["REQUEST_METHOD"] . " is not yet implemented";
    http_response_code(501);
}