<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require "../vendor/autoload.php";

use PieLab\GAB\Controllers\AbstractController;
use PieLab\GAB\Controllers\ClientController;
use PieLab\GAB\Controllers\GroupController;
use PieLab\GAB\Controllers\IdeaController;
use PieLab\GAB\Controllers\TaskController;
use PieLab\GAB\Controllers\VotingController;

$task = TaskController::getInstance();
$idea = IdeaController::getInstance();
$group = GroupController::getInstance();
$voting = VotingController::getInstance();
$client = ClientController::getInstance();

if (AbstractController::isRestCall("GET")) {
    $result = $task->read();
    echo $result;
} elseif (AbstractController::isRestCall("PUT")) {
    $result = $task->update();
    echo $result;
} elseif (AbstractController::isRestCall("DELETE")) {
    $result = $task->delete();
    echo $result;
} elseif (AbstractController::isRestCall("GET", searchDetailHierarchy: "ideas")) {
    $result = $idea->readAllFromTask();
    echo $result;
} elseif (AbstractController::isRestCall("POST", searchDetailHierarchy: "idea")) {
    $result = $idea->addToTask();
    echo $result;
} elseif (AbstractController::isRestCall("GET", searchDetailHierarchy: "groups")) {
    $result = $group->readAllFromTask();
    echo $result;
} elseif (AbstractController::isRestCall("POST", searchDetailHierarchy: "group")) {
    $result = $group->addToTask();
    echo $result;
} elseif (AbstractController::isRestCall("GET", searchDetailHierarchy: "votings")) {
    $result = $voting->getAll();
    echo $result;
} elseif (AbstractController::isRestCall("GET", searchDetailHierarchy: "voting_result")) {
    $result = $voting->votingResult();
    echo $result;
} elseif (AbstractController::isRestCall("POST", searchDetailHierarchy: "voting")) {
    $result = $voting->add();
    echo $result;
} elseif (AbstractController::isRestCall("PUT", searchDetailHierarchy: "client_application_state")) {
    $result = $client->setClient();
    echo $result;
} else {
    echo "Call " . $_SERVER["REQUEST_METHOD"] . " is not yet implemented";
    http_response_code(501);
}
