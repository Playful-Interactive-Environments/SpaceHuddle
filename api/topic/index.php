<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require "../vendor/autoload.php";

use PieLab\GAB\Controllers\Controller;
use PieLab\GAB\Controllers\TopicController;
use PieLab\GAB\Controllers\TaskController;
use PieLab\GAB\Controllers\IdeaController;
use PieLab\GAB\Controllers\GroupController;
use PieLab\GAB\Controllers\SelectionController;
use PieLab\GAB\Controllers\ParticipantController;

$topic = TopicController::getInstance();
$task = TaskController::getInstance();
$idea = IdeaController::getInstance();
$group = GroupController::getInstance();
$selection = SelectionController::getInstance();
$participant = ParticipantController::getInstance();

if (Controller::isRestCall("GET")) {
	$result = $topic->read();
	echo $result;
}
elseif (Controller::isRestCall("PUT")) {
	$result = $topic->update();
	echo $result;
}
elseif (Controller::isRestCall("DELETE")) {
	$result = $topic->delete();
	echo $result;
}
elseif (Controller::isRestCall("GET", searchDetailHierarchy: "tasks")) {
	$result = $task->readAll();
	echo $result;
}
elseif (Controller::isRestCall("POST", searchDetailHierarchy: "task")) {
	$result = $task->add();
	echo $result;
}
elseif (Controller::isRestCall("GET", searchDetailHierarchy: "participant_tasks")) {
	$result = $participant->getTopicTasks();
	echo $result;
}
elseif (Controller::isRestCall("GET", searchDetailHierarchy: "ideas")) {
	$result = $idea->readAllFromTopic();
	echo $result;
}
elseif (Controller::isRestCall("POST", searchDetailHierarchy: "idea")) {
	$result = $idea->addToTopic();
	echo $result;
}
elseif (Controller::isRestCall("GET", searchDetailHierarchy: "groups")) {
	$result = $group->readAllFromTopic();
	echo $result;
}
elseif (Controller::isRestCall("POST", searchDetailHierarchy: "group")) {
	$result = $group->addToTopic();
	echo $result;
}
elseif (Controller::isRestCall("GET", searchDetailHierarchy: "selections")) {
	$result = $selection->readAll();
	echo $result;
}
elseif (Controller::isRestCall("POST", searchDetailHierarchy: "selection")) {
	$result = $selection->add();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(501);
}
?>
