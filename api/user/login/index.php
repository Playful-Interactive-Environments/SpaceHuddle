<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__.'/../../controllers/login.php');
require_once(__DIR__.'/../../controllers/controller.php');

$login = Login_Controller::get_instance();

if (Controller::is_rest_call("POST", 2)) {
	$result = $login->login();
	echo $result;
}
else {
	echo "Call ".$_SERVER["REQUEST_METHOD"]." is not yet implemented";
	http_response_code(405);
}
?>
