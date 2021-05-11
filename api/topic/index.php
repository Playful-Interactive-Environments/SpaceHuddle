<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__.'/../controllers/topic.php');
require_once(__DIR__.'/../controllers/task.php');
require_once(__DIR__.'/../controllers/idea.php');
require_once(__DIR__.'/../controllers/group.php');
require_once(__DIR__.'/../controllers/controller.php');

$topic = Topic_Controller::get_instance();
$task = Task_Controller::get_instance();
$idea = Idea_Controller::get_instance();
$group = Group_Controller::get_instance();

if (Controller::is_rest_call("GET")) {
	$result = $topic->read();
	echo $result;
}
elseif (Controller::is_rest_call("PUT")) {
	$result = $topic->update();
	echo $result;
}
elseif (Controller::is_rest_call("DELETE")) {
	$result = $topic->delete();
	echo $result;
}
elseif (Controller::is_rest_call("GET", search_detail_hierarchy: "tasks")) {
	$result = $task->read_all();
	echo $result;
}
elseif (Controller::is_rest_call("POST", search_detail_hierarchy: "task")) {
	$result = $task->add();
	echo $result;
}
elseif (Controller::is_rest_call("GET", search_detail_hierarchy: "ideas")) {
	$result = $idea->read_all_from_topic();
	echo $result;
}
elseif (Controller::is_rest_call("POST", search_detail_hierarchy: "idea")) {
	$result = $idea->add_to_topic();
	echo $result;
}
elseif (Controller::is_rest_call("GET", search_detail_hierarchy: "groups")) {
	$result = $group->read_all_from_topic();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(405);
}
?>
