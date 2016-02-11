<?php
/*
Details:
 * This is a module for the Grotto
 * It allows you to see which modules have a location setting.
History Log:
 v1.0:
 o Seems to be Stable
 v1.1:
 o Amount counter added
*/
require_once("lib/superusernav.php");
require_once("lib/dbwrapper.php");
require_once("lib/debuglog.php");

function modloc_getmoduleinfo(){
	$info = array(
		"name"=>"Module Locations",
		"version"=>"1.0",
		"author"=>"`^CortalUX",
		"category"=>"Administrative",
		"download"=>"core_module",
	);
	return $info;
}

function modloc_install(){
	module_addhook("footer-modules");
	module_addhook("superuser");
	return true;
}

function modloc_uninstall(){
	return true;
}

function modloc_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "footer-modules":
		if ($session['user']['superuser'] & SU_MANAGE_MODULES) {;
			addnav("Navigation");
			addnav("View Module Locations",
					"runmodule.php?module=modloc&admin=true");
		}
		break;
	case "superuser":
		if ($session['user']['superuser'] & SU_MANAGE_MODULES) {;
			addnav("Mechanics");
			addnav("View Module Locations",
					"runmodule.php?module=modloc&admin=true");
		}
		break;
	}
	return $args;
}

function modloc_run(){
	global $session;
	page_header("Module Location List");
	superusernav();
	addnav("Module Locations");
	addnav("Show All","runmodule.php?module=modloc&admin=true");
	addnav("Show Errors","runmodule.php?module=modloc&op=error&admin=true");
	addnav("Filter");
	$op = httpget('op');
	$loc = httpget('loc');
	$locations = array();
	$t = getsetting("villagename", LOCATION_FIELDS);
	$locations = modulehook("validlocation");
	$locations[$t] = 0;
	$error = 0;
	foreach ($locations as $name => $sname) {
		addnav(array("%s", $name),
				"runmodule.php?module=modloc&admin=true&loc=".$name);
		$locations[$name]=0;
	}
	$sql = "SELECT modulename FROM ".db_prefix("modules");
	$result = db_query($sql);
	output("`@`c`iDue to the fact that some sites can become very cluttered, this tool allows you to see all modules with location settings, and to which location they are hooked. You can also view by city.`i`c`n`n");
	if ($loc==""&&$op=="") {
		$t = translate_inline("Currently showing all location settings.");
	} elseif ($op=="error") {
		$t = translate_inline("Currently showing modules hooked to nonexistent locations.");
	} else {
		$t = translate_inline("Only showing modules that are currently hooked to `@%s`&.");
		$t = str_replace("%s", $loc, $t);
	}
	output_notl("`c`b`&%s`b`c",$t);
	output("`n`^`bModule Location List:`b`0`n");
	output_notl("<table border=1 style='text-align:center;'><tr class='trhead'><td>`&`b%s`b`0</td><td>`@`b%s`b`0</td><td>`^`b%s`b`0</td><td>`#`b%s`b`0</td></tr>",translate_inline("Module Name"),translate_inline("Location Question"),translate_inline("Current Location"),translate_inline("Settings Link"),true);
	$n=0;
	$s = translate_inline("Settings");
	for ($i=0;$i<db_num_rows($result);$i++){
		$row = db_fetch_assoc($result);
		$info = get_module_info($row['modulename']);
		if (isset($info['settings']) && count($info['settings'])>0){
			foreach ($info['settings'] as $key=>$val) {
				if (isset($val)&&!empty($val)&&isset($key)&&!empty($key)) {
					if (is_array($val)) {
						$v = $val[0];
						$x = explode("|", $v);
						$val[0] = $x[0];
						$x[0] = $val;
					} else {
						$x = explode("|", $val);
					}
					$type = split(",", $x[0]);
					if (isset($type[1])) $type = trim($type[1]);
					else $type = "string";

					if ($type=="location") {
						$n++;
						$l = get_module_setting($key,$row['modulename']);
						if (isset($locations[$l])) {
							$locations[$l]++;
							if ($loc==$l&&$loc!=""||$loc==""&&$op!="error") {
								output_notl("<tr class='".($n%2==0?"trdark":"trlight")."'><td>`&".$info['name']."`0</td>",true);
								output_notl("<td>`@".$x[0]."`0</td>",true);
								output_notl("<td>`^".$l."`0</td>",true);
								output_notl("<td>`#[<a href='configuration.php?op=modulesettings&module=".$row['modulename']."'>%s</a>`#]`0</td>",$s,true);
								addnav("","configuration.php?op=modulesettings&module=".$row['modulename']);
								rawoutput("</tr>");
							}
						}else {
							$error++;
							if ($loc==""||$op=="error") {
								output_notl("<tr class='".($n%2==0?"trdark":"trlight")."'><td>`&".$info['name']."`0</td>",true);
								output_notl("<td>`@".$x[0]."`0</td>",true);
								output_notl("<td>`b`\$ERROR!!`b`n`^".$l."`0</td>",true);
								output_notl("<td>`#[<a href='configuration.php?op=modulesettings&module=".$row['modulename']."'>%s</a>`#]`0</td>",$s,true);
								addnav("","configuration.php?op=modulesettings&module=".$row['modulename']);
								rawoutput("</tr>");
							}
						}
					}
				}
			}
		}
	}
	if ($n==0) {
		output_notl("<tr class='trlight'><td colspan=4>`c`b`@%s`b`c`0</td></tr>",translate_inline("No modules are hooked to this location."),true);
	}
	rawoutput("</table>");
	output("`n`@There are `^%s`@ module location settings.`0",$n);
	foreach ($locations as $name => $amount) {
		output("`n`^%s`@ has `^%s`@ modules hooked to it.",$name,$amount);
	}
	if ($error!=0) {
		output("`n`%There are `^%s`% modules hooked to nonexistent locations.",$error);
	}
	page_footer();
}
?>
