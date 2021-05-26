<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__.'/../controllers/participant.php');
require_once(__DIR__.'/../controllers/controller.php');


$participant = ParticipantController::getInstance();
if (Controller::isRestCall("GET", 2, search_detail_hierarchy: "connect")) {
	$result = $participant->reconnect();
	echo $result;
}
elseif (Controller::isRestCall("POST", 2, search_detail_hierarchy: "connect")) {
	$result = $participant->connect();
	echo $result;
}
elseif (Controller::isRestCall("GET", 2, search_detail_hierarchy: "tasks")) {
	$result = $participant->getTasks();
	echo $result;
}
elseif (Controller::isRestCall("GET", 2, search_detail_hierarchy: "topics")) {
	$result = $participant->getTopics();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(501);
}
?>
