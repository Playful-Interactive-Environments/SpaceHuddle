<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require "../vendor/autoload.php";

use PieLab\GAB\Controllers\Controller;
use PieLab\GAB\Controllers\SelectionController;
use PieLab\GAB\Controllers\SelectionIdeaController;

$selection = SelectionController::getInstance();
$selectionIdea = SelectionIdeaController::getInstance();

if (Controller::isRestCall("GET")) {
	$result = $selection->read();
	echo $result;
}
elseif (Controller::isRestCall("PUT")) {
	$result = $selection->update();
	echo $result;
}
elseif (Controller::isRestCall("DELETE")) {
	$result = $selection->delete();
	echo $result;
}
elseif (Controller::isRestCall("GET", search_detail_hierarchy: "ideas")) {
	$result = $selectionIdea->readIdeas();
	echo $result;
}
elseif (Controller::isRestCall("POST", search_detail_hierarchy: "ideas")) {
	$result = $selectionIdea->addIdeas();
	echo $result;
}
elseif (Controller::isRestCall("DELETE", search_detail_hierarchy: "ideas")) {
	$result = $selectionIdea->deleteIdeas();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(501);
}
?>
