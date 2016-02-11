<?php
// addnews ready
// translator ready
// mail ready
function safeescape($input){
	$prevchar="";
	$out="";
	for ($x=0;$x<strlen($input);$x++){
		$char = substr($input,$x,1);
		if (($char=="'" || $char=='"') && $prevchar!="\\"){
			$char="\\$char";
		}
		$out.=$char;
		$prevchar=$char;
	}
	return $out;
}
?>
