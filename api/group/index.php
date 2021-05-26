<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__.'/../controllers/group.php');
require_once(__DIR__.'/../controllers/group_idea.php');
require_once(__DIR__.'/../controllers/controller.php');

$group = GroupController::getInstance();
$group_idea = GroupIdeaController::getInstance();

if (Controller::isRestCall("GET")) {
	$result = $group->read();
	echo $result;
}
elseif (Controller::isRestCall("PUT")) {
	$result = $group->update();
	echo $result;
}
elseif (Controller::isRestCall("DELETE")) {
	$result = $group->delete();
	echo $result;
}
elseif (Controller::isRestCall("GET", search_detail_hierarchy: "ideas")) {
	$result = $group_idea->readIdeas();
	echo $result;
}
elseif (Controller::isRestCall("POST", search_detail_hierarchy: "ideas")) {
	$result = $group_idea->addIdeas();
	echo $result;
}
elseif (Controller::isRestCall("DELETE", search_detail_hierarchy: "ideas")) {
	$result = $group_idea->deleteIdeas();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(501);
}
?>
