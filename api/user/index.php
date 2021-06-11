<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require "../vendor/autoload.php";

use PieLab\GAB\Controllers\AbstractController;
use PieLab\GAB\Controllers\LoginController;

$login = LoginController::getInstance();

if (AbstractController::isRestCall("POST", 2, searchDetailHierarchy: "login")) {
    $result = $login->login();
    echo $result;
} elseif (AbstractController::isRestCall("POST", 2, searchDetailHierarchy: "register")) {
    $result = $login->register();
    echo $result;
} elseif (AbstractController::isRestCall("PUT")) {
    $result = $login->update();
    echo $result;
} elseif (AbstractController::isRestCall("DELETE")) {
    $result = $login->delete();
    echo $result;
} elseif (AbstractController::isRestCall("OPTIONS")) {
    $login->preflight();
} else {
    echo "Call " . $_SERVER["REQUEST_METHOD"] . " is not yet implemented";
    http_response_code(501);
}
