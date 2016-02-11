<?php
// translator ready
// addnews ready
// mail ready
function nltoappon($in){
	$out = str_replace("\r\n","\n",$in);
	$out = str_replace("\r","\n",$out);
	$out = str_replace("\n","`n",$out);
	return $out;
}
?>
