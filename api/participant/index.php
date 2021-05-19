<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__.'/../controllers/participant.php');
require_once(__DIR__.'/../controllers/controller.php');


$participant = Participant_Controller::get_instance();
if (Controller::is_rest_call("GET", 2, search_detail_hierarchy: "connect")) {
	$result = $participant->reconnect();
	echo $result;
}
elseif (Controller::is_rest_call("POST", 2, search_detail_hierarchy: "connect")) {
	$result = $participant->connect();
	echo $result;
}
elseif (Controller::is_rest_call("GET", 2, search_detail_hierarchy: "tasks")) {
	$result = $participant->get_tasks();
	echo $result;
}
elseif (Controller::is_rest_call("GET", 2, search_detail_hierarchy: "topics")) {
	$result = $participant->get_topics();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(501);
}
?>
