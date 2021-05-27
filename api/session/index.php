<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require "../vendor/autoload.php";

use PieLab\GAB\Controllers\AbstractController;
use PieLab\GAB\Controllers\SessionController;
use PieLab\GAB\Controllers\TopicController;

$topic = TopicController::getInstance();
$session = SessionController::getInstance();

if (AbstractController::isRestCall("GET")) {
	$result = $session->read();
	echo $result;
}
elseif (AbstractController::isRestCall("POST")) {
	#foreach ($_SERVER as $key => $value) {
	#	echo $key.": ".$value."\n";
	#}
	$result = $session->add();
	echo $result;
}
elseif (AbstractController::isRestCall("PUT")) {
	$result = $session->update();
	echo $result;
}
elseif (AbstractController::isRestCall("DELETE")) {
	$result = $session->delete();
	echo $result;
}
elseif (AbstractController::isRestCall("GET", searchDetailHierarchy: "topics")) {
	$result = $topic->readAll();
	echo $result;
}
elseif (AbstractController::isRestCall("POST", searchDetailHierarchy: "topic")) {
	$result = $topic->add();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(501);
}
?>
