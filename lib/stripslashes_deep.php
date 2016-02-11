<?php
function stripslashes_deep($input){
	if (!is_array($input)) return stripslashes($input);
	reset($input);
	while (list($key,$val)=each($input)){
		$input[$key] = stripslashes_deep($val);
	}
	return $input;
}
?>
