<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require "../vendor/autoload.php";

use PieLab\GAB\Controllers\AbstractController;
use PieLab\GAB\Controllers\SelectionController;
use PieLab\GAB\Controllers\SelectionIdeaController;

$selection = SelectionController::getInstance();
$selectionIdea = SelectionIdeaController::getInstance();

if (AbstractController::isRestCall("GET")) {
	$result = $selection->read();
	echo $result;
}
elseif (AbstractController::isRestCall("PUT")) {
	$result = $selection->update();
	echo $result;
}
elseif (AbstractController::isRestCall("DELETE")) {
	$result = $selection->delete();
	echo $result;
}
elseif (AbstractController::isRestCall("GET", searchDetailHierarchy: "ideas")) {
	$result = $selectionIdea->readIdeas();
	echo $result;
}
elseif (AbstractController::isRestCall("POST", searchDetailHierarchy: "ideas")) {
	$result = $selectionIdea->addIdeas();
	echo $result;
}
elseif (AbstractController::isRestCall("DELETE", searchDetailHierarchy: "ideas")) {
	$result = $selectionIdea->deleteIdeas();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(501);
}
?>
