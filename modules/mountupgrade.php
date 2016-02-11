<?php
/*
Mount Upgrade
File: mountupgrade.php
Author:  Red Yates aka Deimos
Date:    8/11/2005
Version: 1.2 (8/15/2005)

Allows a mount to upgrade to another mount after defined requirements are met.

Version 1.1:
Added support for prize mounts. Mounts which upgrade from the prizemount are considered prizemounts.
Prizemounts will constantly approach their goal if they are upgradable, every time they are had.
Non-prizemount progress will be put on hold while a prizemount is had.

Version 1.2:
Added ability for mount to "upgrade" to nothing.

*/

function mountupgrade_getmoduleinfo(){
	$info=array(
		"name"=>"Mount Upgrade",
		"version"=>"1.2",
		"author"=>"`\$Red Yates",
		"category"=>"Mounts",
		"download"=>"core_module",
		"prefs"=>array(
			"Mount Upgrade Preferences,title",
			"upgrade"=>"Mount is ready to upgrade,bool|0",
			"upgradetext"=>"Text to output when upgrading,text|",
			"metdks"=>"Dragon kills earned towards upgrade,int|0",
			"metlevels"=>"Levels earned towards upgrade,int|0",
			"metdays"=>"Days earned towards upgrade,int|0",
			"metffs"=>"Forest fights earned towards upgrade,int|0",
			"prizemount"=>"Id of mount for saved stats,int|0",
			"savedks"=>"Dragon kills saved for inactive mount,int|0",
			"savelevels"=>"Levels saved for inactive mount,int|0",
			"savedays"=>"Days saved for inactive mount,int|0",
			"saveffs"=>"Forest fights saved for inactive mount,int|0",
		),
		"prefs-mounts"=>array(
			"Mount Upgrade Mount Preferences,title",
			"upgradeto"=>"Mount to which this one upgrades,mount|",
			"lose"=>"Upgrade to nothing,bool|0",
			"Upgrade to nothing overrides the standard upgrade preference,note",
			"upgradetext"=>"Text to output when upgrading,text|Your mount has been upgraded!",
			"reqdks"=>"Required DKs to upgrade,int|0",
			"reqlevels"=>"Required levels to upgrade,int|0",
			"reqdays"=>"Required days to upgrade,int|0",
			"reqffs"=>"Required forest fights to upgrade,int|0",
		),
	);
	return $info;
}

function mountupgrade_install(){
	module_addhook("pre-newday");
	module_addhook("newday");
	module_addhook("dragonkill");
	module_addhook("battle-victory");
	module_addhook("battle-defeat");
	module_addhook("showformextensions");
	module_addhook("boughtmount");
	module_addhook("soldmount");
	module_addhook("gainprizemount");
	module_addhook("loseprizemount");
	return true;
}

function mountupgrade_uninstall(){
	return true;
}

