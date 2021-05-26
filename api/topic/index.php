<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__.'/../controllers/topic.php');
require_once(__DIR__.'/../controllers/task.php');
require_once(__DIR__.'/../controllers/idea.php');
require_once(__DIR__.'/../controllers/group.php');
require_once(__DIR__.'/../controllers/participant.php');
require_once(__DIR__ . '/../controllers/Controller.php');

$topic = TopicController::getInstance();
$task = TaskController::getInstance();
$idea = IdeaController::getInstance();
$group = GroupController::getInstance();
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
elseif (Controller::isRestCall("GET", search_detail_hierarchy: "tasks")) {
	$result = $task->readAll();
	echo $result;
}
elseif (Controller::isRestCall("POST", search_detail_hierarchy: "task")) {
	$result = $task->add();
	echo $result;
}
elseif (Controller::isRestCall("GET", search_detail_hierarchy: "participant_tasks")) {
	$result = $participant->getTopicTasks();
	echo $result;
}
elseif (Controller::isRestCall("GET", search_detail_hierarchy: "ideas")) {
	$result = $idea->readAllFromTopic();
	echo $result;
}
elseif (Controller::isRestCall("POST", search_detail_hierarchy: "idea")) {
	$result = $idea->addToTopic();
	echo $result;
}
elseif (Controller::isRestCall("GET", search_detail_hierarchy: "groups")) {
	$result = $group->readAllFromTopic();
	echo $result;
}
elseif (Controller::isRestCall("POST", search_detail_hierarchy: "group")) {
	$result = $group->addToTopic();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(501);
}
?>
