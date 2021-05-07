<?php
require(__DIR__.'/../vendor/autoload.php');
$openapi = \OpenApi\scan([
  __DIR__.'/../controllers',
  __DIR__.'/../models'
]);
header('Content-Type: application/json');
echo $openapi->toJSON();
?>
