<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__.'/../controllers/group.php');
require_once(__DIR__.'/../controllers/group_idea.php');
require_once(__DIR__.'/../controllers/controller.php');

$group = Group_Controller::get_instance();
$group_idea = Group_Idea_Controller::get_instance();

if (Controller::is_rest_call("GET")) {
	$result = $group->read();
	echo $result;
}
elseif (Controller::is_rest_call("PUT")) {
	$result = $group->update();
	echo $result;
}
elseif (Controller::is_rest_call("DELETE")) {
	$result = $group->delete();
	echo $result;
}
elseif (Controller::is_rest_call("GET", search_detail_hierarchy: "ideas")) {
	$result = $group_idea->read_ideas();
	echo $result;
}
elseif (Controller::is_rest_call("POST", search_detail_hierarchy: "ideas")) {
	$result = $group_idea->add_ideas();
	echo $result;
}
elseif (Controller::is_rest_call("DELETE", search_detail_hierarchy: "ideas")) {
	$result = $group_idea->delete_ideas();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(405);
}
?>
