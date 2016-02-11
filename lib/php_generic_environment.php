<?php
// addnews ready
// translator ready
// mail ready
function sanitize_uri(){
	global $PATH_INFO,$SCRIPT_NAME,$REQUEST_URI;
	if (isset($PATH_INFO) && $PATH_INFO!="") {
		$SCRIPT_NAME=$PATH_INFO;
		$REQUEST_URI="";
	}
	if ($REQUEST_URI==""){
		//necessary for some IIS installations (CGI in particular)
		$get = httpallget();
		if (count($get) > 0) {
			$REQUEST_URI=$SCRIPT_NAME."?";
			reset($get);
			$i=0;
			while (list($key,$val)=each($get)){
				if ($i>0) $REQUEST_URI.="&";
				$REQUEST_URI.="$key=".URLEncode($val);
				$i++;
			}
		}else{
			$REQUEST_URI=$SCRIPT_NAME;
		}
		$_SERVER['REQUEST_URI'] = $REQUEST_URI;
	}
	$SCRIPT_NAME=substr($SCRIPT_NAME,strrpos($SCRIPT_NAME,"/")+1);
	if (strpos($REQUEST_URI,"?")){
		$REQUEST_URI=$SCRIPT_NAME.substr($REQUEST_URI,strpos($REQUEST_URI,"?"));
	}else{
		$REQUEST_URI=$SCRIPT_NAME;
	}
}
function php_generic_environment(){
	require_once("lib/register_global.php");
	register_global($_SERVER);
	sanitize_uri();
}
?>
