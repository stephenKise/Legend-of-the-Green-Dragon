<?php
// addnews ready
// translator ready
// mail ready

// This function takes an array and makes sure each of the keys in the array
// is set as a global variable.  It's specifally set up to make sure we can
// get around sites that don't automatically register some globals we want to
// deal with.  Yes, we could find all references to this stuff and get rid
// of the dependancy, but it's not really worth it.
function register_global(&$var){
	@reset($var);
	while (list($key,$val)=@each($var)){
		global $$key;
		$$key = $val;
	}
	@reset($var);
}
?>
