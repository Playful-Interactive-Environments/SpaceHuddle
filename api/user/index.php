<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require "../vendor/autoload.php";

use PieLab\GAB\Controllers\Controller;
use PieLab\GAB\Controllers\LoginController;

$login = LoginController::getInstance();

if (Controller::isRestCall("POST", 2, search_detail_hierarchy: "login")) {
    $result = $login->login();
    echo $result;
} elseif (Controller::isRestCall("POST", 2, search_detail_hierarchy: "register")) {
    $result = $login->register();
    echo $result;
} elseif (Controller::isRestCall("PUT")) {
    $result = $login->update();
    echo $result;
} elseif (Controller::isRestCall("DELETE")) {
    $result = $login->delete();
    echo $result;
} else {
    echo "Call " . $_SERVER["REQUEST_METHOD"] . " is not yet implemented";
    http_response_code(501);
}
