<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require "../vendor/autoload.php";

use PieLab\GAB\Controllers\Controller;
use PieLab\GAB\Controllers\VotingController;

$voting = VotingController::getInstance();

if (Controller::isRestCall("GET")) {
	$result = $voting->get();
	echo $result;
}
elseif (Controller::isRestCall("PUT")) {
	$result = $voting->update();
	echo $result;
}
elseif (Controller::isRestCall("DELETE")) {
	$result = $voting->delete();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(501);
}
?>
