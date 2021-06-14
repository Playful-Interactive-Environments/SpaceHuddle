<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require "../vendor/autoload.php";

use PieLab\GAB\Controllers\AbstractController;
use PieLab\GAB\Controllers\ResourceController;
use PieLab\GAB\Controllers\SessionController;
use PieLab\GAB\Controllers\SessionRoleController;
use PieLab\GAB\Controllers\TaskController;
use PieLab\GAB\Controllers\TopicController;

$topic = TopicController::getInstance();
$session = SessionController::getInstance();
$task = TaskController::getInstance();
$resource = ResourceController::getInstance();
$sessionRole = SessionRoleController::getInstance();

if (AbstractController::isRestCall("GET")) {
    $result = $session->read();
    echo $result;
} elseif (AbstractController::isRestCall("POST")) {
    $result = $session->add();
    echo $result;
} elseif (AbstractController::isRestCall("PUT")) {
    $result = $session->update();
    echo $result;
} elseif (AbstractController::isRestCall("DELETE")) {
    $result = $session->delete();
    echo $result;
} elseif (AbstractController::isRestCall("GET", searchDetailHierarchy: "topics")) {
    $result = $topic->readAll();
    echo $result;
} elseif (AbstractController::isRestCall("POST", searchDetailHierarchy: "topic")) {
    $result = $topic->add();
    echo $result;
} elseif (AbstractController::isRestCall("GET", searchDetailHierarchy: "resources")) {
    $result = $resource->readAll();
    echo $result;
} elseif (AbstractController::isRestCall("POST", searchDetailHierarchy: "resource")) {
    $result = $resource->add();
    echo $result;
} elseif (AbstractController::isRestCall("PUT", searchDetailHierarchy: "public_screen")) {
    $result = $session->setPublicScreen();
    echo $result;
} elseif (AbstractController::isRestCall("GET", searchDetailHierarchy: "public_screen")) {
    $result = $task->getPublicScreen();
    echo $result;
} elseif (AbstractController::isRestCall("GET", searchDetailHierarchy: "authorized_users")) {
    $result = $sessionRole->readAll();
    echo $result;
} elseif (AbstractController::isRestCall("POST", searchDetailHierarchy: "authorized_users")) {
    $result = $sessionRole->add();
    echo $result;
} elseif (AbstractController::isRestCall("PUT", searchDetailHierarchy: "authorized_users")) {
    $result = $sessionRole->update();
    echo $result;
} elseif (AbstractController::isRestCall("DELETE", searchDetailHierarchy: "authorized_users")) {
    $result = $sessionRole->delete();
    echo $result;
} elseif (AbstractController::isRestCall("GET", searchDetailHierarchy: "own_user_role")) {
    $result = $sessionRole->read();
    echo $result;
} else {
    echo "Call " . $_SERVER["REQUEST_METHOD"] . " is not yet implemented";
    http_response_code(501);
}