function mountupgrade_dohook($hookname, $args){
	global $session;
	if (is_module_active("prizemount")){
		$prizemount=get_module_setting("mountid","prizemount");
		if ($prizemount){
			$prizemounts=array($prizemount => 1);
			while($prizemount){
				$prizemount=get_module_objpref("mounts",$prizemount,"upgradeto","mountupgrade");
				if ($prizemount) $prizemounts[$prizemount]=1;
			}
		}
	}
	$upgradeto=get_module_objpref("mounts",$session['user']['hashorse'],"upgradeto");
	if (get_module_objpref("mounts",$session['user']['hashorse'],"lose"))
		$upgradeto=-1;
	$upgradetext=get_module_objpref("mounts",$session['user']['hashorse'],"upgradetext");
	$reqdks=get_module_objpref("mounts",$session['user']['hashorse'],"reqdks");
	$reqlevels=get_module_objpref("mounts",$session['user']['hashorse'],"reqlevels");
	$reqdays=get_module_objpref("mounts",$session['user']['hashorse'],"reqdays");
	$reqffs=get_module_objpref("mounts",$session['user']['hashorse'],"reqffs");
	$upgrade=get_module_pref("upgrade");
	$metdks=get_module_pref("metdks");
	$metlevels=get_module_pref("metlevels");
	$metdays=get_module_pref("metdays");
	$metffs=get_module_pref("metffs");
	switch($hookname){
	case "pre-newday":
		if ($upgradeto && $metdks>=$reqdks &&
				$metlevels>=$reqlevels && $metdays>=$reqdays &&
				$metffs>=$reqdks){
			set_module_pref("upgrade",1);
			set_module_pref("upgradetext",$upgradetext);
			set_module_pref("metdks",0);
			set_module_pref("metlevels",0);
			set_module_pref("metdays",0);
			set_module_pref("metffs",0);
			global $playermount;
			$debugmount1=$playermount['mountname'];
			if ($upgradeto==-1){
				 stripbuff("mount");
				$session['user']['hashorse']=0;
				debuglog("upgraded their $debugmount1 to nothing.");
			}else{
				$session['user']['hashorse']=$upgradeto;
				$playermount=getmount($session['user']['hashorse']);
				$debugmount2=$playermount['mountname'];
				debuglog("upgraded their $debugmount1 to $debugmount2.");
			}
		}
		break;
	case "newday":
		if ($upgrade){
			output("`n`^%s`0`n",translate_inline(get_module_pref("upgradetext")));
			$mount=getmount($session['user']['hashorse']);
			apply_buff("mount",unserialize($mount['mountbuff']));
			set_module_pref("upgrade",0);
		}
		if ($upgradeto && $reqdays) set_module_pref("metdays",$metdays+1);
		break;
	case "dragonkill":
		if ($upgradeto){
			if ($reqdks) set_module_pref("metdks",$metdks+1);
			if ($reqlevels) set_module_pref("metlevels",$metlevels+1);
		}
		break;
	case "battle-victory":
		static $runonce = false;
		if ($runonce !== false) break;
		$runonce = true;
		if ($upgradeto){
			global $options;
			if ($reqffs && ($options['type']=="forest" || $options['type']=="travel")) {
				set_module_pref("metffs",$metffs+1);
			}
			if ($reqlevels && $options['type']=="train") {
				set_module_pref("metlevels",$metlevels+1);
			}
		}
		break;
	case "battle-defeat":
		static $runonce = false;
		if ($runonce !== false) break;
		$runonce = true;
		global $options;
		if ($upgradeto && $reqffs && ($options['type']=="forest" || $options['type']=="travel")) {
			set_module_pref("metffs",$metffs+1);
		}
		break;
	case "showformextensions":
		$args['mount']="mountupgrade_showform";
		break;
	case "boughtmount":
	case "soldmount":
		set_module_pref("upgrade",0);
		set_module_pref("upgradetext","");
		set_module_pref("metdks",0);
		set_module_pref("metlevels",0);
		set_module_pref("metdays",0);
		set_module_pref("metffs",0);
		break;
	case "gainprizemount":
		$oldmount=get_module_pref("prizemount");
		$prizemount=get_module_pref("mountid","prizemount");
		if ($prizemount!=$oldmount){
			while($prizemount){
				$prizemount=get_module_objpref("mounts",$prizemount,"upgradeto","mountupgrade");
				if ($prizemount==$oldmount){
					$args['prizemount']=$prizemount;
					break;
				}
			}
		}
		if ($upgradeto){
			set_module_pref("savedks",$metdks);
			set_module_pref("savelevels",$metlevels);
			set_module_pref("savedays",$metdays);
			set_module_pref("saveffs",$metffs);
		}
		break;
	case "loseprizemount":
		set_module_pref("metdks",get_module_pref("savedks"));
		set_module_pref("metlevels",get_module_pref("savelevels"));
		set_module_pref("metdays",get_module_pref("savedays"));
		set_module_pref("metffs",get_module_pref("saveffs"));
		if ($upgradeto){
			set_module_pref("prizemount",$session['user']['hashorse']);
			set_module_pref("savedks",$metdks);
			set_module_pref("savelevels",$metlevels);
			set_module_pref("savedays",$metdays);
			set_module_pref("saveffs",$metffs);
		}
		break;
	}
	return $args;
}

function mountupgrade_showform($keyout, $val, $info){
	$sql = "SELECT mountid,mountname,mountcategory FROM ".db_prefix("mounts")." ORDER BY mountcategory, mountid";
	$result = db_query($sql);
	rawoutput("<select name='$keyout'><option value='0'>".translate_inline("None")."</option>");
	while ($row = db_fetch_assoc($result)){
		$mountid=$row['mountid'];
		$mountcategory=$row['mountcategory'];
		$mountname=color_sanitize($row['mountname']);
		if ($val==$mountid){
			rawoutput("<option value='$mountid' selected>".htmlentities("$mountcategory: $mountname", ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</option>");
		}else{
			rawoutput("<option value='$mountid'>".htmlentities("$mountcategory: $mountname", ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</option>");
		}
	}
	rawoutput("</select>");
}

function mountupgrade_run(){
}

?>
