<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__ . '/../controllers/SessionController.php');
require_once(__DIR__.'/../controllers/topic.php');
require_once(__DIR__ . '/../controllers/Controller.php');

$session = SessionController::getInstance();
$topic = TopicController::getInstance();

if (Controller::isRestCall("GET")) {
	$result = $session->read();
	echo $result;
}
elseif (Controller::isRestCall("POST")) {
	#foreach ($_SERVER as $key => $value) {
	#	echo $key.": ".$value."\n";
	#}
	$result = $session->add();
	echo $result;
}
elseif (Controller::isRestCall("PUT")) {
	$result = $session->update();
	echo $result;
}
elseif (Controller::isRestCall("DELETE")) {
	$result = $session->delete();
	echo $result;
}
elseif (Controller::isRestCall("GET", search_detail_hierarchy: "topics")) {
	$result = $topic->readAll();
	echo $result;
}
elseif (Controller::isRestCall("POST", search_detail_hierarchy: "topic")) {
	$result = $topic->add();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(501);
}
?>
