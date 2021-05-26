<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__.'/../controllers/login.php');
require_once(__DIR__.'/../controllers/controller.php');

$login = LoginController::getInstance();

if (Controller::isRestCall("POST", 2, search_detail_hierarchy: "login")) {
	$result = $login->login();
	echo $result;
}
elseif (Controller::isRestCall("POST", 2, search_detail_hierarchy: "register")) {
	$result = $login->register();
	echo $result;
}
elseif (Controller::isRestCall("PUT")) {
	$result = $login->update();
	echo $result;
}
elseif (Controller::isRestCall("DELETE")) {
	$result = $login->delete();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(501);
}
?>
