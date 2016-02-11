<?php
// addnews ready
// mail ready
function namedmount_getmoduleinfo(){
	$info = array(
		"name"=>"Named Mounts",
		"author"=>"Eric Stevens",
		"version"=>"1.0",
		"download"=>"core_module",
		"category"=>"Lodge",
		"settings"=>array(
			"Name Mount Module Settings,title",
			"initialpoints"=>"How many donator points does it cost to buy the first mount name change?,int|300",
			"extrapoints"=>"How many donator points does it cost to do subsequent name changes?,int|50",
		),
		"prefs"=>array(
			"Name Mount User Preferences,title",
			"mountname"=>"Name of mount (blank to use default mount name)|",
			"boughtbefore"=>"Purchased a mount name in the past?,bool|0",
		),
	);
	return $info;
}

function namedmount_install(){
	module_addhook("lodge");
	module_addhook("everyhit-loggedin");
	module_addhook("bio-mount");
	module_addhook("stable-mount");
	module_addhook("pointsdesc");
	return true;
}
function namedmount_uninstall(){
	return true;
}

function namedmount_dohook($hookname,$args){
	switch($hookname){
	case "pointsdesc":
		$args['count']++;
		$format = $args['format'];
		$str = translate("The ability to name your mount (%s points for the first change, %s points thereafter)");
		$str = sprintf($str, get_module_setting("initialpoints"),
				get_module_setting("extrapoints"));
		output($format, $str, true);
		break;
	case "lodge":
		if (get_module_pref("boughtbefore"))
			$cost = get_module_setting("extrapoints");
		else
			$cost = get_module_setting("initialpoints");
		addnav(array("Name Your Mount (%s points)", $cost),"runmodule.php?module=namedmount&op=setname");
		break;
	case "everyhit-loggedin":
	case "stable-mount":
		global $playermount;
		$name = get_module_pref("mountname");
		if (isset($playermount['mountname'])) {
			$playermount['basename']=$playermount['mountname'];
			if ($name > "") {
				$playermount['mountname']=$name." `&the ".$playermount['basename'] . "`0";
				$playermount['newname']="$name`0";
			}
		}
		break;
	case "bio-mount":
		$name = get_module_pref("mountname",false,$args['acctid']);
		if (isset($args['mountname'])) {
			$args['basename']=$args['mountname'];
			if ($name > "") {
				$args['mountname']=$name." `&the ".$args['basename'] . "`0";
				$args['newname']="$name`0";
			}

		}
		break;
	}
	return $args;
}

function namedmount_run(){
	require_once("lib/sanitize.php");
	global $session;
	global $playermount;
	if (count($playermount)==0){
		page_header("Hunter's Lodge");
		output("You have to have a mount in order to name it, silly!");
		addnav("L?Return to the Lodge","lodge.php");
		page_footer();
	}
	$op = httpget("op");
	$pointsavailable=$session['user']['donation']-$session['user']['donationspent'];
	if ($op=="setname" || $op=="preview"){
		page_header("Hunter's Lodge");
		$name = get_module_pref("mountname");
		$boughtbefore = get_module_pref("boughtbefore");
		if ($boughtbefore){
			$cost = get_module_setting("extrapoints");
			output("`3You've previously named your mount `#%s the %s`3.  Because you've done a mount name in the past, you can change the name of your mount for %s points.`n", $name, $playermount['basename'], $cost);
		}else{
			$cost = get_module_setting("initialpoints");
			$ecost = get_module_setting("extrapoints");
			output("`3You can give your mount a name.  It takes %s donator points to do the first mount name, but you can rename your mount in the future for %s points each time.`n", $cost, $ecost);
		}
		$previewname = httppost("name");
		$previewname = comment_sanitize($previewname);
		$previewname = substr($previewname,0,25);
		while (substr($previewname,strlen($previewname)-1)=="`")
			$previewname = substr($previewname,0,strlen($previewname)-1);
		if ($previewname>"" && httppost("confirm")>""){
			if ($pointsavailable >= $cost){
				$showform = false;
				$session['user']['donationspent']+=$cost;
				set_module_pref("mountname",$previewname);
				set_module_pref("boughtbefore",true);
				$playermount['mountname'] = $previewname.' `&the '.$playermount['basename']."`0";
				output("`n`3Your mount is now named `#%s`3.`n", $playermount['mountname']);
				debuglog("Spent $cost donator points naming their mount {$playermount['mountname']}`0");
			}else{
				$showform = true;
				output("`nYou need `^%s`3 points to change your mount's name, but you only have `^%s`3.`n", $cost, $pointsavailable);
			}
		}else{
			$showform = true;
		}
		if ($previewname=="") $previewname = $name;

		if ($showform){
			output_notl("`0");
			rawoutput("<form action='runmodule.php?module=namedmount&op=preview' method='POST'>");
			addnav("","runmodule.php?module=namedmount&op=preview");
			if ($previewname>"" && $previewname!=$name) {
				output("`n`3Your mount's name will be `#%s `#the %s`3.  Press Confirm if you like this.`n", $previewname, $playermount['basename']);
			}
			output("`n`3Mount name (25 chars)? ");
			rawoutput("<script language='JavaScript'>
			var startname = \"$previewname\";
			function dropConfirm(val){
				var item = document.getElementById('confirm');
				if (val == startname) {
					item.style.visibility='visible';
					item.style.display='inline';
				}else{
					item.style.visibility='hidden';
					item.style.display='none';
				}
			}
			</script>");
			rawoutput("<input name='name' value=\"".htmlentities($previewname, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\" onChange=\"dropConfirm(this.value);\" onKeyUp=\"dropConfirm(this.value)\">",true);
			output_notl("<input name='preview' type='submit' class='button' value='Preview'>",true);
			output_notl("<input name='confirm' id='confirm' type='submit' class='button' value='Confirm' style='".
					($previewname>"" && $previewname!=$name?"visibility: visible; display: inline;":"visibility: hidden; display:none;").
					"'>",true);
			output("`nYou can use the same color codes that you use in comments for your mount's name.  If you change mounts, your new mount will carry your old mount's name.");
			output_notl("</form>",true);
		}
		addnav("L?Return to the Lodge","lodge.php");
		page_footer();
	}
}
?>
