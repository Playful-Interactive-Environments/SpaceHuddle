<?php

/**
 * Generates a random string.
 * @param int $length Length of the result string.
 * @param bool $case_sensitive Use lower case and upper case letters.
 * @return string Returns random string.
 */
function keygen(int $length=10, bool $case_sensitive=true) :  string
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
