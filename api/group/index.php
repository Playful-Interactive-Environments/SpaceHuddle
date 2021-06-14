<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require "../vendor/autoload.php";

use PieLab\GAB\Controllers\AbstractController;
use PieLab\GAB\Controllers\GroupController;
use PieLab\GAB\Controllers\GroupIdeaController;

$group = GroupController::getInstance();
$group_idea = GroupIdeaController::getInstance();

if (AbstractController::isRestCall("GET")) {
    $result = $group->read();
    echo $result;
} elseif (AbstractController::isRestCall("PUT")) {
    $result = $group->update();
    echo $result;
} elseif (AbstractController::isRestCall("DELETE")) {
    $result = $group->delete();
    echo $result;
} elseif (AbstractController::isRestCall("GET", searchDetailHierarchy: "ideas")) {
    $result = $group_idea->readIdeas();
    echo $result;
} elseif (AbstractController::isRestCall("POST", searchDetailHierarchy: "ideas")) {
    $result = $group_idea->addIdeas();
    echo $result;
} elseif (AbstractController::isRestCall("DELETE", searchDetailHierarchy: "ideas")) {
    $result = $group_idea->deleteIdeas();
    echo $result;
} elseif (AbstractController::isRestCall("OPTIONS")) {
    $group->preflight();
} else {
    echo "Call " . $_SERVER["REQUEST_METHOD"] . " is not yet implemented";
    http_response_code(501);
}
