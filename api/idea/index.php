<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__.'/../controllers/idea.php');
require_once(__DIR__ . '/../controllers/Controller.php');

$idea = IdeaController::getInstance();

if (Controller::isRestCall("GET")) {
	$result = $idea->read();
	echo $result;
}
elseif (Controller::isRestCall("PUT")) {
	$result = $idea->update();
	echo $result;
}
elseif (Controller::isRestCall("DELETE")) {
	$result = $idea->delete();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(501);
}
?>
