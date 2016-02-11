<?php
// translator ready
// addnews ready
// mail ready
function is_email($email){
	return preg_match("/[[:alnum:]_.-]+[@][[:alnum:]_.-]{2,}\\.[[:alnum:]_.-]{2,}/",$email);
}
?>
