<?php
// translator ready
// addnews ready
// mail ready
define("ALLOW_ANONYMOUS",true);
define("OVERRIDE_FORCED_NAV",true);
require_once("common.php");
require_once("lib/errorhandling.php");
require_once("lib/http.php");

tlschema("source");

$url=httpget('url');
if ($url) {
	popup_header("Source code for %s", $url);
} else {
	popup_header("Source code");
}
if (!($session['user']['loggedin'] && $session['user']['superuser'] & SU_VIEW_SOURCE) || !isset($session['user']['loggedin'])) {
	output("Due to the behaviour of people in the past, access to the source code online has been restricted.");
	output("You may download the entirety of the latest publically released stable version from <a href='http://www.dragonprime.net' target='_blank'>DragonPrime</a>.", true);
	output("You may then work with that code within the restrictions of its license.");
	output("`n`nHopefully this will help put an end to actions like the following:");
	rawoutput("<ul><li>");
	output("Releasing code which they do not own without permission.");
	rawoutput("</li><li>");
	output("Removing valid copyright information from code and replacing it.");
	rawoutput("</li><li>");
	output("Removing portions of the code required to be kept intact by licensing.");
	rawoutput("</li><li>");
	output("Claiming copyright of items which they did not create.");
	rawoutput("</li></ul>");
	popup_footer();
} else {
	$legal_start_dirs = array(
		"" => 1,
		"lib/*" => 1,
		"modules/*" => 1,
		"modules/avatar" => 0, // No PHP files, so don't show
	);
	if ($url) {
		$dirname = dirname($url);
		foreach ($legal_start_dirs as $dirs=>$value) {
			if (strpos($dirs,"/") === false || !$value) {
				continue;
			}
			if (strpos($dirs,"/*")) {
				$ghjkl = str_replace("/*","",$dirs);
				$dirname = preg_replace("!".$ghjkl."/?\\w*/?!","",$dirname);
			}else {
				$ghjkl = str_replace("/","",$dirs);
				$dirname = preg_replace("!".$ghjkl."/?!","",$dirname);
			}
		}
		$dirname = preg_replace("/\\A\\./","",$dirname);
		$length = strlen($dirname);
		$url = substr($url,$length);
		if (strpos($url,"/") === 0) {
			$url = substr($url,1);
		}
	}
	$select_dir = httpget("dir");
	if (!$select_dir) {
		$select_dir = "";
	}
	$select_dir = "./$select_dir";
	$illegal_files = array(
		"dbconnect.php"=>"Contains sensitive information specific to this installation.",
		"dragon.php"=>"If you want to read the dragon script, I suggest you do so by defeating it!",
		"output_translator.php"=>"X", // hidden
		"pavilion.php"=>"Not released at least for now.",
		"source.php"=>"X", //hide completely -- so that people can't see the names of the other completely hidden files.
		"remotebackup.php"=>"X", // hide completely
		"remotequery.php"=>"X", // hide completely
		"lib/datatable.php"=>"X", // hide completely
		"lib/dbremote.php"=>"X", //hide completely
		"lib/smsnotify.php"=>"X", //hide completely
		"modules/battlearena.php"=>"X", // not for dist
		"modules/blog.php"=>"X", // not for dist
		"modules/clues.php"=>"X", // hidden
		"modules/lycanthropy.php"=>"X", // hidden
		"modules/mutagens.php"=>"X", // hidden
		"modules/privacy.php"=>"X", // hidden
		"modules/store.php"=>"X", // not for dist
		"modules/tournament.php"=>"X", // hide
	);
	$legal_files=array();

	rawoutput("<h1>");
	output("View Source: ");
	output_notl("%s", htmlentities($url, ENT_COMPAT, getsetting("charset", "ISO-8859-1")));
	rawoutput("</h1>");
	if($url) output("<a href='#source'>Click here for the source,</a> OR`n", true);
	output("`bOther files that you may wish to view the source of:`b");
	rawoutput("<ul>");
	// Gather all the legal dirs
	$legal_dirs = array();
	foreach ($legal_start_dirs as $dir=>$value) {
		// If this is a dir to exclude, skip it
		if (!$value) continue;

		$sdir = $dir;
		$base = "./$sdir";

		// If this is not a 'recursive' dir, add it and continue
		if (!strstr($base, "/*")) {
			array_push($legal_dirs, $base);
			continue;
		}

		// Strip of the /*
		$base = substr($base, 0, -2);
		array_push($legal_dirs, $base . "/");
		$d = dir("$base");
		while($entry = $d->read()) {
			// Skip any . files
			if ($entry[0] == '.') continue;
			// skip any php files
			if (substr($entry,strrpos($entry, '.')) == ".php") continue;
			$ndir = $base . "/" . $entry;
			// Okay, check if it's a directory
			$test = preg_replace("!^\\./!", "", $ndir);
			if (is_dir($ndir)) {
				if ((!isset($legal_start_dirs[$test]) ||
					$legal_start_dirs[$test] != 0) && ((strpos(strtolower($select_dir),strtolower($ndir)) !== false) || (strpos(strtolower($ndir),strtolower($select_dir)) !== false)) && $select_dir != "./") {
						array_push($legal_dirs, $ndir . "/");
				}
			}
		}
	}
	foreach ($legal_dirs as $key) {
		//$skey = substr($key,strlen($subdir));
		//if ($key==dirname($_SERVER['SCRIPT_NAME'])) $skey="";
		//$d = dir("./$skey");
		//if (substr($key,0,2)=="//") $key = substr($key,1);
		//if ($key=="//") $key="/";
		// Gaurentee a sort order on source files - Hidehisa Yasuda
		$key1 = substr($key, 2);
		$key2 = $key1;
		$skey = "//" . $key1;
		if ($key != $select_dir) {
			rawoutput("<li>Folder: <a href='source.php?dir=$key1'>".($key1==""?"/":$key1)."</a></li>\n");
			continue;
		}

		$d = dir("$key");
		$files = array();
		while (false !== ($entry = $d->read())) {
			if (substr($entry,strrpos($entry,"."))==".php"){
				array_push($files, "$entry");
			}
		}
		$d->close();
		asort($files);
		foreach($files as $entry) {
			if (isset($illegal_files["$key2$entry"]) &&
					$illegal_files["$key2$entry"]!=""){
					if ($illegal_files["$key2$entry"]=="X"){
					//we're hiding the file completely.
					}else{
					rawoutput("<li>$key1$entry");
					$reason = translate_inline($illegal_files[$key2 . $entry]);
					output("&#151; This file cannot be viewed: %s", $reason, true);
					rawoutput("</li>\n");
					}
			}else{
				rawoutput("<li><a href='source.php?url=$key1$entry&amp;dir=$key1'>$key1$entry</a> &#151; ".date("Y-m-d H:i:s",filemtime($key."/".$entry))."</li>\n");
				$legal_files["$key1$entry"]=true;
			}
		}
	}
	rawoutput("</ul>");
	if ($url) {
		rawoutput("<h1><a name='source'>");
		output("Source of: %s", htmlentities($url, ENT_COMPAT, getsetting("charset", "ISO-8859-1")));
		rawoutput("</a></h1>");

		$page_name = $url;
		if (substr($page_name,0,1)=="/") $page_name=substr($page_name,1);
		if ($legal_files[$url]){
			rawoutput("<table bgcolor=#cccccc>");
			rawoutput("<tr><td>");
			rawoutput("<font size=-1>");
			ob_start();
			show_source($page_name);
			$t = ob_get_contents();
			ob_end_clean();
			rawoutput($t);
			rawoutput("</font>", true);
			rawoutput("</td></tr></table>", true);
		}else if ($illegal_files[$url]!="" && $illegal_files[$url]!="X"){
			$reason = translate_inline($illegal_files[$url]);
			output("`nCannot view this file: %s`n", $reason);
		}else {
			output("`nCannot view this file.`n");
		}
	}
	popup_footer();
}
?>