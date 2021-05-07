<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__.'/../../controllers/participant.php');
require_once(__DIR__.'/../../controllers/controller.php');


$participant = Participant_Controller::get_instance();
if (Controller::is_rest_call("GET", 2)) {
	$result = $participant->reconnect();
	echo $result;
}
elseif (Controller::is_rest_call("POST", 2)) {
	$result = $participant->connect();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(405);
}
?>
