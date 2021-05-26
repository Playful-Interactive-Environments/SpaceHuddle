<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__.'/../controllers/task.php');
require_once(__DIR__.'/../controllers/idea.php');
require_once(__DIR__.'/../controllers/group.php');
require_once(__DIR__ . '/../controllers/Controller.php');

$task = TaskController::getInstance();
$idea = IdeaController::getInstance();
$group = GroupController::getInstance();

if (Controller::isRestCall("GET")) {
	$result = $task->read();
	echo $result;
}
elseif (Controller::isRestCall("PUT")) {
	$result = $task->update();
	echo $result;
}
elseif (Controller::isRestCall("DELETE")) {
	$result = $task->delete();
	echo $result;
}
elseif (Controller::isRestCall("GET", search_detail_hierarchy: "ideas")) {
	$result = $idea->readAllFromTask();
	echo $result;
}
elseif (Controller::isRestCall("POST", search_detail_hierarchy: "idea")) {
	$result = $idea->addToTask();
	echo $result;
}
elseif (Controller::isRestCall("GET", search_detail_hierarchy: "groups")) {
	$result = $group->readAllFromTask();
	echo $result;
}
elseif (Controller::isRestCall("POST", search_detail_hierarchy: "group")) {
	$result = $group->addToTask();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(501);
}
?>
