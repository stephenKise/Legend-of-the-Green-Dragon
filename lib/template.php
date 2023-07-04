<?php
// translator ready
// addnews ready
// mail ready

function templatereplace($itemname,$vals=false){
	global $template;
	if (!isset($template[$itemname]))
		output("`bWarning:`b The `i%s`i template part was not found!`n", $itemname);
	$out = $template[$itemname];
	if (!is_array($vals)) return $out;
	@reset($vals);
	foreach ($vals as $key => $val) {
			if (strpos($out, "{{$key}}") === false) {
				output(
					"`bWarning: %s not found in the %s template part! (%s)`b`n",
					$key,
					$itemname,
					$out
				);
				$out .= $val;
			}
			else {
				$out = str_replace("{{$key}}", $val, $out);
			}
	}
	return $out;
}

function prepare_template($force=false){
	if (!$force) {
		if (defined("TEMPLATE_IS_PREPARED")) return;
		define("TEMPLATE_IS_PREPARED",true);
	}

 	global $templatename, $templatemessage, $template, $session, $y, $z, $y2, $z2, $copyright, $lc, $x;
	 if (!isset($_COOKIE['template'])) $_COOKIE['template']="";
	$templatename="";
	$templatemessage="";
	if ($_COOKIE['template']!="")
		$templatename=$_COOKIE['template'];
	if ($templatename == '' || !file_exists("templates/$templatename"))
		$templatename=getsetting("defaultskin", "jade.htm");
	$template = loadtemplate($templatename);
	if (isset($session['templatename']) &&
			$session['templatename'] == $templatename &&
			$session['templatemtime']==filemtime("templates/$templatename")){
		//We do not have to check that the template is valid since it has
		//not changed.
	}else{
		//We need to double check that the template is valid since the name
		// or file mod time have changed.

		//tags that must appear in the header
		$requiredTags = [
			'title', 'headscript', 'script', 'nav', 'stats',
			'petition', 'motd', 'mail', 'paypal', 'source',
			'version', 'copyright'
		];
		foreach ($requiredTags as $tagName) {
			if (strpos($template['header'], '{' . $tagName . '}') === false &&
					strpos($template['footer'], '{' . $tagName . '}') === false)
				$templatemessage .=
					"{{$tagName}} is not defined in your template\n";
		}
		if ($templatemessage==""){
			$session['templatename'] = $templatename;
			$session['templatemtime'] = filemtime("templates/$templatename");
		}
	}
	if ($templatemessage!=""){
		echo "<b>You have one or more errors in your template page!</b><br>".nl2br($templatemessage);
		$template=loadtemplate("jade.htm");
	}else {
		$y = 0;
		$z = $y2^$z2;
		if (isset($session['user']['loggedin']) && $session['user']['loggedin'] && $x > ''){
			$$z = $x;
		}
		if (isset($$z)) {
		$$z = $lc . $$z . "<br />";
		}
		else {
			$$z = $lc . "<br />";
		}
	}

}
?>
