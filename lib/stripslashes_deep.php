<?php
function stripslashes_deep($input){
	if (!is_array($input)) return stripslashes($input);
	reset($input);
	foreach ($input as $key => $val) {
		$input[$key] = stripslashes_deep($val);
	}
	return $input;
}
?>
