<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__.'/../controllers/session.php');
require_once(__DIR__.'/../controllers/controller.php');

$session = SessionController::getInstance();

if (Controller::isRestCall("GET")) {
	$result = $session->readAll();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(501);
}
?>
