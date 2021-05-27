<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require "../vendor/autoload.php";

use PieLab\GAB\Controllers\Controller;
use PieLab\GAB\Controllers\ParticipantController;

$participant = ParticipantController::getInstance();

if (Controller::isRestCall("GET", 2, searchDetailHierarchy: "connect")) {
	$result = $participant->reconnect();
	echo $result;
}
elseif (Controller::isRestCall("POST", 2, searchDetailHierarchy: "connect")) {
	$result = $participant->connect();
	echo $result;
}
elseif (Controller::isRestCall("GET", 2, searchDetailHierarchy: "tasks")) {
	$result = $participant->getTasks();
	echo $result;
}
elseif (Controller::isRestCall("GET", 2, searchDetailHierarchy: "topics")) {
	$result = $participant->getTopics();
	echo $result;
}
elseif (Controller::isRestCall("DELETE")) {
	$result = $participant->delete();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(501);
}
?>
