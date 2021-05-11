<?php

function keygen($length=10, $case_sensitive=true)
{
	$key = '';
	list($usec, $sec) = explode(' ', microtime());
	mt_srand((float) $sec + ((float) $usec * 100000));

  $inputs = array_merge(range('z','a'),range(0,9),range('A','Z'));
  if (!$case_sensitive) {
    $inputs = array_merge(range(0,9),range('A','Z'));
  }

 	for($i=0; $i<$length; $i++)
	{
    $index = mt_rand(0,count($inputs)-1);
    $key .= $inputs[$index];
	}
	return $key;
}

?>
