<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__.'/../controllers/session.php');
require_once(__DIR__.'/../controllers/topic.php');
require_once(__DIR__.'/../controllers/controller.php');

$session = Session_Controller::get_instance();
$topic = Topic_Controller::get_instance();

if (Controller::is_rest_call("GET")) {
	$result = $session->read();
	echo $result;
}
elseif (Controller::is_rest_call("POST")) {
	#foreach ($_SERVER as $key => $value) {
	#	echo $key.": ".$value."\n";
	#}
	$result = $session->add();
	echo $result;
}
elseif (Controller::is_rest_call("PUT")) {
	$result = $session->update();
	echo $result;
}
elseif (Controller::is_rest_call("DELETE")) {
	$result = $session->delete();
	echo $result;
}
elseif (Controller::is_rest_call("GET", search_detail_hierarchy: "topics")) {
	$result = $topic->read_all();
	echo $result;
}
elseif (Controller::is_rest_call("POST", search_detail_hierarchy: "topic")) {
	$result = $topic->add();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(405);
}
?>
